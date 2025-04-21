<?php
declare(strict_types=1);

// only start session if its requested
if ($startSession === true){
    session_start();
}

use WebDev\Auth\CSRF;
use WebDev\Auth\User;

// logging
use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Logger;

// function to check for header to set correct active class

function matchHeader(string $title): int {
    // 0 - not found
    // 1 - main page
    // 2 - home
    // 3 - admin
    // 4 - school admin

    $returnVal = 0; // default not found
    
    $returnVal = match($title){
        'Landing Page' => 1,
        'Home' => 2,
        'Admin Page' => 3,
        'School Admin Page' => 4,
        default => 0, // if it wasnt found
    };

    return $returnVal; // return value
}

// get active val
$activeVal = matchHeader($title);

// if a link was clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['linkClicked']) && $_POST['linkClicked'] == "true") {
        // Check if user is logged in before accessing session data
        if (isset($_SESSION[User::SESSION_ID_KEY])) {
            Logger::log(
                "User (ID: {$_SESSION[User::SESSION_ID_KEY]}) has performed an action.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            User::load($_SESSION[User::SESSION_ID_KEY])->recordActivity();
        }
    }
}

?>

<!-- html -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <!-- first theme for all the vars declared there -->
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
    <link rel="stylesheet" href="/assets/css/<?php echo $stylesheet; ?>.css">
</head>
<body class="dark-theme">
    <!-- if $showHeader === true, show header, otherwise don't -->
    <?php
        if ($showHeader === true){
            echo '
                <header>
                    <nav class="navbar">
                        <div class="leftSide">
                            <a>Logo</a> 
                            <a href="/" ' . ($activeVal === 1 ? 'class="active links"' : 'class="links"') . '>Main Page</a>
                            <a href="/home" ' . ($activeVal === 2 ? 'class="active links"' : 'class="links"') . '>Home</a>
                        </div>
                        ';
                        
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
                        

                        if (isset($_SESSION['username'])){
                            echo '
                                <div class="rightSide">
                                    <p id="loggedInAs">Logged in as <b>' . $_SESSION['username'] . '</b></p>'
                                    . $adminPage .
                                    '<a href="/auth?action=logout&csrf_token=' . CSRF::getInstance()->getToken() . '" class="links">Logout</a>
                                </div>
                            ';
                        } 
                        else {
                            echo '
                                <div class="rightSide">
                                    <a href="/login">Login</a>
                                    <a href="/register">Register</a>
                                </div>
                            ';
                        }

            echo'   </nav>
                </header>
            ';
        }
?>
<script>

// todo: separatate JS (or maybe switch to TS) into separate files
async function sendData(url, data){
    try{
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data).toString()
        });

        // if the network response wasnt ok, throw new error
        if (!response.ok) throw new error("Network response was not okay.");
    }
    catch (error){
        console.log('Error: ', error);
    }
}

const links = document.getElementsByClassName("links");

[...links].forEach(link => {
    link.addEventListener('click', e => {
        // Prevent default navigation
        e.preventDefault();
        
        // Get the URL we want to navigate to
        const href = link.getAttribute('href');
        
        // Send AJAX request and wait for it to complete
        sendData(window.location.pathname, { linkClicked: "true" })
            .then(() => {
                // Navigate after logging completes
                window.location.href = href;
            })
            .catch(() => {
                // Navigate even if logging fails
                window.location.href = href;
            });
    });
});

</script>