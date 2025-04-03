<?php
// File to connect to the (local for now) database 

require __DIR__ . './../vendor/autoload.php';
use Dotenv\Dotenv;

// start session if requested for $_SESSION['id']
if ($startSession === true){
    session_start();
}

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function getNextId(string $tableIdentifier): int{
    // get db connection 
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
            #       FETCH LAST ID FROM TABLE      #
            #                                     #
            #######################################
        */

        // query and result
        $idQuery = "SELECT MAX(id) FROM {$tableName}";
        $queryResult = $conn->query($idQuery);

        if ($queryResult === false) {
            throw new Exception("Failed to execute query {$idQuery}: " . $conn->error);
        }

        // return the next usable id
        return $queryResult->fetch_row()[0] + 1;
    }
    catch (Exception $e) {
        // log the error message
        error_log($e->getMessage());

        // display the error message to the user
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";

        // throw the exception
        return -1; // return -1 to indicate that the connection failed
    }
}

function getRolesDropdown(string $tableIdentifier): array{
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
            #       FETCH LAST ID FROM TABLE      #
            #                                     #
            #######################################
        */

        // Query to fetch ENUM values
        $roleQuery = "SHOW COLUMNS FROM {$tableName} LIKE 'role'";
        $queryResult = $conn->query($roleQuery);

        if ($queryResult === false) {
            throw new Exception("Failed to execute query: " . $conn->error);
        }

        // Fetch the row containing the 'role' column definition
        $row = $queryResult->fetch_row();
        if (!$row) {
            throw new Exception("Failed to retrieve 'role' column definition.");
        }

        // Extract ENUM values from 'Type'
        preg_match("/^enum\((.*)\)$/", $row[1], $matches);
        if (!isset($matches[1])) {
            throw new Exception("Failed to parse ENUM values for 'role' column.");
        }

        // Convert ENUM values into an array
        $enumValues = str_getcsv($matches[1], ",", "'");

        // Filter out excluded roles based on user permissions
        $excludedArr = match ($_SESSION['id'] ?? null) {
            1 => ['owner'],
            2 => ['owner', 'coOwner'],
            default => ['owner', 'coOwner', 'admin', 'deleted']
        };

        // Populate the full array with allowed roles
        $retArr = array_diff($enumValues, $excludedArr);

        // Return the array of allowed ENUM values
        return $retArr;
    }
    catch (Exception $e) {
        // log the error message
        error_log($e->getMessage());

        // display the error message to the user
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";

        // throw the exception
        $arr[1] = -1;
        return $arr; // return -1 to indicate that the connection failed
    }
}
function checkForTable(string $tableIdentifier): string {
    $retStr = ''; // return string

    // check if table we want to use exists
    $retStr = match ($tableIdentifier) {
        'users' => $_ENV['DB_USER_TABLE'] ?? 'TABLE_DOES_NOT_EXIST',
        'userPreferences' => $_ENV['DB_USER_PREFERENCES_TABLE'] ?? 'TABLE_DOES_NOT_EXIST',
        'userLogs' => $_ENV['DB_USER_LOGS_TABLE'] ?? 'TABLE_DOES_NOT_EXIST',
        'leaderboards' => $_ENV['DB_LEADERBOARD_TABLE'] ?? 'TABLE_DOES_NOT_EXIST',
        default => 'TABLE_NOT_FOUND',
    };

    // return the result of the match statement
    return $retStr;
}

function getDatabaseConnection(): mysqli|false {
    try { 
        // Assign env variables to local variables
        $dbHost = $_ENV['DB_HOST'] ?? null;
        $dbName = $_ENV['DB_NAME'] ?? null;
        $dbUser = $_ENV['DB_USER'] ?? null;
        $dbPass = $_ENV['DB_PASS'] ?? null;

        // Check if all required variables are set
        $missing = [];
        if (!$dbHost) $missing[] = 'DB_HOST';
        if (!$dbName) $missing[] = 'DB_NAME';
        if (!$dbUser) $missing[] = 'DB_USER';
        if (!$dbPass) $missing[] = 'DB_PASS';

        if (!empty($missing)) {
            throw new Exception("Missing environment variables: " . implode(', ', $missing));
        }

        // Connect to database
        $conn = new mysqli($dbHost, $dbUser, $dbPass);

        if ($conn->connect_error) {
            throw new Exception("Failed to connect to database: " . $conn->connect_error);
        }

        // Select the database
        if (!$conn->select_db($dbName)) {
            throw new Exception("Failed to select database {$dbName}: " . $conn->error);
        }

        // return conn object
        return $conn;
    }   
    catch(Exception $e) {
        // log the error message
        error_log($e->getMessage());

        // display the error message to the user
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";

        // throw the exception
        return false; // return false to indicate that the connection failed
    } 
}

