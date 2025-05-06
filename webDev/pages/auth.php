<?php
/**
 * Auth Backend Script
 *
 * Handles authentication-related actions (login, register, logout) for the flight simulator web application.
 * Validates CSRF tokens, manages user sessions, and redirects with messages as needed.
 *
 * @file auth.php
 * @since 0.3.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.6
 * @see Auth, User, CSRF, AppException, Logger
 * @todo Add more granular error handling and logging
 */

use WebDev\Bootstrap;
Bootstrap::init();

// Database
use WebDev\Database\Database;

// Auth classes
use WebDev\Auth\CSRF;
use WebDev\Auth\Auth;
use WebDev\Auth\User;

// Exceptions
use WebDev\Exception\AuthenticationException;

// Logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\LoggerType;

$db = Database::getInstance(); // database
$csrf = CSRF::getInstance(); // CSRF

// Log entry point
Logger::log("auth.php script started.", LogLevel::INFO, LoggerType::NORMAL);

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

                // one method to update everything necessary in the db at once
                $newUser->updateDbAfterLogin();

                redirect("/", "Login successful!");
            }
            catch (AuthenticationException $ae){ // the user entered wrong password
                $fUser = User::loadUsername($username);

                // increment the failed login count
                $success = $fUser->incrementFailedLogin();
                if ($success === null){ // user has logged in too much
                    // implement a lockout (5 mins)
                    // for now just redirect to homepage
                    redirect('/');
                }

                redirect('/login', "Invalid username or password."); // generic message
            }
            catch (Exception $e){ // everything else
                redirect('/login', "Invalid username or password."); // generic message
            }
            
            break;

        case 'Invalid':
            redirect("/", "No action specified.");

        default:
            redirect("/", "Invalid action.");
    }
}