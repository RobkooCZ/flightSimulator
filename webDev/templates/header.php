<?php
/**
 * Header Template
 *
 * Displays the header and navigation for the flight simulator web application.
 * Handles navigation highlighting, user session, and AJAX link tracking.
 *
 * @file header.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.8
 * @see templates/footer.php, Auth\User, Auth\CSRF, Logger
 * @todo Better style it, more links
 */

use WebDev\Bootstrap;
Bootstrap::init();

// only start session if its requested
if ($startSession === true){
    session_start();
}

// include classes
use WebDev\Auth\CSRF;
use WebDev\Auth\User;
use WebDev\Database\UserPreferences;
use WebDev\Utilities\FileHandler;

// logger stuff
use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Logger;

// get the user theme
// attempt to load the theme choice if the user is logged in
if (isset($_SESSION[User::SESSION_ID_KEY])){
    /**
     * The theme if it succeeded, false if it hadn't.
     * @var string|false
     */
    $result = UserPreferences::loadPref($_SESSION[User::SESSION_ID_KEY], "userTheme");

    // if loading from the database failed
    if ($result === false){
        Logger::log(
            "Failed to load user theme preference for user ID: " . $_SESSION[User::SESSION_ID_KEY],
            LogLevel::WARNING,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        /**
         * Default theme if we fail to fetch the user prefered one.
         * @var string $theme
         */
        $theme = 'dark-theme';
    }
    else {
        // if the result is light, no theme name is necessary, otherwise, set the result
        ($result === 'light') ? $theme = '' : $theme = $result;
    }
}
else {
    /**
     * Default theme if the user isn't logged in.
     * @var string $theme
     */
    $theme = 'dark-theme';
}

// function to check for header to set correct active class

/**
 * Based on the provided title we return the number of the page we're currently on.
 *
 * @param string $title The title of the page
 * @return int The page id.
 */
function matchHeader(string $title): int {
    // 0 - not found
    // 1 - landing page - deprecated
    // 2 - home
    // 3 - admin
    // 4 - school admin
    // 5 - profile

    /**
     * @var int
     */
    $returnVal = 0; // default not found
    
    $returnVal = match($title){
        'Landing Page' => 1,
        'Home' => 2,
        'Admin Page' => 3,
        'School Admin Page' => 4,
        'Profile' => 5,
        default => 0, // if it wasnt found
    };

    return $returnVal; // return value
}

// get active val
$activeVal = matchHeader($title);

// get user pfp if the user is logged in
if (isset($_SESSION[User::SESSION_ID_KEY])){
    // user id
    $uid = $_SESSION[User::SESSION_ID_KEY];

    // get the profile picture path
    $handler = new FileHandler($uid); // could be linkHandler, both share the same load method from the Handler parent class
    $path = $handler->load();
}
?>

<!-- html -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <!-- first theme for all the vars declared there -->
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
    <link rel="stylesheet" href="/assets/css/<?= $stylesheet ?>.css">
    <link rel="shortcut icon" href="/assets/images/icons/favicon.ico" type="image/x-icon">
</head>
<body class="<?= $theme ?>">
    <!-- if $showHeader === true, show header, otherwise don't -->
    <?php
        if ($showHeader === true){
            echo '
                <header>
                    <nav class="navbar">
                        <div class="leftSide">
                            <a id="logoLink" href="/"><img id="logoImg" src="/assets/images/logo/logo.png" alt="RCFS Logo"></a>
                            <a href="/home" ' . ($activeVal === 2 ? 'class="active links"' : 'class="links"') . '>Home</a>
                        </div>
                        ';
                        
                        // save the admin link into a variable if the user id is 1 or 2
                        if (!empty($_SESSION['id']) && in_array($_SESSION['id'], [1, 2])){
                            $adminPage = '<a href="/admin" ' . ($activeVal === 3 ? 'class="active links"' : 'class="links"') . '>Admin Page</a>';
                        } 
                        else {
                            $adminPage = ''; // Ensure $adminPage is always defined
                        }
                        
                        // Append the "School Admin Page" link if the user is id = 1
                        if (!empty($_SESSION['id']) && $_SESSION['id'] == 1){
                            $adminPage .= '<a href="/adminSchool" ' . ($activeVal === 4 ? 'class="active links"' : 'class="links"') . '>School Admin Page</a>';
                        }
                        
                        // user logged in
                        if (isset($_SESSION['id'])){
                            echo '
                                <div class="rightSide">
                                    ' . $adminPage . '
                                </div>

                                <div id="dropdown">
                                    <a id="profile" href="#"><img src="' . $path . '"></a>
                                    <div id="dropdownContent">
                                        <a href="/profile">Profile</a>
                                        <a href="/auth?action=logout&csrf_token=' . CSRF::getInstance()->getToken() . '">Logout</a>
                                    </div>
                                </div>
                            ';
                        } 
                        else { // user not logged in
                            echo '
                                <div class="rightSide">
                                    <a href="/login" class="links">Login</a>
                                    <a href="/register" class="links">Register</a>
                                </div>
                            ';
                        }

            echo'   </nav>
                </header>
            ';
        }
?>
<!-- include the header js script -->
<script type="module" src="/assets/js/header.js"></script>