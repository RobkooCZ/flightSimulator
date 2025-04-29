<?php
/**
 * Handles AJAX requests for header link activity logging.
 *
 * Accepts JSON POST requests from header link clicks, logs user activity if logged in,
 * and records the action for auditing purposes.
 *
 * @file headerAjax.php
 * @since 0.7.5
 * @package API
 * @author Robkoo
 * @license TBD
 * @version 0.7.5
 * @see /webDev/assets/js/header.js, /webDev/Auth/User.php, /webDev/Logging/Logger.php
 * @todo ---
 */

// for logging the activity
use WebDev\Auth\User;

// logger
use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Logger;

// start the session to get the data
session_start();

// json
header('Content-Type: application/json');

// if a link was clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    /**
     * @var string Raw HTTP data from the POST request
     */
    $raw = file_get_contents('php://input');

    /**
     * @var mixed Associative array of the decoded data
     */
    $data = json_decode($raw, true);


    if (isset($data['linkClicked']) && $data['linkClicked'] === true){
        // Check if user is logged in before accessing session data
        if (isset($_SESSION[User::SESSION_ID_KEY])){
            Logger::log(
                "User (ID: {$_SESSION[User::SESSION_ID_KEY]}) has performed an action.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            User::load($_SESSION[User::SESSION_ID_KEY])->recordActivity();
        }
    }
}