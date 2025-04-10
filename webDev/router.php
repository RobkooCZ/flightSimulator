<?php

require_once __DIR__ . '/vendor/autoload.php';

// function to serve static files
function serveStaticFile($filePath): void{ // no return
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

        readfile($filePath); // read the file
        exit;
    } 
    else{ // file not found
        http_response_code(404);
        echo "File ($filePath) not found.";
        exit;
    }
}

// function to handle the routing
function handleRequest($uri): void{
    switch ($uri){
        // Serve the homepage (index.php)
        case '/':
            include __DIR__ . '/public/index.php';
            break;

        // login, register, logout forms
        case '/login':
            include __DIR__ . '/php/login.php';
            break;
        case '/register':
            include __DIR__ . '/php/register.php';
            break;
        case '/logout':
            include __DIR__ . '/php/logout.php';
            break;
        
        // admin
        case '/admin':
            include __DIR__ . '/public/adminPage.php';
            break;

        // school admin page to meet criteria for the final project
        case '/adminSchool':
            include __DIR__ . '/public/adminSchoolPage.php';
            break;

        // scripts
        case '/actionScript':
            include __DIR__ . '/php/Functions/actionScript.php';
            break;

        // auth (register, login, and logout functionality)
        case '/auth':
            include __DIR__ . '/php/auth.php';
            break;
        
        // static files
        case (preg_match('/^\/assets\/(css)\/(.+)$/', $uri, $matches) ? true : false): // expression to match assets/css
            // match assets/css
            $fileType = $matches[1]; // css     
            $fileName = $matches[2]; // fileName

            // Construct the file path
            $filePath = __DIR__ . '/assets/' . $fileType . '/' . $fileName;

            // serve the file
            serveStaticFile($filePath);
            break;
        default: // handle 404 not found
            http_response_code(404);
            echo "404 file not found";
            break;
    }
}

// get the current request uri
$requestURI = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// handle incoming request
handleRequest($requestURI);