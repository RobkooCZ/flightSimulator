<?php
namespace WebDev\Functions;

use Exception;
use WebDev\config\Database;
use PDO;

class Table {
    private static array $instances = [];
    private PDO $conn;
    private string $tableName;

    private function __construct(string $tableName) {
        if (!Database::getInstance()->tableExists($tableName)){
            throw new Exception("Table '$tableName' doesn't exist.");
        }
        
        $this->conn = Database::getInstance()->getConnection();
        $this->tableName = $tableName;

        self::$instances[$tableName] = $this;
    }   

    /**
     * Static method to get or create a Table instance for a specific table name.
     *
     * @param string $tableName The name of the table.
     * @return Table The Table instance for the given table name.
     */
    public static function getInstance(string $tableName): Table {
        return self::$instances[$tableName] ?? new self($tableName);
    }

    public function getTableName(): string {
        return $this->tableName;
    }

    public function getTableHeader(): array|false {
        try {
            // try to fetch table header using the query method from my database class
            $result = Database::getInstance()->query("SHOW COLUMNS FROM {$this->tableName}");

            // if its empty, throw expection
            if (count($result) === 0){
                throw new Exception("No columns found in table {$this->tableName}.");
            }

            // success
            return $result;
        }
        catch (Exception $e){
            // failure
            error_log("getTableHeader error for {$this->tableName}: " . $e->getMessage());
            return false;
        }
    }

    public function selectAll(): array|false {
        try {
            // safely execute select all query
            $result = Database::getInstance()->query("SELECT * FROM {$this->tableName}");

            // if it failed, throw an exception
            if (empty($result)){
                throw new Exception("Failed to get data about table '$this->tableName'");
            }

            // good, return the array
            return $result;
        }
        catch (Exception $e){
            // bad
            error_log($e->getMessage());
            return false;
        }
    }

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
            if (empty($result) || !isset($result[0]['AUTO_INCREMENT'])) {
                throw new Exception("No AUTO_INCREMENT value found for table '{$this->tableName}'.");
            }
    
            // Return the next availible ID (AUTO_INCREMENT + 1)
            return (int)($result[0]['AUTO_INCREMENT']) + 1;
        } 
        catch (Exception $e) {
            // Log the error and return false
            error_log("getNextId error for {$this->tableName}: " . $e->getMessage());
            return false;
        }
    }
}