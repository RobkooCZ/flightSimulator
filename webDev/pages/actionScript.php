<?php
/**
 * Action ScripAddresst
 *
 * Handles AJAX actions for user and table management (add, edit, delete, etc.).
 * Processes POST requests for admin/school admin interfaces.
 *
 * @file actionScripAddresst.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.10
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
                $bio = htmlspecialchars(urldecode($_POST['bio']));
                $ipAddress = htmlspecialchars(urldecode($_POST['ipAddress']));
                $password = htmlspecialchars(urldecode($_POST['password']));
                $role = htmlspecialchars(urldecode($_POST['role']));

                // Check if the table exists; if it doesn't, throw an exception
                if (!$db->tableExists($tableIdentifier)){
                    throw new DatabaseException(
                        "Table '{$tableIdentifier}' does not exist."
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
                    ':bio' => $bio,
                    ':ipAddress' => $ipAddress,
                    ':password' => $passwordHash,
                    ':salt' => $salt,
                    ':role' => $role,
                    ':status' => "active" // Default active
                ];

                // Execute query; if it fails, throw an exception
                if (!$db->execute(
                    "INSERT INTO users (username, bio, ipAddress, password, salt, role, status, lastActivityAt, createdAt, updatedAt) 
                    VALUES (:username, :bio, :ipAddress, :password, :salt, :role, :status, NOW(), NOW(), NOW())",
                    $parameters
                )){
                    throw new DatabaseException(
                        "Failed to execute INSERT INTO statement.",
                        500 // Internal Server Error
                    );
                }
                
                echo json_encode(['success' => true, 'message' => 'Record added successfully']);
                break;

            case 'edit':
                // Get data for editing
                $tableIdentifier = htmlspecialchars(urldecode($_POST['tableName']));
                $username = htmlspecialchars(urldecode($_POST['username']));
                $bio = htmlspecialchars(urldecode($_POST['bio']));
                $ipAddress = htmlspecialchars(urldecode($_POST['ipAddress']));
                $password = htmlspecialchars(urldecode($_POST['password']));
                $role = htmlspecialchars(urldecode($_POST['role']));
                $id = htmlspecialchars(urldecode($_POST['id']));

                // Check if the table exists
                if (!$db->tableExists($tableIdentifier)){
                    throw new DatabaseException(
                        "Table '{$tableIdentifier}' does not exist."
                    );
                }

                // Prepare parameter array
                $parameters = [
                    ':username' => $username,
                    ':bio' => $bio,
                    ':ipAddress' => $ipAddress,
                    ':role' => $role,
                    ':id' => $id
                ];

                // Build UPDATE query
                $sql = "UPDATE {$tableIdentifier} SET username = :username, bio = :bio, ipAddress = :ipAddress, role = :role, updatedAt = NOW()";

                // Only update password if provided
                if (!empty($password)){
                    // Generate new salt and hash password
                    $salt = bin2hex(random_bytes(16));
                    $passwordHash = password_hash($password . $salt, PASSWORD_DEFAULT);
                    
                    if ($passwordHash === false){
                        throw new PHPException(
                            "Password hashing failed.",
                            500
                        );
                    }
                    
                    $sql .= ", password = :password, salt = :salt";
                    $parameters[':password'] = $passwordHash;
                    $parameters[':salt'] = $salt;
                }

                $sql .= " WHERE id = :id";

                // Execute the UPDATE query
                if (!$db->execute($sql, $parameters)){
                    throw new DatabaseException(
                        "Failed to execute UPDATE statement for record with ID: {$id}",
                        500
                    );
                }

                echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
                break;

            case 'delete':
                // Get data for deletion
                $tableIdentifier = htmlspecialchars(urldecode($_POST['tableName']));
                $id = htmlspecialchars(urldecode($_POST['id']));

                // Check if the table exists
                if (!$db->tableExists($tableIdentifier)){
                    throw new DatabaseException(
                        "Table '{$tableIdentifier}' does not exist."
                    );
                }

                // Prepare parameter array
                $parameters = [
                    ':id' => $id
                ];

                // Execute the DELETE query
                if (!$db->execute(
                    "DELETE FROM {$tableIdentifier} WHERE id = :id",
                    $parameters
                )){
                    throw new DatabaseException(
                        "Failed to execute DELETE statement for record with ID: {$id}",
                        500
                    );
                }

                // Update the AUTO_INCREMENT value
                $maxIdResult = $db->query("SELECT MAX(id) as maxId FROM {$tableIdentifier}");
                $maxId = $maxIdResult[0]['maxId'];

                // set the new auto increment accordingly. either 1 if no max id is found, or the max id + 1
                if ($maxId === null) $newAutoIncrement = 1;
                else $newAutoIncrement = (int)$maxId + 1;

                // execute ALTER TABLE to change AUTO_INCREMENT
                $db->execute("ALTER TABLE {$tableIdentifier} AUTO_INCREMENT = {$newAutoIncrement}");

                echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
                break;

            default:
                throw new AppException("Invalid action: {$action}");
        }
    }
}