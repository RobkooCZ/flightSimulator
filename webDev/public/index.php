<?php


// only start session if its requested
$startSession = false;
session_start();

$title = 'Landing Page';
$stylesheet = 'landingPage';

// show header and footer
$showHeader = true;
$showFooter = true;

// include header
include __DIR__ . '/../php/includes/header.php'; 
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
include __DIR__ . '/../php/includes/footer.php';
?>