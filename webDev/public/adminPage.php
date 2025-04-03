<?php
// include database connection
use Dom\Mysql;

// start session and set a variable to not start it in header.php
session_start();
$startSession = false;

if (!($_SESSION['id'] === '1') || !($_SESSION['id'] === '2') || !isset($_SESSION['id'])){
    header('Location: /');
}

include_once __DIR__ . './../config/db.php'; 

// DO show the header and footer
$showHeader = true;
$showFooter = true;

// include header and the stylesheet for the current page
// adminPage = name of the stylesheet
// title = title of the page
$stylesheet = 'adminPage';
$title = 'Admin Page';
$show = true; // set show to true to show the top navbar
include __DIR__ . './../php/includes/header.php';

// include the table functions file for table printout
include __DIR__ . './../php/functions/tableFunctions.php';

// TEST
?>

<form method="POST">
    <label for="tableName">Pick a table name to show: </label>
    <select name="tableName">
        <?php // php script to get the tables names and put them as options
            displayTableNamesDropdown();
        ?>
    </select>
        
    <br><br>
    <input type="submit" name="submit" value="Show Table"> 
</form>

<?php

// button was pressed
if (isset($_POST['submit'])){
    // get the table name
    $tableName = $_POST['tableName'];

    // display the full table
    displayFullTable($tableName);
}

// include footer
include __DIR__ . './../php/includes/footer.php';