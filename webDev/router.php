<?php
/**
 * Application Router
 *
 * Handles all HTTP routing for the flight simulator web application.
 * Maps URIs to controllers, serves static files, and initializes core systems.
 *
 * @file router.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see Bootstrap, AppException, Logger
 * @todo Add dynamic route support, improve error handling, and static file types
 */

require_once __DIR__ . '/vendor/autoload.php';

use WebDev\Bootstrap;

// Exception handler
use WebDev\Exception\AppException;

// Logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\Loggers;

// make sure AppException and all its subclasses are loaded
AppException::init();

set_exception_handler(function (Throwable $ae){
    if (AppException::globalHandle($ae)){ // appException or its subclasses
        exit;
    }
    else { // anything but appException and its subclasses
        error_log($ae->getMessage()); // temporary
    }
});

// init the app stuff
Bootstrap::init();

// function to serve static files
function serveStaticFile($filePath): never {
    Logger::log(
        "Attempting to serve static file: $filePath",
        LogLevel::INFO,
        LoggerType::NORMAL,
        Loggers::CMD
    );

    if (file_exists($filePath)){ // check if the file exists
        $ext = pathinfo($filePath, PATHINFO_EXTENSION); // get the extension of static file

        switch ($ext){ // set the proper content-type header
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'json':
                header('Content-Type: application/json');
                break;
            // other static files in the future
            default:
                header('Content-Type: text/plain'); // plain text if the file extension is unrecognized
                break;
        }

        Logger::log(
            "Static file served successfully: $filePath",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        readfile($filePath); // read the file
        exit;
    } 
    else { // file not found
        Logger::log(
            "Static file not found: $filePath",
            LogLevel::FAILURE,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        http_response_code(404);
        echo "File ($filePath) not found.";
        exit;
    }
}

// function to handle the routing
function handleRequest($uri): void {
    Logger::log(
        "Handling request for URI: $uri",
        LogLevel::INFO,
        LoggerType::NORMAL,
        Loggers::CMD
    );

    switch ($uri){
        // Serve the homepage (index.php)
        case '/':
            Logger::log(
                "Routing to homepage.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/public/index.php';
            break;

        // login, register, logout forms
        case '/login':
            Logger::log(
                "Routing to login page.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/pages/login.php';  // UPDATED: php → pages
            break;
        case '/register':
            Logger::log(
                "Routing to register page.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/pages/register.php';  // UPDATED: php → pages
            break;
        case '/logout':
            Logger::log(
                "Routing to logout page.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/pages/logout.php';  // UPDATED: php → pages
            break;
        
        // admin
        case '/admin':
            Logger::log(
                "Routing to admin page.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/public/adminPage.php';
            break;

        // school admin page to meet criteria for the final project
        case '/adminSchool':
            Logger::log(
                "Routing to admin school page.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/public/adminSchoolPage.php';
            break;

        // scripts
        case '/actionScript':
            Logger::log(
                "Routing to action script.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/pages/actionScript.php';  // UPDATED: php/Functions → pages
            break;

        // auth (register, login, and logout functionality)
        case '/auth':
            Logger::log(
                "Routing to auth functionality.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/pages/auth.php';  // UPDATED: php → pages
            break;

        case '/api/adminSchoolAjax.php':
            Logger::log(
                "Routing to adminSchoolAjax.php.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/api/adminSchoolAjax.php';
            break;
        
        // static files
        case (preg_match('/^\/assets\/([a-zA-Z0-9]+)\/(.+)$/', $uri, $matches) ? true : false): // expression to match assets/css
            // match assets/css
            $fileType = $matches[1]; // css     
            $fileName = $matches[2]; // fileName

            // Construct the file path
            $filePath = __DIR__ . '/assets/' . $fileType . '/' . $fileName;

            Logger::log(
                "Routing to static file: $filePath",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );

            // serve the file
            serveStaticFile($filePath);
            break;
        default: // handle 404 not found
            Logger::log(
                "404 Not Found for URI: $uri",
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            http_response_code(404);
            echo "404 file not found";
            break;
    }
}

// get the current request uri
$requestURI = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

Logger::log(
    "Received request URI: $requestURI",
    LogLevel::DEBUG,
    LoggerType::NORMAL,
    Loggers::CMD
);

// handle incoming request
handleRequest($requestURI);