<?php
$startSession = false;
// include database connection
include_once __DIR__ . './../../config/db.php'; 

/**
 * *
 * @param string $tableIdentifier
 * @throws \Exception
 * @return void
 */
function displayFullTable(string $tableIdentifier): void {
    try {
        /* 
            #######################################
            #                                     #
            #         GET THE CONN OBJECT         #
            #                                     #
            #######################################
        */

        // get the table name we want to display
        $tableName = checkForTable($tableIdentifier);

        // check if it was successfully found
        if ($tableName === 'TABLE_NOT_FOUND') {
            throw new Exception("Table {$tableIdentifier} was not found.");
        }

        if ($tableName === 'TABLE_DOES_NOT_EXIST') {
            throw new Exception("Table {$tableIdentifier} does not exist.");
        }

        // get the conn object with the connection to the database and the specified table
        $conn = getDatabaseAndTableConnection($tableName);

        // check if it was successful
        if ($conn === false) {
            throw new Exception("Failed to connect to database.");
        }

        /* 
            #######################################
            #                                     #
            #    FETCH COLUMNS FROM THE TABLE     #
            #                                     #
            #######################################
        */

        // query and result
        $colQuery = "SHOW COLUMNS FROM {$tableName}"; // query to get the columns of the table
        $colResult = $conn->query($colQuery); // get the result of the query

        // check if the query was successful
        if (!$colResult){
            throw new Exception("Failed to get columns: " . $conn->error);
        }

        // check if the result is empty
        if ($colResult->num_rows === 0) {
            throw new Exception("No columns found in table {$tableName}.");
        }

        /* 
            #######################################
            #                                     #
            #       FETCH DATA FROM THE TABLE     #
            #                                     #
            #######################################
        */

        // query and result
        $tableData = "SELECT * FROM {$tableName}"; // query to get all the data from the table
        $result = $conn->query($tableData); // get the result of the query

        // check if the query was successful
        if (!$result){
            throw new Exception("Failed to get data: " . $conn->error);
        }

        // check if the result is empty
        if ($result->num_rows === 0) {
            throw new Exception("No data found in table {$tableName}.");
        }


        /* 
            #######################################
            #                                     #
            #        PRINT THE TABLE HEADER       #
            #                                     #
            #######################################
        */

        $columns = []; // define an empty array to store the columns

        // table structure
        echo "<table style='border: var(--border)';>"; // open the table
        echo "<tr>"; // open the header row

        // while loop to print the header
        while ($col = $colResult->fetch_assoc()) {
            echo "<th>" . htmlspecialchars($col['Field']) . "</th>"; // echo the column header
            $columns[] = $col['Field']; // append column to the columns array
        }

        echo "</tr>"; // close the header row

        /* 
            #######################################
            #                                     #
            #        PRINT THE TABLE BODY         #
            #                                     #
            #######################################
        */

        // while loop to print the body
        while ($row = $result->fetch_assoc()){
            // begin row
            echo "<tr>";

            // foreach loop to print out all the columns of the table
            foreach($columns as $colName){
                // make sure vulnerable info isnt printed out.
                if ($colName === 'password' || $colName === 'salt') {
                    echo '<td title="----------">----------</td>';
                } 
                else {
                    echo '<td title="' . htmlspecialchars(string: $row[$colName]) . '">' . htmlspecialchars($row[$colName]) . '</td>';
                }
            }

            // end row
            echo "</tr>";
        }

        // end table
        echo "</table>";

        // free resources
        $colResult->free();
        $result->free();
        $conn->close();
    } 
    catch (Exception $e) {
        error_log($e->getMessage());
    }
}

