<?php
/**
 * School Admin Page
 *
 * Displays the school admin interface for managing database tables.
 * Only accessible to users with owner permissions (ID 1).
 *
 * @file adminSchoolPage.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.8
 * @see TableRenderer, Table, Database, AuthorizationException, AppException, User
 * @todo Add more admin features and validation
 */

use WebDev\Bootstrap;

Bootstrap::init();

/**
 * Flag to start the session (or not).
 * @var bool
 */
$startSession = false;
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

// Database
use WebDev\Database\Database;

// UI
use WebDev\UI\TableRenderer;

// Exceptions
use WebDev\Exception\AppException;
use WebDev\Exception\AuthorizationException;

// make sure AppException and all its subclasses are loaded
AppException::init();

if (!isset($_SESSION['id']) || $_SESSION['id'] !== 1){
    throw new AuthorizationException(
        message: "Unauthorized access attempt to admin page",
        code: 403,
        userRole: "guest", // User role
        resource: "/adminSchoolPage", // Resource being accessed
        actionAttempted: "view", // Action attempted
        requiredRole: "owner", // Required role
        ipv4: $_SERVER['REMOTE_ADDR'] ?? 'Unknown', // Client IP address
        userId: $_SESSION['id'] ?? null, // User ID 
        previous: null // No previous exception
    );
}

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

$stylesheet = 'schoolAdminPage';

/**
 * Title of the website.
 * @var string
 */
$title = 'School Admin Page';
$show = true; // set show to true to show the top navbar
include __DIR__ . '/../templates/header.php';

require_once __DIR__ . '/../assets/constants/ConstantsLoader.php';
?>

<!-- HTML STRUCTURE -->

<div class="adminPageHeader">
    <h1>School Admin Dashboard</h1>
    <p class="subtitle">Advanced Database Management System</p>
</div>

<div id="<?= $consts->adminSchool->content->name ?>">
    <section id="<?= $consts->adminSchool->content->staticTable->name ?>" class="adminSection">
        <div class="sectionHeader">
            <label for="<?= $consts->adminSchool->content->staticTable->select ?>">Database Table:</label>
            <select
                name="<?= $consts->adminSchool->content->staticTable->select ?>"
                id="<?= $consts->adminSchool->content->staticTable->select ?>"
            >
                <?php
                    $tableNames = Database::getInstance()->getTableNames();
                    $dropdownHtml = TableRenderer::getTableNamesDropdown($tableNames);
                    echo $dropdownHtml !== false
                        ? $dropdownHtml
                        : "<option value='' disabled>Error loading table names</option>";
                ?>
            </select>
        </div>
        <div id="<?= $consts->adminSchool->content->staticTable->display ?>" class="tablePrintout"></div>
    </section>

    <section id="<?= $consts->adminSchool->content->actionTable->name ?>" class="adminSection">
        <div class="sectionHeader">
            <label for="<?= $consts->adminSchool->content->actionTable->select ?>">Database Action:</label>
            <select
                name="<?= $consts->adminSchool->content->actionTable->select ?>"
                id="<?= $consts->adminSchool->content->actionTable->select ?>"
            >
                <option value="add">Add Record</option>
                <option value="edit">Edit Record</option>
                <option value="delete">Delete Record</option>
            </select>
        </div>
        <div id="<?= $consts->adminSchool->content->actionTable->display ?>" class="tableForm"></div>
        <button type="button" id="submitActionForm">Execute Action</button>
    </section>
</div>

<script type="module" src="/assets/js/adminSchool.js"></script>

<?php
// include footer
include __DIR__ . '/../templates/footer.php';
?>