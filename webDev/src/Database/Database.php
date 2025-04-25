<?php
/**
 * Database Class File
 *
 * This file contains the `Database` class, which is responsible for managing database connections and interactions.
 * It provides methods to establish a connection to the database and perform various database operations.
 *
 * @file Database.php
 * @since 0.2
 * @package Database
 * @version 0.3.4
 * @author Robkoo
 * @license TBD
 * @see PDO, Logger
 * @todo Add support for connection pooling
 */

declare(strict_types=1);

namespace WebDev\Database;

// database things
use PDO;
use PDOException;

// dotenv
use Dotenv\Dotenv;

// custom exceptions
use WebDev\Exception\LogicException;
use WebDev\Exception\ConfigurationException;
use WebDev\Exception\DatabaseException;

// custom logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers; 

/**
 * Class Database
 *
 * This class is responsible for managing database connections and interactions.
 * It provides methods to establish a connection to the database and perform
 * various database operations.
 *
 * @package Database
 * @since 0.2
 * @see PDO, Logger
 * @todo Add support for connection pooling
 */
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
        Logger::log(
            "Initializing the Database class and loading .env variables...",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        } 
        catch (\Exception $e){
            Logger::log(
                "Failed to load .env variables: " . $e->getMessage(),
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            throw new ConfigurationException(
                "Failed to load .env file. Please ensure it exists and is properly configured.",
                500,
                $e->getMessage(),
                ".env",
                "Error loading environment variables.",
                __DIR__ . '/../.env',
                $e
            );
        }

        Logger::log(
            "Successfully loaded .env variables.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $dbHost = $_ENV['DB_HOST'] ?? null;
        $dbName = $_ENV['DB_NAME'] ?? null;
        $dbUser = $_ENV['DB_USER'] ?? null;
        $dbPass = $_ENV['DB_PASS'] ?? null;

        $missing = array_filter([
            'DB_HOST' => $dbHost,
            'DB_NAME' => $dbName,
            'DB_USER' => $dbUser,
            'DB_PASS' => $dbPass
        ], fn($value) => !$value);

        if (!empty($missing)){
            Logger::log(
                "Missing required environment variables: " . implode(', ', array_keys($missing)),
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            throw new ConfigurationException(
                "Missing environment variables. Please check your .env file.",
                500,
                implode(', ', array_keys($missing)),
                ".env",
                "All required environment variables must be set.",
                __DIR__ . '/../.env',
                null,
            );
        }

        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

        Logger::log(
            "Attempting to establish a database connection...",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        try {
            $this->conn = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5
            ]);

            Logger::log(
                "Database connection established successfully.",
                LogLevel::SUCCESS,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        catch (PDOException $e){
            throw new DatabaseException(
                "Failed to establish a database connection: " . $e->getMessage(),
                $e->getCode(),
                $e
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
        Logger::log(
            "Destroying the Database instance and closing the connection.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );
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
        if (self::$instance === null){
            Logger::log(
                "Creating a new Database singleton instance.",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            self::$instance = new Database();
        }
        else {
            Logger::log(
                "Reusing the existing Database singleton instance.",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
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
        Logger::log(
            "Retrieving the PDO connection instance.",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
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
     * use WebDev\Database\Database;
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
        Logger::log(
            "Validating query parameters...",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        foreach ($parameters as $key => $value){
            if (is_array($value) || is_object($value)){
                throw new DatabaseException(
                    "Invalid parameter type for key: '$key'. Expected scalar values (int, float, string, or bool), but received " . gettype($value) . ".",
                    400
                );
            }
        }
        Logger::log(
            "Query parameters validated successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
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
        Logger::log(
            "Escaping database identifier: '$identifier'",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)){
            throw new DatabaseException(
                "Invalid identifier: '$identifier'. Identifiers must only contain alphanumeric characters or underscores.",
                400
            );
        }
        Logger::log(
            "Database identifier escaped successfully: `$identifier`",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
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
        Logger::log(
            "Executing query: '$sql'",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        
        // validate the parameters using a custom method
        $this->validateParameters($parameters);

        try {
            // prepare the statement to prevent SQL injection
            $statement = $this->conn->prepare($sql);

            // execute the query
            $statement->execute($parameters);
        }
        catch (PDOException $pe){
            // catch PDOexception(s) and rethrow them as my custom exception
            throw new DatabaseException(
                "PDO error occured.",
                500,
                $pe,
                $sql,
                $pe->getCode(),
                $pe->getMessage()
            );
        }

        Logger::log(
            "Query executed successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // return an assoc array based on the query
        return $statement->fetchAll();
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
     * use WebDev\Database\Database;
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
     * }
     * else {
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
        Logger::log(
            "Executing SQL statement: '$sql'",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // validate the parameters using a custom method
        $this->validateParameters($parameters);

        try {
            // prepare the statement to prevent SQL injection
            $statement = $this->conn->prepare($sql);

            // execute the query and put the resulting bool in a var
            $result = $statement->execute($parameters);
        }
        catch (PDOException $pe){
            // catch PDOexception(s) and rethrow them as my custom exception
            throw new DatabaseException(
                "PDO error occured.",
                500,
                $pe,
                $sql,
                $pe->getCode(),
                $pe->getMessage()
            );
        }
        
        Logger::log(
            $result ? "SQL statement executed successfully." : "SQL statement execution failed.",
            $result ? LogLevel::SUCCESS : LogLevel::FAILURE,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // return a bool based on whether it went correctly or not
        return $result;
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
     * use WebDev\Database\Database;
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
        Logger::log(
            "Checking if table exists: '$tableName'...",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tableName";
        $statement = $this->conn->prepare($sql);
        $statement->execute([':tableName' => $tableName]);
        $exists = $statement->rowCount() > 0;
        Logger::log(
            $exists ? "Table '$tableName' exists." : "Table '$tableName' does not exist.",
            $exists ? LogLevel::SUCCESS : LogLevel::FAILURE,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        return $exists;
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
     * use WebDev\Database\Database;
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
        Logger::log(
            "Fetching all table names from the database...",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        $result = $this->query("SHOW TABLES");
        $tables = !empty($result)
            ? array_map(fn($row) => array_values($row)[0], $result)
            : [];
        Logger::log(
            "Fetched " . count($tables) . " table(s) from the database.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        return $tables;
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
        Logger::log(
            "Closing the database connection.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        $this->conn = null;
    }
}