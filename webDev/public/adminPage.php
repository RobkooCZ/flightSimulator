<?php
/**
 * Admin Page
 *
 * Displays the admin interface for managing database tables.
 * Only accessible to users with appropriate permissions (IDs 1 or 2).
 *
 * @file adminPage.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.3
 * @see TableRenderer, Table, Database, AuthorizationException, AppException, User
 * @todo Add more admin features and validation
 */

declare(strict_types=1);

use WebDev\Bootstrap;

Bootstrap::init();

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

// User
use WebDev\Auth\User;

AppException::init();

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
    // record activity
    $user = User::current();
    if ($user) $user->recordActivity();

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