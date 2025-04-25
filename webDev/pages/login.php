<?php
/**
 * Login Page
 *
 * Displays the login form for the flight simulator web application.
 * Handles CSRF protection and shows messages for login attempts.
 *
 * @file login.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see Auth, CSRF, templates/header.php, templates/footer.php
 * @todo Add more login features and validation
 */

declare(strict_types=1);

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
include __DIR__ . '/../templates/header.php';

// for CSRF hidden field
use WebDev\Auth\CSRF;

$message = '';

// Display the message if it exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!-- the login modal -->
<div class="loginModal">
    <h2>LOGIN</h2> 
    <form method="post" action="/auth?action=login">
        <!-- put hidden csrf field -->
        <?= CSRF::getInstance()->getCSRFField(); ?>

        <!-- rest of the form -->
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

<?php // include footer
include __DIR__ . '/../templates/footer.php';
?>