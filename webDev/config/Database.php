<?php

namespace WebDev\config;

use PDO;
use PDOException;
use Exception;
use Dotenv\Dotenv;

class Database {
    private static ?Database $instance = null;
    private ?PDO $conn = null;

    /**
     * Private constructor for initializing the database connection.
     * 
     * This method performs the following steps:
     * 1. Loads environment variables from a `.env` file located in the parent directory.
     * 2. Retrieves database configuration values (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) from the environment.
     * 3. Validates that all required database configuration values are present.
     * 4. Constructs a DSN (Data Source Name) string for connecting to a MySQL database.
     * 5. Attempts to establish a PDO connection to the database with the provided credentials.
     * 
     * If any required configuration is missing, an exception is thrown.
     * If the database connection fails, an error is logged, and an error message is displayed.
     * 
     * PDO options used:
     * - `PDO::ATTR_ERRMODE`: Sets error reporting mode to throw exceptions (`PDOException`).
     * - `PDO::ATTR_DEFAULT_FETCH_MODE`: Sets the default fetch mode to return results as an associative array.
     * - `PDO::ATTR_EMULATE_PREPARES`: Disables emulated prepared statements, enforcing real prepared statements.
     * 
     * @throws Exception If any required database configuration is missing.
     */
    private function __construct(){
        // Load .env variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // load necessary data from the .env file
        $dbHost = $_ENV['DB_HOST'] ?? null;
        $dbName = $_ENV['DB_NAME'] ?? null;
        $dbUser = $_ENV['DB_USER'] ?? null;
        $dbPass = $_ENV['DB_PASS'] ?? null;

        // get an array of missing values
        $missing = array_filter([
            'DB_HOST' => $dbHost,
            'DB_NAME' => $dbName,
            'DB_USER' => $dbUser,
            'DB_PASS' => $dbPass
        ], fn($value) => !$value);

        // if anything is missing, throw an expection that displays all the missing key variables
        if (!empty($missing)) {
            throw new Exception("Missing environment variables: " . implode(', ', array_keys($missing)));
        }

        // DSN - Data Source Name
        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

        try {
            $this->conn = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // set error reporting mode to throwing exceptions (PDOException)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // set the default fetch mode for queries to return an associative array
                PDO::ATTR_EMULATE_PREPARES => false // disable emulated prepared statements, forcing you to use real prepared statements
            ]);
        }
        catch (PDOException $e){
            error_log("Database connection error: " . $e->getMessage());
        }
    }

    /**
     * Retrieves the singleton instance of the Database class.
     * 
     * This method ensures that only one instance of the Database class
     * is created during the application's lifecycle. If the instance
     * does not already exist, it initializes a new one.
     * 
     * @return Database The singleton instance of the Database class.
     */
    public static function getInstance(): Database {
        // Check if the instance is null (not yet created)
        if (self::$instance === null){
            // Create a new instance of the Database class
            self::$instance = new Database();
        }
        // Return the existing or newly created instance
        return self::$instance;
    }

    /**
     * Retrieves the PDO database connection instance.
     * 
     * This method provides access to the PDO connection object
     * that was established during the initialization of the `Database` class.
     * 
     * @return PDO The PDO connection instance.
     */
    public function getConnection(): PDO {
        return $this->conn;
    }

    /**
     * Executes a SQL query and retrieves the results as an array.
     * 
     * This method prepares and executes a SQL query with optional parameters.
     * It fetches all rows from the result set and returns them as an associative array.
     * Use this if you need to retrieve data (e.g. `SELECT ...` from the database.)
     * 
     * ### Example usage:
     * 
     * `query("SELECT * FROM $this->tableName");`
     * 
     * `query();`
     * 
     * @param string $sql The SQL query to execute.
     * @param array $parameters Optional parameters to bind to the query.
     * @return array The result set as an associative array, or an empty array on failure.
     */
    public function query(string $sql, array $parameters = []): array {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($parameters);
            return $stmt->fetchAll();
        }
        catch (PDOException $e){
            error_log("Query error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Executes a SQL statement without returning a result set.
     * 
     * This method prepares and executes a SQL statement with optional parameters.
     * It is typically used for operations like INSERT, UPDATE, or DELETE.
     * 

     * @param string $sql The SQL statement to execute.
     * @param array $params Optional parameters to bind to the statement.
     * @return bool True on success, or false on failure.
     */
    public function execute(string $sql, array $params = []): bool {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } 
        catch (PDOException $e) {
            error_log("Execution error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Executes a SQL statement without returning a result set.
     * 
     * ### Example usage:
     * 
     * ```php
     * $db = Database::getInstance();
     * $db->execute("INSERT INTO users (username, password) VALUES (:username, :password)", 
     * [
     *     'username' => 'testuser',
     *     'password' => 'hashedpassword'
     * ]
     * );
     * ```
     * 
     * @param string $sql The SQL statement to execute.
     * @param array $params Optional parameters to bind to the statement.
     * @return bool True on success, or false on failure.
     */
    public function tableExists(string $tableName): bool {
        try {
            $quotedTable = $this->conn->quote($tableName);
            $sql = "SHOW TABLES LIKE $quotedTable";
            $stmt = $this->conn->query($sql);
            // If any rows are returned, the table exists
            return $stmt->rowCount() > 0;
        } 
        catch (PDOException $e) {
            error_log("Error checking if table exists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves a list of all table names in the database.
     *
     * @return array|false An array of table names, or false on failure.
     */
    public function getTableNames(): array|false {
        try {
            // Query to fetch all table names
            $result = $this->query("SHOW TABLES");

            // Check if the result is empty
            if (empty($result)) {
                throw new Exception("No tables found in the database.");
            }

            // Extract table names from the result
            return array_map(fn($row) => reset($row), $result); // Get the first column of each row
        } 
        catch (Exception $e) {
            // Log the error and return false
            error_log("Error fetching table names: " . $e->getMessage());
            return false;
        }
    }
}