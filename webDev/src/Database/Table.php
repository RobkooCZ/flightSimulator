<?php
/**
 * Table Class File
 *
 * This file contains the `Table` class, which provides a mechanism to manage database table interactions.
 * It uses a singleton pattern to ensure only one instance per table is created.
 *
 * @file Table.php
 * @since 0.1
 * @package Database
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see Database
 * @todo Add more table utility methods
 */

declare(strict_types=1);

namespace WebDev\Database;

// Database
use WebDev\Database\Database;

// PDO
use PDO;

// Exception classes
use WebDev\Exception\LogicException;
use WebDev\Exception\ConfigurationException;
use WebDev\Exception\DatabaseException;

// Logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers;

/**
 * Class Table
 *
 * Provides a mechanism to manage database table interactions.
 * Uses a singleton pattern to ensure only one instance per table is created.
 *
 * @package Database
 * @since 0.2
 * @see Database
 * @todo Add more table utility methods
 */
class Table {
    /**
     * @var array $instances An associative array to hold instances of the class.
     * Each instance is identified by a table name.
     */
    private static array $instances = [];

    /**
     * @var PDO $conn The PDO connection instance for database interactions.
     */
    private PDO $conn;

    /**
     * @var string $tableName The name of the database table associated with this class.
     */
    private string $tableName;

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
        throw new LogicException(
            message: "Cannot unserialize singleton.",
            reason: "Would violate the singleton pattern."
        );
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
        throw new LogicException(
            message: "Cannot clone singleton.",
            reason: "Would violate the singleton pattern."
        );
    }

    /**
     * Private constructor to initialize the Table class.
     * 
     * This method ensures that the table exists in the database before creating an instance.
     * It also establishes a database connection and stores the table name.
     * 
     * @param string $tableName The name of the table.
     * @throws ConfigurationException If the table does not exist in the database.
     */
    private function __construct(string $tableName){
        Logger::log(
            "Initializing Table instance for table: '$tableName'.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        if (!Database::getInstance()->tableExists($tableName)){
            throw new ConfigurationException(
                message: "Table '$tableName' doesn't exist.",
                code: 400,
                configKey: "tableName",
                source: "Table",
                expected: "A valid table name in the database.",
                configPath: null
            );
        }

        $this->conn = Database::getInstance()->getConnection();
        $this->tableName = $tableName;

        Logger::log(
            "Table instance for '$tableName' initialized successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        self::$instances[$tableName] = $this;
    }

    /**
     * Retrieves or creates a Table instance for a specific table name.
     * 
     * This method ensures that only one instance of the Table class is created
     * for each table name during the application's lifecycle.
     * 
     * ### Example:
     * ```php
     * use WebDev\Functions\Table;
     * 
     * $table = Table::getInstance('users');
     * echo $table->getTableName(); // Outputs: 'users'
     * ```
     * 
     * @param string $tableName The name of the table.
     * @return Table The Table instance for the given table name.
     */
    public static function getInstance(string $tableName): Table {
        if (isset(self::$instances[$tableName])){
            Logger::log(
                "Reusing existing Table instance for table: '$tableName'.",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "Creating new Table instance for table: '$tableName'.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return self::$instances[$tableName] ?? new self($tableName);
    }

    /**
     * Retrieves the name of the table.
     * 
     * This method returns the name of the table associated with the current Table instance.
     * 
     * ### Example:
     * ```php
     * $table = Table::getInstance('users');
     * echo $table->getTableName(); // Outputs: 'users'
     * ```
     * 
     * @return string The name of the table.
     */
    public function getTableName(): string {
        Logger::log(
            "Retrieving the table name: '{$this->tableName}'.",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        return $this->tableName;
    }

    /**
     * Retrieves the header (columns) of the table.
     * 
     * This method fetches the column names and metadata for the table using the `SHOW COLUMNS` SQL command.
     * 
     * ### Example:
     * ```php
     * $table = Table::getInstance('users');
     * $header = $table->getTableHeader();
     * 
     * foreach ($header as $column){
     *     echo $column['Field']; // Outputs column names like 'id', 'username', etc.
     * }
     * ```
     * 
     * @return array An array of column metadata.
     * @throws DatabaseException If no columns are found in the table or the query fails.
     */
    public function getTableHeader(): array {
        Logger::log(
            "Fetching table header (columns) for table: '{$this->tableName}'.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $result = Database::getInstance()->query("SHOW COLUMNS FROM {$this->tableName}");

        Logger::log(
            "Table header fetched successfully for table: '{$this->tableName}'.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return $result;
    }

    /**
     * Retrieves all rows from the table.
     * 
     * This method fetches all rows from the table using the `SELECT *` SQL command.
     * 
     * ### Example:
     * ```php
     * $table = Table::getInstance('users');
     * $rows = $table->selectAll();
     * 
     * foreach ($rows as $row){
     *     echo $row['username']; // Outputs usernames from the 'users' table.
     * }
     * ```
     * 
     * @return array An array of rows from the table.
     * @throws DatabaseException If the query fails or no data is found.
     */
    public function selectAll(): array {
        Logger::log(
            "Fetching all rows from table: '{$this->tableName}'.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $result = Database::getInstance()->query("SELECT * FROM {$this->tableName}");

        Logger::log(
            "All rows fetched successfully from table: '{$this->tableName}'.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return $result;
    }

    /**
     * Retrieves the next available auto-increment ID for the table.
     * 
     * This method fetches the `AUTO_INCREMENT` value for the table from the `INFORMATION_SCHEMA.TABLES`.
     * 
     * ### Example:
     * ```php
     * $table = Table::getInstance('users');
     * $nextId = $table->getNextId();
     * echo $nextId; // Outputs the next available ID for the 'users' table.
     * ```
     * 
     * @return int The next available ID.
     * @throws DatabaseException If the query fails or no AUTO_INCREMENT value is found.
     */
    public function getNextId(): int {
        Logger::log(
            "Fetching next AUTO_INCREMENT ID for table: '{$this->tableName}'.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $parameters = [
            ":db" => "webDev",
            ":table" => $this->tableName
        ];

        $result = Database::getInstance()->query(
            "SELECT AUTO_INCREMENT 
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = :db 
             AND TABLE_NAME = :table",
            $parameters
        );

        Logger::log(
            "Next AUTO_INCREMENT ID fetched successfully for table: '{$this->tableName}'.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return (int)$result[0]['AUTO_INCREMENT'];
    }
}