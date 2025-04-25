<?php
/**
 * Landing Page
 *
 * Displays the landing page for the flight simulator web application.
 * Shows a welcome message and user information if logged in.
 *
 * @file index.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see templates/header.php, templates/footer.php
 * @todo Add more landing page features
 */

declare(strict_types=1);

// only start session if its requested
$startSession = false;
session_start();

$title = 'Landing Page';
$stylesheet = 'landingPage';

// show header and footer
$showHeader = true;
$showFooter = true;

// include header
include __DIR__ . '/../templates/header.php'; 
?>

<?php
$username = (isset($_SESSION["username"])) ? $_SESSION["username"] : "Guest";
$id = (isset($_SESSION["id"])) ? $_SESSION["id"] : "0";
echo "
    <div class='landingPage'>
        <h1>Welcome to the landing page</h1>
        <p>Hello, $username!</p>
        <p>Your ID is: $id</p>
    </div>"
?>


<?php
// include footer
include __DIR__ . '/../templates/footer.php';
?>