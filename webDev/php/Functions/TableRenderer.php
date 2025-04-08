<?php
namespace WebDev\Functions;

use Exception;
use WebDev\config\Database;
use WebDev\Functions\Table;
use WebDev\Functions\RoleManager;

class TableRenderer {
    private static array $instances = []; // Registry for TableRenderer instances
    private $table;

    private function __construct(Table $table){
        $this->table = $table;
    }

    public static function getInstance(Table $table): TableRenderer{
        $tableName = $table->getTableName();

        if (!isset(self::$instances[$tableName])){
            self::$instances[$tableName] = new self($table);
        }

        return self::$instances[$tableName];
    }

    public function displayTable(array $result, bool $endTable): bool {
        try {
            // get table header info
            $headerData = $this->table->getTableHeader();
        
            // table structure
            echo "<table style='border: var(--border)';>"; // open the table
            echo "<tr>"; // open the header row
    
            // while loop to print the header
            foreach ($headerData as $header) {
                echo "<th>" . htmlspecialchars($header['Field']) . "</th>";
                $columns = $header['Field'];
            }
    
            echo "</tr>"; // close the header row
    
            // print the body
            foreach ($result as $row){
                echo "<tr>";

                foreach ($row as $key => $cell){
                    if ($key === "password" || $key === "salt"){
                        echo "<td> --------- </td>";
                    }
                    else{
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                }

                echo "</tr>";
            }
    
            // end table if bool $endTable is set to true
            if ($endTable === true){
                // end table
                echo "</table>";
            }
    
            return true; // success
        } 
        catch (Exception $e) {
            // failure
            error_log($e->getMessage());
            return false;
        }
    }

    public function getRolesDropdown(int $userId): string|false {
        try {    
            if ($this->table->getTableName() !== "users"){
                throw new Exception("Table name isn't users. Can't show roles.");
            }

            $result = Database::getInstance()->query(
                "SELECT COLUMN_TYPE 
                      FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'users' 
                      AND COLUMN_NAME = 'role'
                     ");

            if (empty($result)) {
                throw new Exception("No ENUM values found for 'role' column.");
            }

            // Extract the ENUM string (e.g., "enum('owner','coOwner','admin','user','deleted')")
            $enumString = $result[0]['COLUMN_TYPE'];

            // Use a regular expression to extract the values inside the parentheses
            preg_match("/^enum\((.*)\)$/", $enumString, $matches);

            if (!isset($matches[1])) {
                throw new Exception("Failed to parse ENUM values for 'role' column.");
            }

            // Split the ENUM values into an array
            $enumValues = array_map(function ($value) {
                return trim($value, "'");
            }, explode(',', $matches[1]));

            // match it based on session id
            $currentRole = match ($userId) {
                1 => "owner",
                2 => "coOwner",
                default => "user"
            };

            // remove the roles the user can't choose in the select
            $filteredRoles = RoleManager::returnAvailibleRoles($enumValues, $currentRole);

            // Build the dropdown options
            $options = '';
            foreach ($filteredRoles as $value) {
                $options .= "<option value='" . htmlspecialchars($value) . "'>" . htmlspecialchars($value) . "</option>";
            }

            return $options;
        } 
        catch (Exception $e) {
            // Log the error and return false
            error_log($e->getMessage());
            return false;
        }
    }

    public function displayTableForm(array $result, string $actionName, int $userId): bool {
        try {    
            // Display the table, but don't close the table tag to add the form
            $this->displayTable($result, false);
    
            // Decode the action (encoded in AJAX)
            $action = urldecode($actionName);
    
            // Get header data
            $headerData = $this->table->getTableHeader();
    
            // Start the form row
            echo "<tr>";
    
            foreach ($headerData as $header) {
                $fieldName = $header['Field'];
    
                // Handle specific fields
                switch ($fieldName) {
                    case "id":
                        $id = $this->table->getNextId();
                        echo '<td><input class="addInputs noBorder" type="text" pattern="[0-9]*" name="' . $fieldName . '" value="' . $id . '" disabled></td>';
                        break;
    
                    case "profilePicture":
                    case "lastActivityAt":
                    case "createdAt":
                    case "lastLoginAt":
                    case "updatedAt":
                    case "salt":
                        echo '<td><input class="addInputs noBorder" type="text" name="' . $fieldName . '" value="---------" disabled></td>';
                        break;
    
                    case "role":
                        $rolesDropdown = $this->getRolesDropdown($userId);
                        if ($rolesDropdown !== false) { // if the getRolesDropdown method hadn't failed, display the dropdown
                            echo '<td><select id="role" class="addInputs noBorder" name="role">' . $rolesDropdown . '</select></td>';
                        } 
                        else {
                            echo '<td><input class="addInputs noBorder" type="text" name="role" value="Error loading roles" disabled></td>';
                        }
                        break;
    
                    case "status":
                        echo '<td><input class="addInputs noBorder" type="text" name="status" value="active" disabled></td>';
                        break;
    
                    case "failedLoginAttempts":
                        echo '<td><input class="addInputs noBorder" type="text" name="failedLoginAttempts" value="0" disabled></td>';
                        break;

                    default:
                        echo '<td><input class="addInputs" id="' . $fieldName . '" name="' . $fieldName . '"></td>';
                        break;
                }
            }
    
            // Close the form row
            echo "</tr>";
    
            // End the table
            echo "</table>";
    
            return true; // Successfully displayed form
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false; // Something failed
        }
    }

    /**
     * Returns HTML of options of the provided $tableNames array.
     *
     * @param array $tableNames An array of table names to display.
     * @return string|false The HTML for the dropdown, or false on failure.
     */
    public static function getTableNamesDropdown(array $tableNames): string|false {
        try {
            // Check if the array is empty
            if (empty($tableNames)) {
                throw new Exception("No table names provided to render.");
            }

            // Build the dropdown options
            $options = '';
            foreach ($tableNames as $tableName) {
                $options .= "<option value='" . htmlspecialchars($tableName) . "'>" . htmlspecialchars($tableName) . "</option>";
            }

            // Return the generated HTML
            return $options;
        } catch (Exception $e) {
            // Log the error and return false
            error_log("Error rendering table names dropdown: " . $e->getMessage());
            return false;
        }
    }
}