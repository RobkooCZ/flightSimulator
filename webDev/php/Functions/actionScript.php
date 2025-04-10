<?php

// include db
use WebDev\config\Database;

// get a file wide db conn
$db = Database::getInstance();

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

                    // check if table exists, if it doesnt, throw expection
                    if (!$db->tableExists($tableIdentifier)){
                        throw new Exception("Table {$tableIdentifier} doesn't exist.");
                    }

                    // generate salt
                    $salt = bin2hex(random_bytes(16)); // 32 characters long

                    // hash the password with the salt
                    $passwordHash = password_hash($password . $salt, PASSWORD_DEFAULT);
                    
                    // check if the password was hashed successfully
                    if ($passwordHash === 'FALSE') {
                        throw new Exception("Password hashing failed.");
                    }

                    // prepare param array
                    $parameters = [
                        ':username' => $username,
                        ':password' => $passwordHash,
                        ':salt' => $salt,
                        ':role' => $role,
                        ':status' => "active" // default active
                    ];

                    // execute query. if it fails, throw an exception
                    if (!$db->execute("INSERT INTO users (username, password, salt, role, status, lastActivityAt, createdAt, updatedAt) VALUES (:username, :password, :salt, :role, :status, NOW(), NOW(), NOW())", $parameters)){
                        throw new Exception("Failed to execute INSERT INTO statement.");
                    }
                }
                catch (Exception $e){
                    error_log("Error: " . $e->getMessage());
                    echo "Error: " . $e->getMessage();
                }
                break;
        }
    }
}