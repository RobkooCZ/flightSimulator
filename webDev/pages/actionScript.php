<?php
/**
 * Action Script
 *
 * Handles AJAX actions for user and table management (add, edit, delete, etc.).
 * Processes POST requests for admin/school admin interfaces.
 *
 * @file actionScript.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.3
 * @see Database, User, AppException, DatabaseException, PHPException
 * @todo Add more actions (edit, delete), validation, and error handling
 */

use WebDev\Bootstrap;
Bootstrap::init();

use WebDev\Auth\User;
use WebDev\Database\Database;

// custom exceptions
use WebDev\Exception\AppException;
use WebDev\Exception\DatabaseException;
use WebDev\Exception\PHPException;

// load the appexception class and all its subclasses
AppException::init();

// global handler for any thrown exceptions
set_exception_handler(function (Throwable $ae){
    if (AppException::globalHandle($ae)){ // appException or its subclasses
        // error page or smth would go here (todo) for now just exit the script
        exit;
    }
    else { // anything but appException and its subclasses
        error_log($ae->getMessage()); // temporary
    }
});

// get a file wide db conn
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    // someone has submitted a form
    $user = User::current();
    // if the user is logged in (not null) record the activity
    if ($user) $user->recordActivity();

    // if the requested data is set
    if (isset($_POST['action']) && isset($_POST['tableName']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['role'])){
        // get the action
        $action = $_POST['action'];

        // switch to decide what to do based on action
        switch ($action){
            // admin wants to add a user to the db
            case 'add':
                // Get data
                $tableIdentifier = htmlspecialchars(urldecode($_POST['tableName']));
                $username = htmlspecialchars(urldecode($_POST['username']));
                $password = htmlspecialchars(urldecode($_POST['password']));
                $role = htmlspecialchars(urldecode($_POST['role']));

                // Check if the table exists; if it doesn't, throw an exception
                if (!$db->tableExists($tableIdentifier)){
                    throw new DatabaseException(
                        "Table '{$tableIdentifier}' doesn't exist.",
                        400 // Bad Request
                    );
                }

                // Generate salt
                $salt = bin2hex(random_bytes(16)); // 32 characters long

                // Hash the password with the salt
                $passwordHash = password_hash($password . $salt, PASSWORD_DEFAULT);

                // Check if the password was hashed successfully
                if ($passwordHash === false){
                    throw new PHPException(
                        "Password hashing failed.",
                        500 // Internal Server Error
                    );
                }

                // Prepare parameter array
                $parameters = [
                    ':username' => $username,
                    ':password' => $passwordHash,
                    ':salt' => $salt,
                    ':role' => $role,
                    ':status' => "active" // Default active
                ];

                // Execute query; if it fails, throw an exception
                if (!$db->execute(
                    "INSERT INTO users (username, password, salt, role, status, lastActivityAt, createdAt, updatedAt) 
                    VALUES (:username, :password, :salt, :role, :status, NOW(), NOW(), NOW())",
                    $parameters
                )){
                    throw new DatabaseException(
                        "Failed to execute INSERT INTO statement.",
                        500 // Internal Server Error
                    );
                }
                break;
        }
    }
}