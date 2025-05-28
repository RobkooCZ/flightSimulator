<?php
/**
 * FileHandler class
 *
 * Handles file operations for user profile pictures, including validation, saving, and database updates.
 *
 * @file FileHandler.php
 * @since 0.7.7
 * @package Utilities
 * @author Robkoo
 * @license TBD
 * @version 0.7.7
 */

declare(strict_types=1);

namespace WebDev\Utilities;

// logging purposes
use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Logger;

/**
 * FileHandler class.
 * 
 * Handles file operations for user profile pictures. Extends the `Handler` class and implements its abstract methods. This class also contains file-specific methods for the flow of this project.
 *
 * @package Utilities
 * @since 0.7.7
 * @see WebDev\Utilities\Handler
 */
final class FileHandler extends Handler {
    /**
     * Relative path to the folder with profile pictures.
     * @var string
    */
    public const FILE_FOLDER_PATH = __DIR__ . "/../../../assets/images/pfps";

    /**
     * An array of allowed file types.
     * @var array<int,string>
     */
    private const ALLOWED_FILE_TYPES = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];

    /**
     * The max filesize the user image is allowed to be. Currently set at **5MiB** *(~5.24MB)*.
     * @var int
     */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Regex to validate if the database result is an image path or not.
     * @var string
     */
    private const DB_IMAGE_PATH_REGEX = "/^assets\/images\/pfps\/[0-9]+_\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.(png|jpe?g|webp)$/";

    /**
     * Check whether the provided `$resource` is a path to an image or not.
     *
     * @param string $resource The resource to check
     * @return bool True if its a path, false if it isn't.
     */
    final public static function checkForPath(string $resource): bool {
        return (bool)preg_match(self::DB_IMAGE_PATH_REGEX, $resource);
    }

    /**
     * Method to generate a file name based on the User ID, current date and time, and the provided `$extension`.
     *
     * @param string $extension The extension of the file.
     * @return string The filename.
     */
    final public function generateFileName(string $extension): string {
        return $this->uid . "_" . date("Y-m-d") . "-" . date("H-i-s") .  '.' . $extension;
    }

    /**
     * Method to save the profile picture locally into the provided `$path`.
     *
     * @param string $path Where to save the file.
     * @return bool True on success, false on failure.
     */
    final public function savePfpLocally(string $path): bool {
        // get the temp location of the uploaded file
        $fileTmpPath = $_FILES['img']['tmp_name'];

        // move the file to the local folder
        if (move_uploaded_file($fileTmpPath, $path)) return true;
        else return false;
    }
    
    /**
     * Validate the provided image.
     *
     * @param array<string,int|string>|string $resource The image to validate.
     * @return array<string,bool|string> An associative array containing a success flag, and messages for the user and the backend.
     */
    final public function validate(array|string $resource): array {
        // check if the temp name of the image is set
        if (!isset($resource['tmp_name'])){
            return [
                "success" => false,
                "message" => "Invalid image provided, please try a different one.",
                "backendMessage" => "Temporary name not set in the provided image."
            ];
        }

        // check whether the upload went well
        if ($resource['error'] !== UPLOAD_ERR_OK){
            return [
                "success" => false,
                "message" => "Error occurred during file upload.",
                "backendMessage" => "File upload error code: {$resource['error']}."
            ];
        }

        // check whether the file was uploaded via HTTP 
        if (!is_uploaded_file($resource['tmp_name'])){
            return [
                "success" => false,
                "message" => "Invalid file upload.",
                "backendMessage" => "File is not a valid uploaded file."
            ];
        }

        /**
         * Retrieve image info.
         * @var array<int|string,int|string> $info
         */
        $info = getimagesize($resource['tmp_name']);

        // if we failed to retrieve image info
        if ($info === false){
            return [
                "success" => false,
                "message" => "Invalid image file.",
                "backendMessage" => "Failed to retrieve image information."
            ];
        }

        // get the mime info
        /**
         * @var int|string
         */
        $mime = $info['mime'];

        // validate the type of the image
        if (!in_array($mime, self::ALLOWED_FILE_TYPES)){
            return [
                "success" => false,
                "message" => "Unsupported file type.",
                "backendMessage" => "File type {$mime} is not allowed."
            ];
        }
        
        // validate the size of the provided image
        if ($resource['size'] > self::MAX_FILE_SIZE){
            return [
                "success" => false,
                "message" => "File size exceeds the limit.",
                "backendMessage" => "File size is larger than 5MiB."
            ];
        }

        // user image passed validation, return a success flag and a generic message
        return [
            "success" => true,
            "message" => "Image validation passed.",
            "backendMessage" => "Image validation passed."
        ];
    }

    /**
     * Upload the provided profile picture path to the database and save the picture locally.
     *
     * @param array<string,int|string>|string $resource The image file to upload
     * @return array<string,string|bool> Associative array with a success flag, and messages for user reading and the backend
     */
    final public function upload(array|string $resource): array {
        // validate the provided image
        /**
         * @var array<string,string> $validationResult
         */
        $validationResult = $this->validate($resource);

        // failed to pass validation
        if ($validationResult['success'] === false){
            return [
                "success" => false,
                "message" => $validationResult['message'],
                "backendMessage" => $validationResult['backendMessage']
            ];
        }

        // get the file extension
        /**
         * The file extension.
         * @var string
         */
        $extension = strtolower(pathinfo($resource['name'], PATHINFO_EXTENSION));

        // generate a file name
        /**
         * The generated filename
         * @var string
         */
        $fileName = $this->generateFileName($extension);

        // get the path from the database or false if it's not there yet
        /**
         * The path from the database or false if nothing is there.
         * @var string|bool
         */
        $exists = $this->load();

        // Define relative and absolute path helpers
        /**
         * @var string
         */
        $relativeDir = 'assets/images/pfps';

        /**
         * @var string
         */
        $absoluteDir = __DIR__ . '/../../' . $relativeDir;

        // Ensure the directory exists
        if (!is_dir($absoluteDir)){
            return [
                "success" => false,
                "message" => "Fatal server error. Please report this via the bug report form. (Coming soon™)",
                "backendMessage" => "Directory `$absoluteDir` doesn't exist."
            ];
        }

        // if there is nothing in the database
        if ($exists === false){
            /**
             * @var string
             */
            $relativePath = $relativeDir . '/' . $fileName;
            
            /**
             * @var string
             */
            $absolutePath = $absoluteDir . '/' . $fileName;

            // save locally using absolute path
            /**
             * @var bool
             */
            $saveSuccess = $this->savePfpLocally($absolutePath);

            // if we failed to save the image locally
            if (!$saveSuccess){
               return [
                    "success" => false,
                    "message" => "Failed to save image.",
                    "backendMessage" => "Saving the picture locally failed."
               ];
            }

            // save relative path to the db
            /**
             * @var bool
             */
            $dbSaveSuccess = $this->save($relativePath);

            // if we failed to save the data into the database
            if (!$dbSaveSuccess){
                return [
                    "success" => false,
                    "message" => "Failed to save image.",
                    "backendMessage" => "Saving the picture into the database failed."
                ];
            }

            // all good
            return [
                "success" => true,
                "message" => "Profile picture successfully added!",
                "backendMessage" => "Successfully changed the pfp for User with ID: {$this->uid}"
            ];
        }
        else { // something was there already
            /**
             * @var string
             */
            $newFileName = $this->generateFileName($extension);
            if (self::checkForPath($exists)){ // image path was there
                // Get the old relative path from DB and build absolute path
                /**
                 * @var string
                 */
                $oldRelativePath = $this->load();

                /**
                 * @var string
                 */
                $oldAbsolutePath = __DIR__ . '/../../' . $oldRelativePath;

                // if the specified file exists
                if (file_exists($oldAbsolutePath)){
                    if (!unlink($oldAbsolutePath)){ // attempt to delete it
                        return [
                            "success" => false,
                            "message" => "Internal server error.",
                            "backendMessage" => "Failed to delete old profile picture ($oldAbsolutePath)."
                        ];
                    }
                }
                else {
                    Logger::log( // just a warning, no need to crash the flow
                        "Image with path $oldAbsolutePath doesn't exist. Can't delete.",
                        LogLevel::WARNING,
                        LoggerType::NORMAL,
                        Loggers::CMD,
                        __LINE__,
                        __FILE__
                    );
                }

                // get the paths
                /**
                 * @var string
                 */
                $relativePath = $relativeDir . '/' . $newFileName;

                /**
                 * @var string
                 */
                $absolutePath = $absoluteDir . '/' . $newFileName;

                /**
                 * @var bool
                 */
                $saveSuccess = $this->savePfpLocally($absolutePath);

                // if we failed to save locally
                if (!$saveSuccess){
                    return [
                        "success" => false,
                        "message" => "Failed to save image.",
                        "backendMessage" => "Saving the picture locally failed."
                    ];
                }

                /**
                 * @var bool
                 */
                $dbSaveSuccess = $this->save($relativePath);

                // if we failed to save into the database
                if (!$dbSaveSuccess){
                    return [
                        "success" => false,
                        "message" => "Failed to save image.",
                        "backendMessage" => "Saving the picture into the database failed."
                    ];
                }

                // all good
                return [
                    "success" => true,
                    "message" => "Profile picture successfully updated!",
                    "backendMessage" => $relativePath
                ];
            }
            else { // a link was there
                // get the paths
                /**
                 * @var string
                 */
                $relativePath = $relativeDir . '/' . $newFileName;

                /**
                 * @var string
                 */
                $absolutePath = $absoluteDir . '/' . $newFileName;

                /**
                 * @var bool
                 */
                $saveSuccess = $this->savePfpLocally($absolutePath);

                // if we failed to save locally
                if (!$saveSuccess){
                    return [
                        "success" => false,
                        "message" => "Failed to save image.",
                        "backendMessage" => "Saving the picture locally failed."
                    ];
                }

                /**
                 * @var bool
                 */
                $dbSaveSuccess = $this->save($relativePath);

                // if we failed to save into the database
                if (!$dbSaveSuccess){
                    return [
                        "success" => false,
                        "message" => "Failed to save image.",
                        "backendMessage" => "Saving the picture into the database failed."
                    ];
                }

                // all good
                return [
                    "success" => true,
                    "message" => "Profile picture successfully updated!",
                    "backendMessage" => $relativePath
                ];
            }
        }
    }
}