<?php
// get classes
use WebDev\config\Database;
use WebDev\Functions\CSRF;
use WebDev\Functions\Auth;

$db = Database::getInstance(); // database
$csrf = CSRF::getInstance(); // CSRF

/**
 * Redirects the user to a specified URL with an optional message.
 * 
 * This function sets a session message (if provided) and redirects the user
 * to the specified URL. It then terminates the script execution.
 * 
 * **Important:** The URL must be defined in the `router.php` file located in the root directory of this project.
 * If the URL (e.g., `/login`) is not added as a case in the main switch statement of `router.php`, the redirection will fail.
 * 
 * ### Example usage:
 * #### With a message:
 * ```php
 * redirect(url: '/login', message: 'Please log in to continue.');
 * ```
 * 
 * #### Without a message:
 * ```php
 * redirect(url: '/');
 * ```
 * 
 * @param string $url The URL to redirect to. Must be added in `router.php`.
 * @param ?string $message An optional message to display after redirection.
 * @return never This function does not return; it terminates the script.
 */
function redirect(string $url, ?string $message = ''): never {
    if (!empty($message)){
        $_SESSION['message'] = $message;
    }
    header ('Location: ' . $url);
    exit;
}

// special case for logout as it's in the navbar
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'logout'){
    // Get the CSRF token
    $receivedToken = $_GET['csrf_token'] ?? '';

    // Validate the received CSRF token
    if (!$csrf->validateToken($receivedToken)){
        error_log("CSRF validation failed for logout.");
        redirect("/", "Invalid CSRF token.");
    }

    // Perform logout
    Auth::getInstance()->logout();

    // Redirect to the main page
    redirect("/", "You have been logged out.");
}

// Handle POST requests for actions like login and register
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Get the action from the URL
    $action = $_GET['action'] ?? '';

    // Get the CSRF token from the POST request
    $receivedToken = $_POST['csrf_token'] ?? '';

    // Validate the received CSRF token
    if (!$csrf->validateToken($receivedToken)){
        error_log("CSRF validation failed.");
        redirect("/" . $action, "Session expired. Please try again.");
    }

    // Handle the action
    switch ($action){
        case 'register':
            // Sanitize and validate the username
            $username = trim($_POST['username'] ?? '');
            
            try {
                Auth::validateUser($username);
            } 
            catch (Exception $e){
                redirect('/register', "Invalid characters in username.");
            }

            // Get and validate the passwords
            $password = $_POST['password'] ?? '';
            $passwordRepeat = $_POST['passwordRepeat'] ?? '';
            if ($password !== $passwordRepeat){
                redirect('/register', "Passwords do not match!");
            }

            // validate the password
            try {
                Auth::validatePass($password);
            } 
            catch (Exception $e){
                redirect('/register', $e->getMessage());
            }

            // Check if the username already exists
            $result = $db->query(
                "SELECT id FROM users WHERE username = :username",
                ["username" => $username]
            );
            if (!empty($result)){
                redirect('/register', "Username already exists!");
            }

            // Register the user
            try {
                Auth::getInstance()->register($username, $password);
                redirect('/login', "Registration successful! Please log in.");
            } 
            catch (Exception $e){
                error_log("Registration failed: " . $e->getMessage());
                redirect('/register', "Error! Failed to register.");
            }

            break;

        case 'login':
            // Get and validate the username
            $username = $_POST['username'] ?? '';
            try {
                Auth::validateUser($username);
            } 
            catch (Exception $e){
                redirect('/login', "Invalid username.");
            }

            // Get the password
            $password = $_POST['password'] ?? '';

            // Attempt to log in the user
            try {
                $infoArr = Auth::getInstance()->login($username, $password);

                // Set session data
                $_SESSION['id'] = $infoArr['id'];
                $_SESSION['username'] = $infoArr['username'];

                // Redirect to the homepage
                redirect("/", "Login successful!");
            } 
            catch (Exception $e){
                error_log("Login failed: " . $e->getMessage());
                redirect('/login', "Login failed: " . $e->getMessage());
            }

            break;

        // No action was received
        case 'Invalid':
            error_log("No action received in auth.php.");
            redirect("/", "No action specified.");
            
        // Invalid action
        default:
            error_log("Invalid action in auth.php.");
            redirect("/", "Invalid action.");
    }
}