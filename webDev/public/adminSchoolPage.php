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
 * @version 0.3.4
 * @see TableRenderer, Table, Database, AuthorizationException, AppException, User
 * @todo Add more admin features and validation
 */

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

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

/*
    #######################################
    #                                     #
    #             AJAX SCRIPT             # 
    #                                     #
    #######################################
*/

// some ajax request caught
if (isset($_POST['action'])){
    // => action
    $user = User::current();
    if ($user) $user->recordActivity();

    switch ($_POST['action']){
        case 'getValue':
            // get passed tablename
            $tableName = urldecode($_POST['value']);

            // get table object based on name
            $table = Table::getInstance($tableName);

            // get table renderer for the table
            $tableRenderer = TableRenderer::getInstance($table);

            // get result
            $result = $table->selectAll();

            // display the form based on the action
            $tableRenderer->displayTable($result, true);

            break;
        case 'tableActionChoice':
            // get the table name and the action
            $action = urldecode($_POST['value']);
            $tableName = urldecode($_POST['tableName']);

            // get table object based on name
            $table = Table::getInstance($tableName);

            // get table renderer for the table
            $tableRenderer = TableRenderer::getInstance($table);
            
            // get result
            $result = $table->selectAll();

            // display table form
            $tableRenderer->displayTableForm($result, $action, $_SESSION['id'] ?? 0);

            break;
    }

    exit;
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


?>

<!-- HTML STRUCTURE -->

<div class="content">
    <div class="tablePrintout">
        <div class="tableDropdown">
            <form method="POST">
                <label for="tableName">Table: </label>
                <select name="tableName" id="tableName">
                <?php
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
            </form>
        </div>

        <div class="tableData" id="tableData">
            <?php
                
            ?>
        </div>
    </div>

    <div class="tableForm">
        <div class="tableFormDropdown">
            <form method="POST">
                <label for="tableAction">Action: </label>
                <select name="tableAction" id="tableAction">
                    <option value="add">Add</option>
                    <option value="edit">Edit</option>
                    <option value="delete">Delete</option>
                </select>
            </form>
        </div>

        <div class="actionForm" id="actionForm">
            <?php
                
            ?>
        </div>

        <button type="submit" id="actionFormSubmitButton">TEST SUBMIT</button>
    </div>
</div>

<?php
// include footer
include __DIR__ . '/../templates/footer.php';
?>

<!-- AJAX -->
<script>

// functions to send data to php
function tableSelect(value){
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function(){
        if (xhr.readyState === 4 && xhr.status === 200){
            document.getElementById("tableData").innerHTML = xhr.responseText;
        }
    };

    // Send the value and specify the action
    xhr.send("action=getValue&value=" + encodeURIComponent(value));
}

function actionSelect(value){
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function(){
        if (xhr.readyState === 4 && xhr.status === 200){
            document.getElementById("actionForm").innerHTML = xhr.responseText;
        }
    };

    // Get the selected table name
    var tableName = document.getElementById("tableName").value;

    // Send table name and action value
    xhr.send("action=tableActionChoice&value=" + encodeURIComponent(value) + "&tableName=" + encodeURIComponent(tableName));
}

// on select change, display new value based on choice
document.getElementById("tableName").addEventListener("change", function(){
    tableSelect(this.value);
});

document.getElementById("tableAction").addEventListener("change", function(){
    actionSelect(this.value);
});

// on window load, make sure the default values are chosen and displayed
window.onload = function(){
    var tableSelectElement = document.getElementById("tableName");
    if (tableSelectElement.options.length > 0){
        tableSelectElement.value = tableSelectElement.options[0].value; // Select the first option
        tableSelect(tableSelectElement.value); // Trigger AJAX
    }

    var actionSelectElement = document.getElementById("tableAction");
    if (actionSelectElement.options.length > 0){
        actionSelectElement.value = actionSelectElement.options[0].value; // Select the first option
        actionSelect(actionSelectElement.value); // Trigger AJAX
    }
};

// ADD

function sanitizeInput(input){
    // remove all unwanted characters to prevent XSS
    return input.replace(/[<>"'`]/g, "");
}

document.getElementById("actionFormSubmitButton").addEventListener("click", function (){
    // get all of the inputted data (username, password, role)
    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const role = document.getElementById("role").value;

    // sanitize
    const sanitizedUsername = sanitizeInput(username);
    const sanitizedPassword = sanitizeInput(password);
    const sanitizedRole = sanitizeInput(role);

    // send data to /functions/actionScript.php
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "/actionScript", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function(){
        if (xhr.readyState === 4 && xhr.status === 200){
            // Display the server's response in the actionForm div
            // document.getElementById("actionForm").innerHTML = xhr.responseText;
        }
    };

    // Send the sanitized data to the server
    var actionSelectElement = document.getElementById("tableAction");
    // console.log("Action: " + actionSelectElement.value);
    // console.log("Username: " + sanitizedUsername);
    // console.log("Password: " + sanitizedPassword);
    // console.log("Role: " + sanitizedRole);
    // console.log("Table Name: " + document.getElementById("tableName").value);
    xhr.send(
        "action=" + encodeURIComponent(actionSelectElement.value) +
        "&username=" + encodeURIComponent(sanitizedUsername) +
        "&password=" + encodeURIComponent(sanitizedPassword) +
        "&role=" + encodeURIComponent(sanitizedRole) +
        "&tableName=" + encodeURIComponent(document.getElementById("tableName").value)
    );
});

</script>