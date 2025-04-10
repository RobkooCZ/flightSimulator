<?php
// start session and set a variable to not start it in header.php
session_start();
$startSession = false;

// title for the site
$title = 'Login';

// DONT show the header and footer
$showHeader = false;
$showFooter = false;

// include header and the stylesheet for the current page
// adminPage = name of the stylesheet
$stylesheet = 'loginPage';
include __DIR__ . './../php/includes/header.php';

use WebDev\config\Database;
use WebDev\Functions\CSRF;
use WebDev\Functions\Auth;

$db = Database::getInstance();

$message = '';
?>

<!-- the login modal -->
<div class="loginModal">
    <h2>LOGIN</h2> 
    <form method="post">
        <!-- put hidden csrf field -->
        <?= CSRF::getInstance()->getCSRFField(); ?>

        <input name="username" type="text" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>

        <!-- container for "remember me" to keep it on the same line -->
        <div class="rememberMeContainer">
            <input name="rememberMe" type="checkbox" id="rememberMe">
            <label for="rememberMe">Remember me</label>
        </div>

        <!-- message -->
        <p><?= htmlspecialchars($message); ?></p>

        <input type="submit" name="submit" value="Login">
        <label for="register">Don't have an account? <a href="/register">Register</a></label>
    </form>
</div>

<!-- The script to login the user -->
<?php
// Display the message if it exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // if the submit button was clicked
    if (isset($_POST['submit'])){ // button is clicked, login
        try {
            // validate CSRF token
            $csrf = CSRF::getInstance();

            // get the token from the form
            $formToken = $_POST['csrf_token'] ?? ''; // either get the token or null

            if (!$csrf->validateToken($formToken)){
                // not valid, do NOT proceed
                error_log("CSRF validation failed.");

                // Redirect back to the login page with an error message
                $_SESSION['message'] = "Session expired. Please try again.";
                header('Location: /login');
                exit; // stop continuing script
            }

            // valid, you can proceed

            $username = $_POST['username']; // no need for null, required in form

            Auth::validateUser($username); // validate the username

            $password = $_POST['password']; // no need for null, required in form

            // login the user
            $infoArr = Auth::getInstance()->login($username, $password);

            // set the session data
            $_SESSION['id'] = $infoArr['id'];
            $_SESSION['username'] = $infoArr['username'];

            // if it suceeds, redirect user to homepage
            header('Location: /');
        }
        catch (Exception $e){
            error_log($e->getMessage());
        }
    }
}

?>

<?php // include footer
include __DIR__ . './../php/includes/footer.php';
?>