<?php
declare(strict_types=1);

// start session and set a variable to not start it in header.php
session_start();
$startSession = false;

// DO show the header and footer
$showHeader = true;
$showFooter = true;

// include header and the stylesheet for the current page
// adminPage = name of the stylesheet
// title = title of the page
$stylesheet = 'adminPage';
$title = 'Admin Page';
$show = true; // set show to true to show the top navbar
include __DIR__ . '/../templates/header.php';

// Database
use WebDev\Database\Database;
use WebDev\Database\Table;

// UI
use WebDev\UI\TableRenderer;

// Exceptions
use WebDev\Exception\AppException;
use WebDev\Exception\AuthorizationException;

AppException::init();

set_exception_handler(function (Throwable $ae){
    if ($ae instanceof AppException){ // appException or its subclasses
        AppException::globalHandle($ae);
        header('Location: /'); // for now
        exit;
    }
    else { // anything but appException and its subclasses
        error_log($ae->getMessage());
    }
});

if (!isset($_SESSION['id']) || !in_array($_SESSION['id'], [1, 2])){
    throw new AuthorizationException(
        message: "Unauthorized access attempt to admin page",
        code: 403,
        userRole: 'user',
        resource: "/admin",
        actionAttempted: "view",
        requiredRole: "co-owner",
        ipv4: $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        userId: $_SESSION['id'] ?? null,
        previous: null
    );
}

$db = Database::getInstance();

?>

<form method="POST">
    <label for="tableName">Pick a table name to show: </label>
    <select name="tableName">
        <?php // php script to get the tables names and put them as options
            // Fetch table names from the database
            $tableNames = Database::getInstance()->getTableNames();

            // Render the dropdown options
            $dropdownHtml = TableRenderer::getTableNamesDropdown($tableNames);

            // Echo the dropdown HTML if it was successfully generated
            if ($dropdownHtml !== false){
                echo $dropdownHtml;
            } 
            else {
                echo "<option value='' disabled>Error loading table names</option>";
            }
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

    // get table object
    $table = Table::getInstance($tableName);

    // get result
    $result = $table->selectAll();

    // print table
    TableRenderer::getInstance($table)->displayTable($result, true);
}

// include footer
include __DIR__ . '/../templates/footer.php';