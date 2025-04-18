<?php
declare(strict_types=1);

// get classes
use WebDev\config\Database;
use WebDev\Functions\CSRF;
use WebDev\Functions\Auth;
use WebDev\Functions\AppException;
use WebDev\Functions\Logger;
use WebDev\Functions\LogLevel;
use WebDev\Functions\LoggerType;

$db = Database::getInstance(); // database
$csrf = CSRF::getInstance(); // CSRF

// Log entry point
Logger::log("auth.php script started.", LogLevel::INFO, LoggerType::NORMAL);

// load the appexception class and all its subclasses
Logger::log("Initializing AppException...", LogLevel::INFO, LoggerType::NORMAL);
AppException::init();
Logger::log("AppException initialized successfully.", LogLevel::SUCCESS, LoggerType::NORMAL);

// global handler for any thrown exceptions
set_exception_handler(function (Throwable $ae) {
    Logger::log("Global exception handler triggered.", LogLevel::WARNING, LoggerType::NORMAL);
    if (AppException::globalHandle($ae)) { // appException or its subclasses
        Logger::log("AppException handled successfully.", LogLevel::SUCCESS, LoggerType::NORMAL);
        exit;
    } else { // anything but appException and its subclasses
        Logger::log("Non-AppException: " . $ae->getMessage(), LogLevel::FAILURE, LoggerType::NORMAL);
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
    Logger::log("Redirecting to: " . $url . ($message ? " with message: " . $message : ""), LogLevel::INFO, LoggerType::NORMAL);
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        Logger::log("Session message set: " . $message, LogLevel::DEBUG, LoggerType::NORMAL);
    }
    header('Location: ' . $url);
    exit;
}

// special case for logout as it's in the navbar
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    Logger::log("Logout action detected.", LogLevel::INFO, LoggerType::NORMAL);
    $receivedToken = $_GET['csrf_token'] ?? '';
    Logger::log("Received CSRF token for logout: " . ($receivedToken ? 'yes' : 'no'), LogLevel::DEBUG, LoggerType::NORMAL);

    if (!$csrf->validateToken($receivedToken)) {
        Logger::log("CSRF validation failed for logout.", LogLevel::FAILURE, LoggerType::NORMAL);
        redirect("/", "Invalid CSRF token.");
    }
    Logger::log("CSRF validation passed for logout.", LogLevel::SUCCESS, LoggerType::NORMAL);

    Auth::getInstance()->logout();
    Logger::log("User logged out successfully.", LogLevel::SUCCESS, LoggerType::NORMAL);

    redirect("/", "You have been logged out.");
}

// Handle POST requests for actions like login and register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Logger::log("POST request detected.", LogLevel::INFO, LoggerType::NORMAL);
    $action = $_GET['action'] ?? '';
    Logger::log("Action received: " . ($action ?: 'none'), LogLevel::DEBUG, LoggerType::NORMAL);

    $receivedToken = $_POST['csrf_token'] ?? '';
    Logger::log("Received CSRF token: " . ($receivedToken ? 'yes' : 'no'), LogLevel::DEBUG, LoggerType::NORMAL);

    if (!$csrf->validateToken($receivedToken)) {
        Logger::log("CSRF validation failed for action: " . $action, LogLevel::FAILURE, LoggerType::NORMAL);
        redirect("/" . $action, "Session expired. Please try again.");
    }
    Logger::log("CSRF validation passed for action: " . $action, LogLevel::SUCCESS, LoggerType::NORMAL);

    switch ($action) {
        case 'register':
            Logger::log("Register action processing started.", LogLevel::INFO, LoggerType::NORMAL);
            $username = trim($_POST['username'] ?? '');
            Logger::log("Username received: " . $username, LogLevel::DEBUG, LoggerType::NORMAL);

            try {
                Auth::validateUser($username);
                Logger::log("Username validated successfully.", LogLevel::SUCCESS, LoggerType::NORMAL);
            } catch (Exception $e) {
                Logger::log("Invalid username: " . $e->getMessage(), LogLevel::FAILURE, LoggerType::NORMAL);
                redirect('/register', "Invalid characters in username.");
            }

            $password = $_POST['password'] ?? '';
            $passwordRepeat = $_POST['passwordRepeat'] ?? '';
            if ($password !== $passwordRepeat) {
                Logger::log("Passwords do not match.", LogLevel::FAILURE, LoggerType::NORMAL);
                redirect('/register', "Passwords do not match!");
            }
            Logger::log("Passwords match.", LogLevel::SUCCESS, LoggerType::NORMAL);

            try {
                Auth::validatePass($password);
                Logger::log("Password validated successfully.", LogLevel::SUCCESS, LoggerType::NORMAL);
            } catch (Exception $e) {
                Logger::log("Invalid password: " . $e->getMessage(), LogLevel::FAILURE, LoggerType::NORMAL);
                redirect('/register', $e->getMessage());
            }

            $result = $db->query(
                "SELECT id FROM users WHERE username = :username",
                ["username" => $username]
            );
            if (!empty($result)) {
                Logger::log("Username already exists: " . $username, LogLevel::FAILURE, LoggerType::NORMAL);
                redirect('/register', "Username already exists!");
            }
            Logger::log("Username does not exist.", LogLevel::SUCCESS, LoggerType::NORMAL);

            try {
                Auth::getInstance()->register($username, $password);
                Logger::log("User registered successfully: " . $username, LogLevel::SUCCESS, LoggerType::NORMAL);
                redirect('/login', "Registration successful! Please log in.");
            } catch (Exception $e) {
                Logger::log("Registration failed: " . $e->getMessage(), LogLevel::FAILURE, LoggerType::NORMAL);
                redirect('/register', "Error! Failed to register.");
            }

            break;

        case 'login':
            Logger::log("Login action processing started.", LogLevel::INFO, LoggerType::NORMAL);
            $username = $_POST['username'] ?? '';
            Logger::log("Username received: " . $username, LogLevel::DEBUG, LoggerType::NORMAL);

            try {
                Auth::validateUser($username);
                Logger::log("Username validated successfully.", LogLevel::SUCCESS, LoggerType::NORMAL);
            } catch (Exception $e) {
                Logger::log("Invalid username: " . $e->getMessage(), LogLevel::FAILURE, LoggerType::NORMAL);
                redirect('/login', "Invalid username or password.");
            }

            $password = $_POST['password'] ?? '';
            Logger::log("Password received.", LogLevel::DEBUG, LoggerType::NORMAL);

            try {
                $infoArr = Auth::getInstance()->login($username, $password);

                $_SESSION['id'] = $infoArr['id'];
                $_SESSION['username'] = $infoArr['username'];
                Logger::log("User logged in successfully: " . $username, LogLevel::SUCCESS, LoggerType::NORMAL);

                redirect("/", "Login successful!");
            } catch (Exception $e) {
                Logger::log("Login failed: " . $e->getMessage(), LogLevel::FAILURE, LoggerType::NORMAL);
                redirect('/login', "Invalid username or password.");
            }

            break;

        case 'Invalid':
            Logger::log("No action received.", LogLevel::FAILURE, LoggerType::NORMAL);
            redirect("/", "No action specified.");

        default:
            Logger::log("Invalid action received: " . $action, LogLevel::FAILURE, LoggerType::NORMAL);
            redirect("/", "Invalid action.");
    }
}