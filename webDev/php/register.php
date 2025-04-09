<?php
// Start session and set a variable to not start it in header.php
session_start();
$startSession = false;

// Title for the site
$title = 'Register';

// Don't show the header and footer
$showHeader = false;
$showFooter = false;

// Include header and the stylesheet for the current page
$stylesheet = 'registerPage';
include __DIR__ . '/../php/includes/header.php';

use WebDev\config\Database;
use WebDev\Functions\CSRF;

$db = Database::getInstance();

// Get the database connection (include db.php too)
$message = ""; // Empty string

// If the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // If the submit button was clicked
    if (isset($_POST['submit'])) { // Button is clicked, register
        // validate CSRF token
        $csrf = CSRF::getInstance();

        // get the token from the form
        $formToken = $_POST['csrf_token'] ?? ''; // either get the token or null

        if (!$csrf->validateToken($formToken)){
            // not valid, do NOT proceed
            error_log("CSRF validation failed.");

            // Redirect back to the register page with an error message
            $_SESSION['message'] = "Session expired. Please try again.";
            header('Location: /register');
            exit; // stop continuing script
        }

        // Sanitize user input
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $passwordRepeat = $_POST['passwordRepeat'];

        // Check if username already exists in the database
        $result = $db->query("SELECT id, username, password, salt FROM users WHERE username = :username", ["username" => $username]);

        if (!empty($result)) { // username already exists in database
            $_SESSION['message'] = "Username already exists!";
        } 
        else { // Username is unique, check password next
            // Check if the passwords match
            if ($password !== $passwordRepeat) {
                $_SESSION['message'] = "Passwords do not match!";
            } 
            else { // Passwords match, insert the user into the database
                try {
                    // Generate a salt
                    $salt = bin2hex(random_bytes(16)); // 128-bit random salt (32 bytes)

                    // Combine the password with the salt and hash it
                    $combinedPassword = $password . $salt; // Combine the password with the salt
                    $hashedPassword = password_hash($combinedPassword, PASSWORD_BCRYPT); // Hash the password

                    // Check if the password was hashed successfully
                    if (!$hashedPassword) { // If the password fails to hash, throw an exception
                        throw new Exception("Failed to hash password.");
                    }

                    // make a parameters array
                    $parameters = [
                        "username" => $username,
                        "password" => $hashedPassword,
                        "salt" => $salt,
                    ];

                    // execute the query using safe execute method
                    if (!$db->execute("INSERT INTO users (username, password, salt, lastActivityAt, createdAt, updatedAt) VALUES (:username, :password, :salt, NOW(), NOW(), NOW())", $parameters)){
                        throw new Exception("Failed to execute INSERT INTO.");
                    }
                
                    // Redirect to the login page after successful registration
                    header('Location: /login');
                    exit();
                } 
                catch (Exception $e) {
                    // Handle errors
                    error_log($e->getMessage());
                    $_SESSION['message'] = "Error! Failed to register!";
                }
            }
        }

        // Redirect to the same page to clear form data
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Display the message if it exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!-- The register modal -->
<div class="registerModal">
    <h2>REGISTER</h2>
    <form method="post">
        <!-- include CSRF -->
        <?= CSRF::getInstance()->getCSRFField(); ?>

        <input name="username" type="text" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <input name="passwordRepeat" type="password" placeholder="Repeat Password" required>    

        <p><?= htmlspecialchars($message); ?></p>

        <input type="submit" name="submit" value="Register">
        <label for="register">Already have an account? <a href="/login">Login</a></label>
    </form>
</div>

<?php // Include footer
include __DIR__ . '/../php/includes/footer.php';
?>