function displayTableForm(string $tableIdentifier, string $actionName): void {
    try {
        /* 
            #######################################
            #                                     #
            #         GET THE CONN OBJECT         #
            #                                     #
            #######################################
        */

        // get the table name we want to display
        $tableName = checkForTable($tableIdentifier);

        // check if it was successfully found
        if ($tableName === 'TABLE_NOT_FOUND') {
            throw new Exception("Table {$tableIdentifier} was not found.");
        }

        if ($tableName === 'TABLE_DOES_NOT_EXIST') {
            throw new Exception("Table {$tableIdentifier} does not exist.");
        }

        // get the conn object with the connection to the database and the specified table
        $conn = getDatabaseAndTableConnection($tableName);

        // check if it was successful
        if ($conn === false) {
            throw new Exception("Failed to connect to database.");
        }

        /* 
            #######################################
            #                                     #
            #    FETCH COLUMNS FROM THE TABLE     #
            #                                     #
            #######################################
        */

        // query and result
        $colQuery = "SHOW COLUMNS FROM {$tableName}"; // query to get the columns of the table
        $colResult = $conn->query($colQuery); // get the result of the query

        // check if the query was successful
        if (!$colResult){
            throw new Exception("Failed to get columns: " . $conn->error);
        }

        // check if the result is empty
        if ($colResult->num_rows === 0) {
            throw new Exception("No columns found in table {$tableName}.");
        }

        /* 
            #######################################
            #                                     #
            #       FETCH DATA FROM THE TABLE     #
            #                                     #
            #######################################
        */

        // query and result
        $tableData = "SELECT * FROM {$tableName}"; // query to get all the data from the table
        $result = $conn->query($tableData); // get the result of the query

        // check if the query was successful
        if (!$result){
            throw new Exception("Failed to get data: " . $conn->error);
        }

        // check if the result is empty
        if ($result->num_rows === 0) {
            throw new Exception("No data found in table {$tableName}.");
        }


        /* 
            #######################################
            #                                     #
            #        PRINT THE TABLE HEADER       #
            #                                     #
            #######################################
        */

        $columns = []; // define an empty array to store the columns

        // table structure
        echo "<table style='border: var(--border)';>"; // open the table
        echo "<tr>"; // open the header row

        // while loop to print the header
        while ($col = $colResult->fetch_assoc()) {
            echo "<th>" . htmlspecialchars($col['Field']) . "</th>"; // echo the column header
            $columns[] = $col['Field']; // append column to the columns array
        }

        echo "</tr>"; // close the header row

        /* 
            #######################################
            #                                     #
            #        PRINT THE TABLE BODY         #
            #                                     #
            #######################################
        */

        // while loop to print the body
        while ($row = $result->fetch_assoc()){
            // begin row
            echo "<tr>";

            // foreach loop to print out all the columns of the table
            foreach($columns as $colName){
                // make sure vulnerable info isnt printed out.
                if ($colName === 'password' || $colName === 'salt') {
                    echo '<td title="----------">----------</td>';
                } 
                else {
                    echo '<td title="' . htmlspecialchars(string: $row[$colName]) . '">' . htmlspecialchars($row[$colName]) . '</td>';
                }
            }

            // end row
            echo "</tr>";
        }

        /* 
            #######################################
            #                                     #
            #    BASED ON ACTION, DISPLAY FORM    #
            #                                     #
            #######################################
        */

        // decode the action (encoded in AJAX)
        $action = urldecode($actionName);

        // based on action, do different things
        // user wants to add something to the selected table
        if ($action === 'add'){
            echo "<tr>";
            for ($i = 0; $i < count($columns); $i++) { 
                // based on switch case, display different input types
                switch ($columns[$i]){
                    // id is readonly, display next id
                    case "id":
                        $id = getNextId($tableName);
                        echo '<td><input class="addInputs noBorder" type="text" pattern="[0-9]*" name="' . $columns[$i] . '" value="'. $id .'" disabled></td>';
                        break;
                    // salt is randomly generated, dont let the user input
                    case "salt":
                        echo '<td><input class="addInputs noBorder" type="text" name="' . $columns[$i] . '" value="----------" disabled></td>';
                        break;
                    // for role, make a dropdown of the enum
                    case "role":
                        $roleArr = getRolesDropdown($tableName);
                        echo '<td><select id="role" class="addInputs noBorder">';
                        foreach ($roleArr as $role) {
                            echo '<option value="'. $role .'">'. $role .'</option>';
                        }
                        echo '</select></td>';
                        break;
                    // for status, active is default and cant be modified in adding.
                    case "status":
                        echo '<td><input class="addInputs noBorder" type="text" name="status" value="active" disabled></td>';
                        break;
                    // not supported yet
                    case "profilePicture":
                        echo '<td><input class="addInputs noBorder" type="text" name="profilePicture" value="----------" disabled></td>';
                        break;
                    // 0 at the beginning 
                    case "failedLoginAttempts":
                        echo '<td><input class="addInputs noBorder" type="text" name="failedLoginAttempts" value="0" disabled></td>';
                        break;
                    // last login at also not inputtable by admin when adding
                    case "lastLoginAt":
                        echo '<td><input class="addInputs noBorder" type="text" name="lastLoginAt" value="----------" disabled></td>';
                        break;
                    // last activity at when user first logs in, created at when query is sent, updated at whenever the row is updated
                    case "lastActivityAt":
                        echo '<td><input class="addInputs noBorder" type="text" name="lastActivityAt" value="----------" disabled></td>';
                        break;
                    case "createdAt":
                        echo '<td><input class="addInputs noBorder" type="text" name="createdAt" value="----------" disabled></td>';
                        break;
                    case "updatedAt":
                        echo '<td><input class="addInputs noBorder" type="text" name="updatedAt" value="----------" disabled></td>';
                        break;
                    default:
                        echo '<td><input class="addInputs" id="' . $columns[$i] . '"></td>';
                        break;
                }
            }
            echo "</tr>";
        }
        

        // end table
        echo "</table>";

        // free resources
        $colResult->free();
        $result->free();
        $conn->close();
    } 
    catch (Exception $e) {
        error_log($e->getMessage());
    }
}

function displayTableNamesDropdown(): void{
    try {
        // get the conn object with the connection to the database and the specified table
        $conn = getDatabaseConnection();

        // check if it was successful
        if ($conn === false) {
            throw new Exception("Failed to connect to database.");
        }

        // query to show tables
        $query = "SHOW TABLES";
        $result = $conn->query($query);

        // check if the query was successful
        if (!$result){
            throw new Exception("Failed to get tables: " . $conn->error);
        }

        // check if the result is empty
        if ($result->num_rows === 0) {
            throw new Exception("No tables found in database.");
        }

        // while loop to print the options
        while ($opt = $result->fetch_row()) {
            echo "<option value='" . htmlspecialchars($opt[0]) . "'>" . htmlspecialchars($opt[0]) . "</option>";
        }
    }
    catch (Exception $e) {
        // error log
        error_log($e->getMessage());
    }
}