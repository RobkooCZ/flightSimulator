<?php
declare(strict_types=1);

namespace WebDev\Functions;

use WebDev\config\Database;
use WebDev\Functions\LogicException;
use WebDev\Functions\DatabaseException;
use WebDev\Functions\ConfigurationException;
use PDO;

class Table {
    private static array $instances = [];
    private PDO $conn;
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

        self::$instances[$tableName] = $this;
    }

    /**
     * Retrieves or creates a Table instance for a specific table name.
     * 
     * This method ensures that only one instance of the Table class is created
     * for each table name during the application's lifecycle.
     * 
     * ### Example usage:
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
        return self::$instances[$tableName] ?? new self($tableName);
    }

    /**
     * Retrieves the name of the table.
     * 
     * This method returns the name of the table associated with the current Table instance.
     * 
     * ### Example usage:
     * ```php
     * $table = Table::getInstance('users');
     * echo $table->getTableName(); // Outputs: 'users'
     * ```
     * 
     * @return string The name of the table.
     */
    public function getTableName(): string {
        return $this->tableName;
    }

    /**
     * Retrieves the header (columns) of the table.
     * 
     * This method fetches the column names and metadata for the table using the `SHOW COLUMNS` SQL command.
     * 
     * ### Example usage:
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
        $result = Database::getInstance()->query("SHOW COLUMNS FROM {$this->tableName}");

        if (empty($result)){
            throw new DatabaseException(
                message: "No columns found in table '{$this->tableName}'.",
                code: 500
            );
        }

        return $result;
    }

    /**
     * Retrieves all rows from the table.
     * 
     * This method fetches all rows from the table using the `SELECT *` SQL command.
     * 
     * ### Example usage:
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
        $result = Database::getInstance()->query("SELECT * FROM {$this->tableName}");

        if (empty($result)){
            throw new DatabaseException(
                message: "Failed to retrieve data from table '{$this->tableName}'.",
                code: 500
            );
        }

        return $result;
    }

    /**
     * Retrieves the next available auto-increment ID for the table.
     * 
     * This method fetches the `AUTO_INCREMENT` value for the table from the `INFORMATION_SCHEMA.TABLES`.
     * 
     * ### Example usage:
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

        if (empty($result) || !isset($result[0]['AUTO_INCREMENT'])){
            throw new DatabaseException(
                message: "No AUTO_INCREMENT value found for table '{$this->tableName}'.",
                code: 500
            );
        }

        return (int)$result[0]['AUTO_INCREMENT'];
    }
}