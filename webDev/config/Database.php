<?php
declare(strict_types=1);

namespace WebDev\config;

use PDO;
use PDOException;
use Exception;
use Dotenv\Dotenv;
use WebDev\Functions\LogicException;
use WebDev\Functions\AppException;
use WebDev\Functions\ConfigurationException;
use WebDev\Functions\DatabaseException;
// use WebDev\Functions\ExceptionType;

class Database {
    private static ?Database $instance = null;
    private ?PDO $conn = null;

    /**
     * Prevent unserialize attacks.
     * 
     * This method prevents the unserialization of the singleton instance,
     * ensuring that the class cannot be instantiated through unserialization.
     * 
     * @throws LogicException Always throws a LogicException with a reason indicating
     *                        that unserializing the singleton would violate the singleton pattern.
     */
    public function __wakeup(): never {
        throw new LogicException(message: "Cannot unserialize singleton.", reason: "Would violate the singleton pattern.");
    }

    /**
     * Prevent cloning of the singleton instance.
     * 
     * This method ensures that the singleton instance cannot be cloned,
     * maintaining the integrity of the singleton pattern.
     * 
     * @throws LogicException Always throws a LogicException with a reason indicating
     *                        that cloning the singleton would violate the singleton pattern.
     */
    public function __clone(): void {
        throw new LogicException(message: "Cannot clone singleton", reason: "Would violate the singleton pattern.");
    }

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
     * ### Exceptions:
     * - If any required configuration is missing, a `ConfigurationException` is thrown.
     * - If the database connection fails, a `DatabaseException` is thrown.
     * 
     * ### PDO Options Used:
     * - `PDO::ATTR_ERRMODE`: Sets error reporting mode to throw exceptions (`PDOException`).
     * - `PDO::ATTR_DEFAULT_FETCH_MODE`: Sets the default fetch mode to return results as an associative array.
     * - `PDO::ATTR_EMULATE_PREPARES`: Disables emulated prepared statements, enforcing real prepared statements.
     * - `PDO::ATTR_TIMEOUT`: Sets a 5-second timeout to prevent hanging connections.
     * 
     * @throws ConfigurationException If any required database configuration is missing.
     * @throws DatabaseException If the database connection fails.
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
        if (!empty($missing)){
            throw new ConfigurationException(
                "Missing environment variables. Please check your .env file.",
                500, // HTTP status code for internal server error
                null, // No previous exception
                implode(', ', array_keys($missing)), // The missing configuration keys
                ".env", // The source of the configuration
                "All required environment variables must be set.", // Expected value or explanation
                __DIR__ . '/../.env' // Path to the .env file
            );
        }

        // DSN - Data Source Name
        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

