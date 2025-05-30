<?php
/**
 * Handler for profile picture links.
 *
 * @file LinkHandler.php
 * @since 0.7.7
 * @package Utilities
 * @author Robkoo
 * @license TBD
 * @version 0.7.7
 */

declare(strict_types=1);

namespace WebDev\Utilities;

use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Logger;

/**
 * LinkHandler class.
 * 
 * Handles link operations for user profile pictures. Extends the `Handler` class and implements its abstract methods. Contains link specific validation and other methods specific to this class.
 *
 * @package Utilities
 * @since 0.7.7
 * @see WebDev\Utilities\Handler
 */
final class LinkHandler extends Handler {
    /**
     * An array of allowed extensions.
     * @var array<int,string>
     */
    public const ALLOWED_EXTENSIONS = ['png', 'jpeg', 'jpg', 'webp'];

    /**
     * Validate the provided link.
     *
     * @param array|string $resource The link to validate
     * @return array<string,bool|string> An associative array containing a success flag, and messages for the user and the backend.
     */
    final public function validate(array|string $resource): array {
        // check if we have a valid url
        if (!filter_var($resource, FILTER_VALIDATE_URL)){
            return [
                "success" => false,
                "message" => "Invalid link provided. Please try a different one.",
                "backendMessage" => "User provided link didn't pass filter_var validation."
            ];
        }

        // validate the file extension
        /**
         * @var array<string,int|string>|int|string|false|null
         */
        $parsedUrl = parse_url($resource);

        // check if it contains a path
        if (!isset($parsedUrl['path'])){
            return [
                "success" => false,
                "message" => "Invalid link provided. Please try a different one.",
                "backendMessage" => "User provided link doesn't contain a path."
            ];
        }

        // check if the image is the correct format
        /**
         * @var string
         */
        $extension = strtolower(pathinfo($parsedUrl['path'], PATHINFO_EXTENSION));

        // incorrect format
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)){
            return [
                "success" => false,
                "message" => "Link with unsupported file extension provided. Please try a different one.",
                "backendMessage" => "User provided link with an invalid extension. ($extension)."
            ];
        }

        // check if its reachable by fetching the headers
        /**
         * @var array<int|string,string>|false $headers
         */
        $headers = @get_headers($resource, true);

        // not reachable
        if ($headers === false){
            return [
                "success" => false,
                "message" => "Invalid link provided. Please try a different one.",
                "backendMessage" => "User provided link is unreachable."
            ];
        }

        // check if it contains the content type
        if (!isset($headers['Content-Type'])){
            return [
                "success" => false,
                "message" => "Provided link is invalid. Please provide a valid image link.",
                "backendMessage" => "User provided link doesn't have Content-Type."
            ];
        }

        // check if its content type is an image
        if (!str_starts_with($headers['Content-Type'], 'image/')){
            return [
            "success" => false,
            "message" => "Provided link is not an image. Please provide a valid image link.",
            "backendMessage" => "User provided link's Content-Type isn't an image. ({$headers['Content-Type']})"
            ];
        }

        // all good
        return [
            "success" => true,
            "message" => null,
            "backendMessage" => null
        ];
    }

    /**
     * 
     */
    final public function upload(array|string $resource): array {
        // validate the provided link
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

        // get the database profilePicture field to determine whether we have to delete a local file
        /**
         * @var string|false
         */
        $exists = $this->load();

        if ($exists === false){ // nothing in the database
            // save to db
            /**
             * @var bool
             */
            $dbSaveSuccess = $this->save($resource);

            // if we failed to save the link into the database
            if (!$dbSaveSuccess){
                return [
                    "success" => false,
                    "message" => "Failed to save link.",
                    "backendMessage" => "Saving the link into the database failed."
                ];
            }

            // all good
            return [
                "success" => true,
                "message" => "Profile picture successfully added!",
                "backendMessage" => $resource
            ];
        }
        else { // something is there
            // check whether the database contents are an image (path)
            if (FileHandler::checkForPath($exists)){ // it is an image (path)
                // get the old image paths
                /**
                 * @var string
                 */
                $oldRelativePath = $exists;
                /**
                 * @var string
                 */
                $oldAbsolutePath = __DIR__ . '/../../' . $oldRelativePath;

                // check if the file exists
                if (file_exists($oldAbsolutePath)){
                    if (!unlink($oldAbsolutePath)){ // attempt to delete the image
                        return [
                            "success" => false,
                            "message" => "Internal server error.",
                            "backendMessage" => "Failed to delete old profile picture ($oldAbsolutePath)."
                        ];
                    }
                }
                else {
                    Logger::log( // log a warning, not necessary to crash the flow
                        "Image with path $oldAbsolutePath doesn't exist. Can't delete.",
                        LogLevel::WARNING,
                        LoggerType::NORMAL,
                        Loggers::CMD,
                        __LINE__,
                        __FILE__
                    );
                }

                // save the link into the database
                /**
                 * @var bool
                 */
                $dbSaveSuccess = $this->save($resource);

                // check if it succeeded
                if (!$dbSaveSuccess){
                    return [
                        "success" => false,
                        "message" => "Failed to save link.",
                        "backendMessage" => "Saving the link into the database failed."
                    ];
                }

                // all good
                return [
                    "success" => true,
                    "message" => "Profile picture successfully updated!",
                    "backendMessage" => $resource
                ];
            }
            else { // link is there
                // attempt to save the new link there
                /**
                 * @var bool
                 */
                $dbSaveSuccess = $this->save($resource);

                // check if it failed
                if (!$dbSaveSuccess){
                    return [
                        "success" => false,
                        "message" => "Failed to save link.",
                        "backendMessage" => "Saving the link into the database failed."
                    ];
                }

                // all good
                return [
                    "success" => true,
                    "message" => "Profile picture successfully updated!",
                    "backendMessage" => $resource
                ];
            }
        }
    }
}