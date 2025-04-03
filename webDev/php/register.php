<?php
// start session and set a variable to not start it in header.php
session_start();
$startSession = false;

// make sure all errors are displayed (comment when pushing to production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// title for the site
$title = 'Register';

// DONT show the header and footer
$showHeader = false;
$showFooter = false;

// include header and the stylesheet for the current page
// adminPage = name of the stylesheet
$stylesheet = 'registerPage';
include __DIR__ . './../php/includes/header.php';

// get the database connection (include db.php too)
include __DIR__ . './../config/db.php';
$conn = getDatabaseAndTableConnection('users'); // get the connection to the users table (to login)

$message = ""; // empty string
?>

<!-- the register modal -->
<div class="registerModal">
    <h2>REGISTER</h2>
    <form method="post">
        <input name="username" type="text" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <input name="passwordRepeat" type="password" placeholder="Repeat Password" required>    

        <p><?php echo $message; ?></p>

        <input type="submit" name="submit" value="Register">
        <label for="register">Already have an account? <a href="/login">Login</a></label>
    </form>
</div>

<!-- The script to login the user -->
<?php

// if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // if the submit button was clicked
    if (isset($_POST['submit'])){ // button is clicked, register
        // check if the passwords match
        if ($_POST['password'] !== $_POST['passwordRepeat']){
            $message = "Passwords do not match!";
            header('Location: ' . $_SERVER['PHP_SELF']); // stay on the current site
            exit();
        }

        // make sure you dont send multiple queries
        header('Location: /'); // redirect to main page
        exit();
    }

    // close the statement and the connection
    $stmt->close();
    $conn->close();
}

?>

<?php // include footer
include __DIR__ . './../php/includes/footer.php';
?>