        try {
            $this->conn = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // set error reporting mode to throwing exceptions (PDOException)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // set the default fetch mode for queries to return an associative array
                PDO::ATTR_EMULATE_PREPARES => false, // disable emulated prepared statements, forcing you to use real prepared statements
                PDO::ATTR_TIMEOUT => 5 // 5-second timeout to prevent hanging connections
            ]);
        }
        catch (PDOException $e){
            throw new DatabaseException(
                "Failed to establish a database connection: " . $e->getMessage(),
                $e->getCode(),
                $e // Pass the original exception for context
            );
        }
    }

    /**
     * Destructor method to clean up resources.
     * 
     * This method is automatically called when the object is destroyed.
     * It ensures that the database connection is closed to free up resources.
     */
    public function __destruct(){
        $this->close();
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
     * Validates the parameters to ensure they are of acceptable types.
     * 
     * This method checks each parameter in the provided array to ensure
     * that it is not an array or an object. If any parameter fails this
     * validation, an exception is thrown with the corresponding key.
     * 
     * ### Example usage:
     * 
     * ```php
     * use WebDev\Functions\Database;
     * $db = Database::getInstance();
     * 
     * $db->validateParameters(['id' => 1, 'name' => 'John']); // Valid
     * $db->validateParameters(['data' => [1, 2, 3]]); // Throws Exception
     * ```
     * 
     * @param array $parameters The array of parameters to validate.
     * 
     * @throws DatabaseException If any parameter is an array or an object.
     */
    private function validateParameters(array $parameters): void {
        foreach ($parameters as $key => $value){
            // if the value is array or object, throw exception
            if (is_array($value) || is_object($value)){
                throw new DatabaseException(
                    "Invalid parameter type for key: '$key'. Expected scalar values (int, float, string, or bool), but received " . gettype($value) . ".",
                    400 // HTTP status code for bad request
                );
            }
        }
    }

    /**
     * Escapes a database identifier (e.g., table or column name) to prevent SQL injection.
     *
     * This method ensures that the provided identifier is valid and safely quoted
     * for use in SQL queries. It performs basic validation to allow only alphanumeric
     * characters and underscores in the identifier.
     * 
     * ### Examples:
     * 
     * #### Example 1
     * - INPUT: table
     * - OUTPUT: \`table\` 
     * 
     * #### Example 2
     * - INPUT: column_name
     * - OUTPUT: \`column_name\`
     * 
     * #### Example 3
     * - INPUT: invalid-name  
     * - THROWS EXCEPTION: "Invalid identifier."
     *
     * @param string $identifier The database identifier to escape.
     * 
     * @return string The escaped and quoted identifier, safe for use in SQL queries.
     * 
     * @throws DatabaseException If the identifier contains invalid characters.
     */
    public function escapeIdentifier(string $identifier): string {
        // basic validation for now
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)){
            throw new DatabaseException(
                "Invalid identifier: '$identifier'. Identifiers must only contain alphanumeric characters or underscores.",
                400 // HTTP status code for bad request
            );
        }

        // return the quoted identifier
        return "`" . $identifier . "`";
    }

    /**
     * Executes a SQL query and retrieves the results as an array.
     * 
     * This method prepares and executes a SQL query with optional parameters.
     * It fetches all rows from the result set and returns them as an associative array.
     * Use this if you need to retrieve data (e.g., `SELECT ...` from the database).
     * 
     * ### Example usage:
     * 
     * ```php
     * $db = Database::getInstance();
     * $result = $db->query("SELECT * FROM users WHERE id = :id", ['id' => 1]);
     * ```
     * 
     * ### Example output:
     * ```php
     * [
     * ····[
     * ········'id' => 1,
     * ········'username' => 'testuser',
     * ········'email' => 'test@example.com',
     * ····],
     * ····[
     * ········'id' => 2,
     * ········'username' => 'anotheruser',
     * ········'email' => 'another@example.com',
     * ····],
     * ]
     * ```
     * 
     * You would **access** the first row's username as follows: `$username = $result[0]['username'];`
     * 
     * @param string $sql The SQL query to execute.
     * @param array $parameters Optional parameters to bind to the query.
     * @return array The result set as an associative array, or an empty array on failure.
     * @throws DatabaseException If any part of the query process fails.
     */
    public function query(string $sql, array $parameters = []): array {
        try {
            // Check if there is a connection to the database established
            if ($this->conn === null){
                throw new DatabaseException(
                    "Database connection not established. Ensure the connection is initialized before executing queries.",
                    500 // Internal Server Error
                );
            }
    
            // Validate the parameters to ensure they are of acceptable types
            $this->validateParameters($parameters);
    
            // Prepare the SQL statement
            $statement = $this->conn->prepare($sql);
    
            // Check if the statement preparation failed
            if ($statement === false){
                throw new DatabaseException(
                    "Failed to prepare the SQL statement: '$sql'. Parameters: " . json_encode($parameters),
                    500 // Internal Server Error
                );
            }
    
            // Execute the SQL statement
            if (!$statement->execute($parameters)){
                throw new DatabaseException(
                    "Failed to execute the SQL query: '$sql'. Parameters: " . json_encode($parameters),
                    500 // Internal Server Error
                );
            }
    
            // Fetch and return the results as an associative array
            return $statement->fetchAll();
        } 
        catch (PDOException $e){
            // Wrap the PDOException in a DatabaseException for consistency
            throw new DatabaseException(
                "Database query failed: " . $e->getMessage(),
                $e->getCode(),
                $e // Pass the original exception for context
            );
        }
    }

    /**
     * Executes a SQL statement without returning a result set.
     * 
     * This method prepares and executes a SQL statement with optional parameters.
     * It is typically used for operations like INSERT, UPDATE, or DELETE.
     * 
     * ### Example usage:
     * 
     * ```php
     * use WebDev\Functions\Database;
     * $db = Database::getInstance();
     * $success = $db->execute(
     *     "INSERT INTO table (column1, column2) VALUES (:value1, :value2)",
     *     [
     *         ':value1' => $value1,
     *         ':value2' => $value2
     *     ]
     * );
     * if ($success){
     *     echo "Query executed successfully.";
     * } else {
     *     echo "Failed to execute query.";
     * }
     * ```
     * 
     * @param string $sql The SQL statement to execute.
     * @param array $parameters Optional associative array of parameters to bind to the statement.
     *                       Keys should match the named placeholders in the SQL statement.
     * @return bool True on success, or false on failure (e.g., invalid SQL or execution error).
     * @throws DatabaseException If the connection is not established, the statement preparation fails, or execution fails.
     */
    public function execute(string $sql, array $parameters = []): bool {
        try {
            // Check if there is a connection to the database established
            if ($this->conn === null){
                throw new DatabaseException(
                    "Database connection not established. Ensure the connection is initialized before executing queries.",
                    500 // Internal Server Error
                );
            }

            // Validate the parameters to ensure they are of acceptable types
            $this->validateParameters($parameters);

            // Prepare the SQL statement
            $statement = $this->conn->prepare($sql);

            // Check if the statement preparation failed
            if ($statement === false){
                throw new DatabaseException(
                    "Failed to prepare the SQL statement: '$sql'. Parameters: " . json_encode($parameters),
                    500 // Internal Server Error
                );
            }

            // Execute the SQL statement
            if (!$statement->execute($parameters)){
                throw new DatabaseException(
                    "Failed to execute the SQL statement: '$sql'. Parameters: " . json_encode($parameters),
                    500 // Internal Server Error
                );
            }

            // Return true on successful execution
            return true;
        } 
        catch (PDOException $e){
            throw new DatabaseException(
                "Database query execution failed: " . $e->getMessage(),
                $e->getCode(),
                $e // Pass the original exception for context
            );
        }
    }

    /**
     * Checks if a table exists in the database.
     *
     * This method verifies the existence of a table in the database by querying
     * the information schema. It ensures that the table name is properly validated
     * to prevent SQL injection.
     *
     * ### Example usage:
     * 
     * ```php
     * use WebDev\Functions\Database;
     * $db = Database::getInstance();
     * $exists = $db->tableExists('users');
     * 
     * if ($exists){
     *     echo "The table exists.";
     * } 
     * else {
     *     echo "The table does not exist.";
     * }
     * ```
     *
     * @param string $tableName The name of the table to check.
     * @return bool True if the table exists, false otherwise.
     * @throws DatabaseException If the database connection is not established, the table name is invalid, or the query fails.
     */
    public function tableExists(string $tableName): bool {
        try {
            // Check if there is a connection to the database established
            if ($this->conn === null){
                throw new DatabaseException(
                    "Database connection not established. Ensure the connection is initialized before checking table existence.",
                    500
                );
            }

            // Validate the table name
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)){
                throw new DatabaseException(
                    "Invalid table name: '$tableName'. Table names must only contain alphanumeric characters or underscores.",
                    400
                );
            }

            // Prepare the SQL statement
            $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tableName";
            $statement = $this->conn->prepare($sql);

            // Execute the query
            $statement->execute([':tableName' => $tableName]);

            // Return true if the table exists, false otherwise
            return $statement->rowCount() > 0;
        } 
        catch (PDOException $e){
            throw new DatabaseException(
                "Error checking if table exists: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Retrieves a list of all table names in the database.
     *
     * This method queries the database to fetch the names of all tables
     * currently present. It ensures that the database connection is established
     * before executing the query.
     *
     * ### Example usage:
     * 
     * ```php
     * use WebDev\Functions\Database;
     * $db = Database::getInstance();
     * $tables = $db->getTableNames();
     * 
     * foreach ($tables as $table){
     *     echo "Table: $table\n";
     * }
     * ```
     *
     * @return array An array of table names, or an empty array if no tables were found.
     * @throws DatabaseException If the database connection is not established or the query fails.
     */
    public function getTableNames(): array {
        try {
            // Check if there is a connection to the database established
            if ($this->conn === null){
                throw new DatabaseException(
                    "Database connection not established. Ensure the connection is initialized before fetching table names.",
                    500
                );
            }

            // Query to fetch all table names
            $result = $this->query("SHOW TABLES");

            // Return the table names as an array
            return !empty($result)
                ? array_map(fn($row) => array_values($row)[0], $result) // Extract table names from the result
                : []; // Return an empty array if no tables are found
        } 
        catch (Exception $e){
            throw new DatabaseException(
                "Error fetching table names: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Closes the database connection.
     *
     * This method explicitly closes the PDO connection to the database.
     * It's useful for freeing up resources when the connection is no longer needed.
     * 
     * This method gets called when the destructor gets called. 
     *
     * @return void
     */
    public function close(): void {
        $this->conn = null;
    }
}