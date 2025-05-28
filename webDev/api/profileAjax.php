<?php
/**
 * Profile Picture Upload Endpoint
 *
 * Handles AJAX requests for uploading and updating user profile pictures.
 * Validates the uploaded image, saves it to disk, updates the database, and returns a JSON response.
 *
 * @file profileAjax.php
 * @since 0.7.7
 * @package API
 * @author Robkoo
 * @license TBD
 * @version 0.7.7
 * @see WebDev\Utilities\FileHandler, WebDev\API\ApiResponse
 */
declare(strict_types=1);

// start the session
session_start();

// set the correct json content type
header('Content-Type: application/json');

// init bootstrap
use WebDev\Bootstrap;
Bootstrap::init();

// standardised api response
use WebDev\API\ApiResponse;
use WebDev\Auth\Auth;
// checking if a user with a specific id exists
use WebDev\Auth\User;
use WebDev\Exception\ValidationException;
// logging
use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Logger;

// handlers
use WebDev\Utilities\FileHandler;
use WebDev\Utilities\LinkHandler;

// no action received, send back a failure
if (!isset($_GET['action'])){
    ApiResponse::failure(
        "Internal server error. Please try again.",
        400,
        "No action GET parameter received."
    );
}

// get the user id from the session
$uid = $_SESSION[User::SESSION_ID_KEY] ?? null;

// check if the managed to get the uid
if (is_null($uid)){
    Logger::log(
        "Failed to extract User ID from the session.",
        LogLevel::WARNING,
        LoggerType::NORMAL,
        Loggers::CMD,
        __LINE__,
        __FILE__
    );

    ApiResponse::failure(
        "Internal server error.",
        400,
        "Failed to get User ID from session."
    );
}

// get the action
/**
 * @var string
 */
$action = $_GET['action'];

