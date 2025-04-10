<?php
namespace WebDev\Functions;

use Exception;
use WebDev\config\Database;
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
     * @throws Exception Always throws an exception when called.
     */
    public function __wakeup(): never {
        throw new Exception(message: "Cannot unserialize singleton");
    }

    /**
     * Prevent cloning of the singleton instance.
     * 
     * This method ensures that the singleton instance cannot be cloned,
     * maintaining the integrity of the singleton pattern.
     * 
     * @throws Exception Always throws an exception when called.
     */
    public function __clone(): void {
        throw new Exception(message: "Cannot clone singleton");
    }

    /**
     * Private constructor to initialize the Table class.
     * 
     * This method ensures that the table exists in the database before creating an instance.
     * It also establishes a database connection and stores the table name.
     * 
     * @param string $tableName The name of the table.
     * @throws Exception If the table does not exist in the database.
     */
    private function __construct(string $tableName){
        if (!Database::getInstance()->tableExists($tableName)){
            throw new Exception("Table '$tableName' doesn't exist.");
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
     * @return array|false An array of column metadata, or false on failure.
     * @throws Exception If no columns are found in the table.
     */
    public function getTableHeader(): array|false {
        try {
            $result = Database::getInstance()->query("SHOW COLUMNS FROM {$this->tableName}");

            if (count($result) === 0){
                throw new Exception("No columns found in table {$this->tableName}.");
            }

            return $result;
        } catch (Exception $e){
            error_log("getTableHeader error for {$this->tableName}: " . $e->getMessage());
            return false;
        }
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
     * @return array|false An array of rows from the table, or false on failure.
     * @throws Exception If the query fails or no data is found.
     */
    public function selectAll(): array|false {
        try {
            $result = Database::getInstance()->query("SELECT * FROM {$this->tableName}");

            if (empty($result)){
                throw new Exception("Failed to get data about table '$this->tableName'");
            }

            return $result;
        } catch (Exception $e){
            error_log($e->getMessage());
            return false;
        }
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
     * @return int|false The next available ID.
     */ 
    public function getNextId(): int|false {
        try {
            $parameters = [
                ":db" => "webDev",
                ":table" => $this->tableName
            ];
    
            // Execute the query with parameter binding
            $result = Database::getInstance()->query(
                "SELECT AUTO_INCREMENT 
                      FROM INFORMATION_SCHEMA.TABLES
                      WHERE TABLE_SCHEMA = :db 
                      AND TABLE_NAME = :table",
                $parameters
            );
    
            // Check if the result is valid
            if (empty($result) || !isset($result[0]['AUTO_INCREMENT'])){
                throw new Exception("No AUTO_INCREMENT value found for table '{$this->tableName}'.");
            }
    
            // Return the next availible ID
            return (int)($result[0]['AUTO_INCREMENT']);
        } 
        catch (Exception $e){
            // Log the error and return false
            error_log("getNextId error for {$this->tableName}: " . $e->getMessage());
            return false;
        }
    }
}