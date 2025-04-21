<?php
declare(strict_types=1);

// Database
use WebDev\Database\Database;

// Auth classes
use WebDev\Auth\CSRF;
use WebDev\Auth\Auth;
use WebDev\Auth\User;
// Exception handling
use WebDev\Exception\AppException;

// Logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\LoggerType;

$db = Database::getInstance(); // database
$csrf = CSRF::getInstance(); // CSRF

// Log entry point
Logger::log("auth.php script started.", LogLevel::INFO, LoggerType::NORMAL);

// load the appexception class and all its subclasses
AppException::init();

// global handler for any thrown exceptions
set_exception_handler(function (Throwable $ae){
    if (AppException::globalHandle($ae)){ // appException or its subclasses
        exit;
    } 
});

/**
 * Redirects the user to a specified URL with an optional message.
 * 
 * This function sets a session message (if provided) and redirects the user
 * to the specified URL. It then terminates the script execution.
 * 
 * @param string $url The URL to redirect to. Must be added in `router.php`.
 * @param ?string $message An optional message to display after redirection.
 * @return never This function does not return; it terminates the script.
 */
function redirect(string $url, ?string $message = ''): never {
    if (!empty($message)){
        $_SESSION['message'] = $message;
    }
    header('Location: ' . $url);
    exit;
}

// special case for logout as it's in the navbar
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout'){
    $receivedToken = $_GET['csrf_token'] ?? '';

    if (!$csrf->validateToken($receivedToken)){
        redirect("/", "Invalid CSRF token.");
    }

    Auth::getInstance()->logout();
    redirect("/", "You have been logged out.");
}

// Handle POST requests for actions like login and register
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_GET['action'] ?? '';
    $receivedToken = $_POST['csrf_token'] ?? '';

    if (!$csrf->validateToken($receivedToken)){
        redirect("/" . $action, "Session expired. Please try again.");
    }

    switch ($action){
        case 'register':
            $username = trim($_POST['username'] ?? '');

            try {
                Auth::validateUser($username);
            }
            catch (Exception $e){
                redirect('/register', "Invalid characters in username.");
            }

            $password = $_POST['password'] ?? '';
            $passwordRepeat = $_POST['passwordRepeat'] ?? '';
            if ($password !== $passwordRepeat){
                redirect('/register', "Passwords do not match!");
            }

            try {
                Auth::validatePass($password);
            }
            catch (Exception $e){
                redirect('/register', $e->getMessage());
            }

            $result = $db->query(
                "SELECT id FROM users WHERE username = :username",
                ["username" => $username]
            );
            if (!empty($result)){
                redirect('/register', "Username already exists!");
            }

            try {
                Auth::getInstance()->register($username, $password);
                redirect('/login', "Registration successful! Please log in.");
            }
            catch (Exception $e){
                redirect('/register', "Error! Failed to register.");
            }

            break;

        case 'login':
            $username = $_POST['username'] ?? '';

            try {
                Auth::validateUser($username);
            }
            catch (Exception $e){
                redirect('/login', "Invalid username or password.");
            }

            $password = $_POST['password'] ?? '';

            try {
                // login the user and get the user object
                $newUser = Auth::getInstance()->login($username, $password);

                // set session data
                $newUser->storeInSession();

                // login counts as an activity
                $newUser->recordActivity();

                redirect("/", "Login successful!");
            }
            catch (Exception $e){
                redirect('/login', "Invalid username or password.");
            }

            break;

        case 'Invalid':
            redirect("/", "No action specified.");

        default:
            redirect("/", "Invalid action.");
    }
}