// type received
if ($action == "nav"){
    // no type received, send back a failure
    if (!isset($_GET['type'])){
        ApiResponse::failure(
            "Internal server error. Please try again.",
            400,
            "No type GET parameter received."
        );
    }

    /**
     * @var string
     */
    $type = $_GET['type'];

    if ($type === "file"){ // file received
        // first validate that we have gotten an image
        if (!isset($_FILES['img'])){ // no image found
            Logger::log(
                "No image was uploaded in the request.",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD,
                __LINE__,
                __FILE__
            );
            ApiResponse::failure(
                "No image uploaded.",
                400,
                "The 'img' key was missing from $_FILES."
            );
        }

        /**
         * @var string
         */
        $extension = strtolower(pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION));

        // get the instance
        /**
         * @var FileHandler
         */
        $fileHandler = new FileHandler($uid);

        // attempt to upload the image
        /**
         * @var array<string,string|bool>
         */
        $uploadSuccess = $fileHandler->upload($_FILES['img']);

        // failed to upload
        if ($uploadSuccess['success'] === false){
            Logger::log( // log the failure
                $uploadSuccess['backendMessage'],
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );

            ApiResponse::failure(
                $uploadSuccess['message'],
                400,
                $uploadSuccess['backendMessage']
            );
        }
        else { // all went well
            ApiResponse::success(
                $uploadSuccess['backendMessage'],
                $uploadSuccess['message']
            );
        }
    }
    elseif ($type === "link"){ // link received
        // get the link
        /**
         * @var string
         */
        $link = $_POST['link'];
        
        // get the instance of linkHandker
        /**
         * @var LinkHandler
         */
        $linkHandler = new LinkHandler($uid);

        // attempt to upload the link
        /**
         * @var array<string,string|bool>
         */
        $uploadSuccess = $linkHandler->upload($link);

        // failed to upload
        if ($uploadSuccess['success'] === false){
            Logger::log( // log the failure
                $uploadSuccess['backendMessage'],
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );

            ApiResponse::failure(
                $uploadSuccess['message'],
                400,
                $uploadSuccess['backendMessage']
            );
        }
        else { // all went well
            ApiResponse::success(
                $uploadSuccess['backendMessage'],
                $uploadSuccess['message']
            );
        }
    }
}
elseif ($action == "update"){ // Profile data change
    /**
     * @var array<string,int|string>
     */
    $changeData = json_decode($_POST['changeData'], true);

    /**
     * @var int
     */
    $dataCount = (int)$_POST['dataCount'];

    /**
     * True if both things are to be changed, false otherwise.
     * @var bool
     */
    $both = ($dataCount === 2);

    fwrite(STDOUT, sprintf("\nDatacount: %d, both: %d\n", $dataCount, $both));

    // check for correct dataCount
    if ($dataCount < 1){
        ApiResponse::failure(
            "Internal server error. Please try again.",
            400,
            "DataCount is lower than 1. Value: $dataCount"
        );
    }
    elseif ($dataCount > 2){
        ApiResponse::failure(
            "Internal server error. Please try again.",
            400,
            "DataCount is higher than 2. Value: $dataCount"
        );
    }

    // loop through the array and change the profile data
    foreach ($changeData as $item){
        /**
         * The type of the change.
         * @var string
         */
        $type = $item['type'];

        /**
         * The value of the change.
         * @var string|int
         */
        $value = $item['data'];

        // based on the type do different things
        switch ($type){
            case "username": // user wants to change username
                // validate the provided username
                try {
                    Auth::validateUser($value);
                }
                catch (ValidationException $ve){
                    $reason = $ve->getMessage();
                    ApiResponse::failure(
                        "$reason",
                        400,
                        "User provided invalid username: $reason"
                    );
                }

                // valid username, check for uniqueness
                if(User::existsUsername($value)){
                    ApiResponse::failure(
                        "Username already exists. Please choose a different one.",
                        400,
                        "Username already exists in the database."
                    );
                }

                // valid, unique username, change the users username
                $user = User::loadUsername($_SESSION[User::SESSION_USERNAME_KEY]);

                // attempt to set the username
                if ($user->setUsername($value)){ // successfully set
                    // set it to the session
                    $_SESSION[User::SESSION_USERNAME_KEY] = $value;

                    // update lastActivityAt
                    $user->recordActivity();

                    // send the success response to the frontend unless its not the only value to be changed
                    if (!$both){
                        ApiResponse::success(
                            "User successfully changed their username.",
                            "Successfully changed username!"
                        );
                    }
                }
                else { // unsuccessfully set
                    // send a failure response to the frontend
                    ApiResponse::failure(
                        "Failed to change username. Please try again.",
                        400,
                        "setUsername() method of User class failed to change username."
                    );
                }
                // here, break is not necessary, IF both are NOT set, as methods of `ApiResponse` never return; they terminate execution.
                if ($both) break;
            case "bio": // user wants to change bio
                // validate the provided bio
                try {
                    Auth::validateBio($value);
                }
                catch (ValidationException $ve){
                    $reason = $ve->getMessage();
                    ApiResponse::failure(
                        "$reason",
                        400,
                        "User provided invalid bio: $reason"
                    );
                }

                // valid bio, change the user's bio
                $user = User::loadUsername($_SESSION[User::SESSION_USERNAME_KEY]);

                // attempt to set the username
                if ($user->setBio($value)){ // successfully set
                    // update lastActivityAt
                    $user->recordActivity();

                    // send the success response to the frontend unless it's not the only one to be changed
                    if (!$both){
                        ApiResponse::success(
                            "User successfully changed their bio.",
                            "Successfully changed bio!"
                        );
                    }
                }
                else { // unsuccessfully set
                    // send a failure response to the frontend
                    ApiResponse::failure(
                        "Failed to change bio. Please try again.",
                        400,
                        "setBio() method of User class failed to change bio."
                    );
                }

                // here, break is not necessary, IF both are NOT set, as methods of `ApiResponse` never return; they terminate execution.
                if ($both) break;
            default: // wrong parameter passed
                ApiResponse::failure(
                    "Internal server error. Please try again.",
                    400,
                    "Wrong type POST parameter received."
                );
                break;
        }
    }

    if ($both){
        ApiResponse::success(
            "User successfully changed both their username and bio.",
            "Successfully changed username and bio!"
        );
    }
}
else { // wrong action received, send back a failure
    ApiResponse::failure(
        "Internal server error. Please try again.",
        400,
        "Wrong action GET parameters received."
    );
}

// if the execution reaches the end of the file
ApiResponse::failure(
    "Internal server error. Please try again.",
    400,
    "Execution reached the end of the file."
);