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
 * @version 0.7.10
 * @see TableRenderer, Table, Database, AuthorizationException, AppException, User
 * @todo Add more admin features and validation
 */

declare(strict_types=1);

use WebDev\Auth\AccessControl;
use WebDev\Bootstrap;
Bootstrap::init();

/**
 * Flag to start the session (or not).
 * @var bool
 */
$startSession = false;
session_start();

/**
 * Flag to show the navbar.
 * @var bool
 */
$showHeader = true;

/**
 * Flag to show the footer.
 * @var bool
 */
$showFooter = true;

// include header and the stylesheet for the current page

$stylesheet = 'adminPage';

/**
 * Title of the website.
 * @var string
 */
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

// User
use WebDev\Auth\User;

AppException::init();

// check permission
AccessControl::requireAccess('admin');

$db = Database::getInstance();

?>
<main>
    <div class="adminHeader">
        <h1>Admin Dashboard</h1>
        <p class="subtitle">Database Table Management System</p>
    </div>

    <div class="tableSelectorContainer">
        <form method="POST">
            <div class="formGroup">
                <label for="tableName">Select Database Table:</label>
                <select name="tableName" id="tableName" required>
                    <option value="" disabled selected>Choose a table...</option>
                    <?php
                        $tableNames = Database::getInstance()->getTableNames();
                        $dropdownHtml = TableRenderer::getTableNamesDropdown($tableNames);
                        
                        if ($dropdownHtml !== false){
                            echo $dropdownHtml;
                        }
                        else {
                            echo "<option value='' disabled>Error loading table names</option>";
                        }
                    ?>
                </select>
            </div>
            
            <input type="submit" name="submit" value="Show Table Data"> 
        </form>
    </div>

    <?php if (isset($_POST['submit'])): ?>
        <div class="tableContainer">
            <?php
                $user = User::current();
                if ($user) $user->recordActivity();

                $tableName = $_POST['tableName'];
                $table = Table::getInstance($tableName);
                $result = $table->selectAll();
                
                if (empty($result)){
                    echo '<div class="emptyState">';
                    echo '<h3>No Data Found</h3>';
                    echo '<p>The selected table "' . htmlspecialchars($tableName) . '" is empty.</p>';
                    echo '</div>';
                }
                else {
                    TableRenderer::getInstance($table)->displayTable($result, true);
                }
            ?>
        </div>
    <?php endif; ?>
</main>

<?php

// include footer
include __DIR__ . '/../templates/footer.php';