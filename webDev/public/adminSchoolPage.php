<?php

session_start();

if (!($_SESSION['id'] === '1') || !isset($_SESSION['id'])){
    header('Location: /');
    exit;
}

// include table functions file only ONCE
include_once __DIR__ . './../php/functions/tableFunctions.php';

// generate a CSRF token to prevent CSRF attacks
if (!isset($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32)); // Generate a new RANDOM CSRF token
}

/*
    #######################################
    #                                     #
    #             AJAX SCRIPT             #
    #                                     #
    #######################################
*/

// some ajax request caught
if ($_POST['action']){
    switch ($_POST['action']){
        case 'getValue':
            // get the table name
            $tableName = $_POST['value'];

            // include the table functions file
            include_once __DIR__ . './../php/functions/tableFunctions.php';

            // display the form based on the action
            displayFullTable($tableName);

            break;
        case 'tableActionChoice':
            // get the table name and the action
            $tableName = $_POST['tableName'];
            $action = $_POST['value'];

            // include the table functions file
            include_once __DIR__ . './../php/functions/tableFunctions.php';

            displayTableForm($tableName, $action);

            break;
    }

    exit;
}

// include database connection
use Dom\Mysql;

// start session and set a variable to not start it in header.php
$startSession = false;

include_once __DIR__ . './../config/db.php'; 

// DO show the header and footer
$showHeader = true;
$showFooter = true;

// include header and the stylesheet for the current page
// adminPage = name of the stylesheet
// title = title of the page
$stylesheet = 'schoolAdminPage';
$title = 'School Admin Page';
$show = true; // set show to true to show the top navbar
include __DIR__ . './../php/includes/header.php';


?>

<!-- HTML STRUCTURE -->

<div class="content">
    <div class="tablePrintout">
        <div class="tableDropdown">
            <form method="POST">
                <label for="tableName">Table: </label>
                <select name="tableName" id="tableName">
                    <?php // php script to get the tables names and put them as options
                        displayTableNamesDropdown();
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
include __DIR__ . './../php/includes/footer.php';
?>

<!-- AJAX -->
<script>

// functions to send data to php
function tableSelect(value) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("tableData").innerHTML = xhr.responseText;
        }
    };

    // Send the value and specify the action
    xhr.send("action=getValue&value=" + encodeURIComponent(value));
}

function actionSelect(value) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("actionForm").innerHTML = xhr.responseText;
        }
    };

    // Get the selected table name
    var tableName = document.getElementById("tableName").value;

    // Send table name and action value
    xhr.send("action=tableActionChoice&value=" + encodeURIComponent(value) + "&tableName=" + encodeURIComponent(tableName));
}

// on select change, display new value based on choice
document.getElementById("tableName").addEventListener("change", function() {
    tableSelect(this.value);
});

document.getElementById("tableAction").addEventListener("change", function() {
    actionSelect(this.value);
});

// on window load, make sure the default values are chosen and displayed
window.onload = function() {
    var tableSelectElement = document.getElementById("tableName");
    if (tableSelectElement.options.length > 0) {
        tableSelectElement.value = tableSelectElement.options[0].value; // Select the first option
        tableSelect(tableSelectElement.value); // Trigger AJAX
    }

    var actionSelectElement = document.getElementById("tableAction");
    if (actionSelectElement.options.length > 0) {
        actionSelectElement.value = actionSelectElement.options[0].value; // Select the first option
        actionSelect(actionSelectElement.value); // Trigger AJAX
    }
};

// ADD

function sanitizeInput(input) {
    // remove all unwanted characters to prevent XSS
    return input.replace(/[<>"'`]/g, "");
}

document.getElementById("actionFormSubmitButton").addEventListener("click", function (){
    console.log("IS IT HERE");

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

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
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