<?php
/**
 * School Admin Page
 *
 * Displays the school admin interface for managing database tables.
 * Only accessible to users with owner permissions (ID 1).
 *
 * @file adminSchoolPage.php
 * @since TBD
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version TBD
 * @see TableRenderer, Table, Database, AuthorizationException, AppException, User
 * @todo Add more admin features and validation
 */

declare(strict_types=1);

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

set_exception_handler(function (Throwable $ae){
    if (AppException::globalHandle($ae)){ // appException or its subclasses
        header('Location: /'); // for now
        exit;
    }
    else { // anything but appException and its subclasses
        error_log($ae->getMessage()); // temporary (in this file no other exceptions are thrown but AuthorizationException)
    }
});

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

// start session and set a variable to not start it in header.php
$startSession = false;

// DO show the header and footer
$showHeader = true;
$showFooter = true;

// include header and the stylesheet for the current page
// adminPage = name of the stylesheet
// title = title of the page
$stylesheet = 'schoolAdminPage';
$title = 'School Admin Page';
$show = true; // set show to true to show the top navbar
include __DIR__ . '/../templates/header.php';

require_once __DIR__ . '/../assets/constants/constants.php';
?>

<!-- HTML STRUCTURE -->

<div id="<?= $consts->adminSchool->content->name ?>">
    <section id="<?= $consts->adminSchool->content->staticTable->name ?>">
        <label for="<?= $consts->adminSchool->content->staticTable->select ?>">Table:</label>
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
        <div id="<?= $consts->adminSchool->content->staticTable->display ?>"></div>
    </section>

    <section id="<?= $consts->adminSchool->content->actionTable->name ?>">
        <label for="<?= $consts->adminSchool->content->actionTable->select ?>">Action:</label>
        <select
            name="<?= $consts->adminSchool->content->actionTable->select ?>"
            id="<?= $consts->adminSchool->content->actionTable->select ?>"
        >
            <option value="add">Add</option>
            <option value="edit">Edit</option>
            <option value="delete">Delete</option>
        </select>
        <div id="<?= $consts->adminSchool->content->actionTable->display ?>"></div>
        <button type="button" id="submitActionForm">Submit</button>
    </section>
</div>

<script type="module" src="/assets/js/adminSchool.js"></script>

<?php
// include footer
include __DIR__ . '/../templates/footer.php';
?>