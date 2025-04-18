<?php

require_once __DIR__ . '/vendor/autoload.php';

use WebDev\AppBootstrapper;
use WebDev\Bootstrap;
// global exception handler
use WebDev\Functions\AppException;
use WebDev\Functions\Logger;
use WebDev\Functions\LogLevel;
use WebDev\Functions\LoggerType;
use WebDev\Functions\Loggers;

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
function serveStaticFile($filePath): void { // no return
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
            // other static files in the future (can be images, js, ...)
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
            include __DIR__ . '/php/login.php';
            break;
        case '/register':
            Logger::log(
                "Routing to register page.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/php/register.php';
            break;
        case '/logout':
            Logger::log(
                "Routing to logout page.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/php/logout.php';
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
            include __DIR__ . '/php/Functions/actionScript.php';
            break;

        // auth (register, login, and logout functionality)
        case '/auth':
            Logger::log(
                "Routing to auth functionality.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            include __DIR__ . '/php/auth.php';
            break;
        
        // static files
        case (preg_match('/^\/assets\/(css)\/(.+)$/', $uri, $matches) ? true : false): // expression to match assets/css
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