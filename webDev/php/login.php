<?php
// start session and set a variable to not start it in header.php
session_start();
$startSession = false;

// make sure all errors are displayed
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// title for the site
$title = 'Login';

// DONT show the header and footer
$showHeader = false;
$showFooter = false;

// include header and the stylesheet for the current page
// adminPage = name of the stylesheet
$stylesheet = 'loginPage';
include __DIR__ . './../php/includes/header.php';

// get the database connection (include db.php too)
include_once __DIR__ . './../config/db.php';
$conn = getDatabaseAndTableConnection('users'); // get the connection to the users table (to login)

?>

<!-- the login modal -->
<div class="loginModal">
    <h2>LOGIN</h2> 
    <form method="post">
        <input name="username" type="text" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>

        <!-- container for "remember me" to keep it on the same line -->
        <div class="rememberMeContainer">
            <input name="rememberMe" type="checkbox" id="rememberMe">
            <label for="rememberMe">Remember me</label>
        </div>
        <input type="submit" name="submit" value="Login">
        <label for="register">Don't have an account? <a href="/register">Register</a></label>
    </form>
</div>

<!-- The script to login the user -->
<?php

// if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // if the submit button was clicked
    if (isset($_POST['submit'])){ // button is clicked, login
        try {
            $username = $_POST['username']; // no need for null, required in form
            $password = $_POST['password']; // no need for null, required in form

            // prepare the query (SAFELY)
            $stmt = $conn->prepare("SELECT id, username, password, salt FROM users WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            $stmt->bind_param('s', $username); // s = string
            if (!$stmt->execute()) {
                throw new Exception("Execute statement failed: " . $stmt->error);
            }

            if (!$stmt->store_result()) {
                throw new Exception("Store result failed: " . $stmt->error);
            }

            $stmt->bind_result($id, $usernameDb, $passwordHash, $salt);
            if (!$stmt->fetch()) {
                throw new Exception("Fetch result failed: " . $stmt->error);
            }

            // if the user exists
            if ($stmt->num_rows > 0){
                // check if the password is correct
                if (password_verify($password . $salt, $passwordHash)){ 
                    // login the user
                    $_SESSION['username'] = $username;
                    $_SESSION['id'] = $id;
                    echo "You are logged in!";
                }
                else{
                    echo "Password is incorrect!";
                }
            }
            else{
                echo "User not found!";
            }

            // make sure you dont send multiple queries
            header('Location: /');
            exit();
        }
        catch (Exception $e){
            error_log($e->getMessage());
            $_SESSION['message'] = "Internal Error! Failed to login!";
        }
    }

    // close the statement and the connection
    $stmt->close();
    $conn->close();
}

?>

<?php // include footer
include __DIR__ . './../php/includes/footer.php';
?>