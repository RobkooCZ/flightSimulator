<?php
/**
 * Register Page
 *
 * Displays the registration form for the flight simulator web application.
 * Handles CSRF protection and shows messages for registration attempts.
 *
 * @file register.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see Auth, CSRF, templates/header.php, templates/footer.php
 * @todo Add more registration features and validation
 */

declare(strict_types=1);

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
include __DIR__ . '/../templates/header.php';

// for CSRF hidden field
use WebDev\Auth\CSRF;

$message = "";

// Display the message if it exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!-- The register modal -->
<div class="registerModal">
    <h2>REGISTER</h2>
    <form method="post" action="/auth?action=register">
        <!-- include CSRF -->
        <?= CSRF::getInstance()->getCSRFField(); ?>

        <!-- rest of the form -->
        <input name="username" type="text" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <input name="passwordRepeat" type="password" placeholder="Repeat Password" required>    

        <p><?= htmlspecialchars($message); ?></p>

        <input type="submit" name="submit" value="Register">
        <label for="register">Already have an account? <a href="/login">Login</a></label>
    </form>
</div>

<?php // Include footer
include __DIR__ . '/../templates/footer.php';
?>