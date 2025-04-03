<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // if the requested data is set
    if (isset($_POST['action']) && isset($_POST['tableName']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['role'])) {
        // get the action
        $action = $_POST['action'];

        // switch to decide what to do based on action
        switch ($action){
            // admin wants to add a user to the db
            case 'add':
                try {
                    // get data
                    $tableIdentifier = htmlspecialchars(urldecode($_POST['tableName']));
                    $username = htmlspecialchars(urldecode($_POST['username']));
                    $password = htmlspecialchars(urldecode($_POST['password']));
                    $role = htmlspecialchars(urldecode($_POST['role']));

                    // get connection to db
                    include_once __DIR__ . './../../config/db.php';

                    // check if the table name is valid (if valid, returns the table name, if not, returns an error code)
                    $tableName = checkForTable($tableIdentifier);

                    // check if it was successfully found
                    if ($tableName === 'TABLE_NOT_FOUND') {
                        throw new Exception("Table {$tableIdentifier} was not found.");
                    }

                    if ($tableName === 'TABLE_DOES_NOT_EXIST') {
                        throw new Exception("Table {$tableIdentifier} does not exist.");
                    }

                    // get the connection to the table
                    $conn = getDatabaseAndTableConnection($tableName); // get the connection to the users table (to login)

                    // check if the connection was successful
                    if (!$conn){
                        throw new Exception("Connection to the table {$tableName} failed.");
                    }

                    // generate salt
                    $salt = bin2hex(random_bytes(16)); // 32 characters long

                    // hash the password with the salt
                    $passwordHash = password_hash($password . $salt, PASSWORD_DEFAULT);
                    
                    // check if the password was hashed successfully
                    if ($passwordHash === 'FALSE') {
                        throw new Exception("Password hashing failed.");
                    }

                    // prepare the query
                    $stmt = $conn->prepare("INSERT INTO {$tableName} (username, password, salt, role, status, lastActivityAt, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())");

                    // check if the statement was prepared successfully
                    if (!$stmt) {
                        throw new Exception("Prepare statement failed: " . $conn->error);
                    }

                    // bind the parameters
                    $status = 'active'; // explicitly define the status value
                    $stmt->bind_param('sssss', $username, $passwordHash, $salt, $role, $status); // s = string

                    // execute the statement
                    if (!$stmt->execute()) {
                        throw new Exception("Execute statement failed: " . $stmt->error);
                    }

                    // free up resources
                    $stmt->close();
                }
                catch (Exception $e){
                    error_log("Error: " . $e->getMessage());
                    echo "Error: " . $e->getMessage();
                }
                break;
        }
    }
}