/**
 * Establishes a connection to a MySQL database and verifies the existence of a specific table.
 *
 * This function loads environment variables to retrieve database credentials, connects to the database,
 * selects the specified database, and checks if the given table exists. If any step fails, an exception
 * is thrown, and the error is logged and displayed to the user.
 *
 * @param string $tableIdentifier The identifier used to locate or verify the table name.
 * 
 * @return mysqli|false Returns a `mysqli` connection object if successful, or `false` if the connection fails.
 *
 * @throws Exception If any of the following conditions occur:
 * - Required environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) are missing.
 * - Database connection fails.
 * - Database selection fails.
 * - The table identifier cannot be resolved to a valid table name.
 * - The table does not exist in the database.
 * - A query to check the table's existence fails.
 *
 * Environment Variables:
 * - `DB_HOST`: The hostname of the database server.
 * - `DB_NAME`: The name of the database to connect to.
 * - `DB_USER`: The username for database authentication.
 * - `DB_PASS`: The password for database authentication.
 *
 * Error Handling:
 * - Logs error messages using `error_log()`.
 * - Displays error messages to the user in a styled `<p>` tag.
 *
 * Usage Example:
 * ```php
 * $connection = getDatabaseAndTableConnection('users');
 * if ($connection === false) {
 *     // Handle connection failure
 * } else {
 *     // Proceed with database operations
 * }
 * ```
 */
function getDatabaseAndTableConnection(string $tableIdentifier): mysqli|false {
    try {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Assign env variables to local variables
        $dbHost = $_ENV['DB_HOST'] ?? null;
        $dbName = $_ENV['DB_NAME'] ?? null;
        $dbUser = $_ENV['DB_USER'] ?? null;
        $dbPass = $_ENV['DB_PASS'] ?? null;

        // Check if all required variables are set
        $missing = [];
        if (!$dbHost) $missing[] = 'DB_HOST';
        if (!$dbName) $missing[] = 'DB_NAME';
        if (!$dbUser) $missing[] = 'DB_USER';
        if (!$dbPass) $missing[] = 'DB_PASS';

        if (!empty($missing)) {
            throw new Exception("Missing environment variables: " . implode(', ', $missing));
        }

        // Connect to database
        $conn = new mysqli($dbHost, $dbUser, $dbPass);

        if ($conn->connect_error) {
            throw new Exception("Failed to connect to database: " . $conn->connect_error);
        }

        // Select the database
        if (!$conn->select_db($dbName)) {
            throw new Exception("Failed to select database {$dbName}: " . $conn->error);
        }

        // Get the table name
        $tableName = checkForTable($tableIdentifier);

        if ($tableName === 'TABLE_NOT_FOUND') {
            throw new Exception("Table {$tableIdentifier} was not found.");
        }

        if ($tableName === 'TABLE_DOES_NOT_EXIST') {
            throw new Exception("Table {$tableIdentifier} does not exist.");
        }

        // verify table exists
        $query = "SHOW TABLES LIKE '{$tableName}'";

        // execute query and check if it was successful
        $result = $conn->query($query);

        // failed to execute query
        if (!$result) {
            throw new Exception("Failed to check if table exists: " . $conn->error);
        }

        // check if table exists
        if ($result->num_rows === 0) {
            throw new Exception("Table `{$tableName}` does not exist.");
        }

        // return the conn obj
        return $conn;
    } 
    catch (Exception $e) {
        // log the error message
        error_log($e->getMessage());

        // display the error message to the user
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";

        // throw the exception
        return false; // return false to indicate that the connection failed
    }
}

?>