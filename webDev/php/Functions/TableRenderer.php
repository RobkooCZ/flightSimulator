<?php
namespace WebDev\Functions;

use Exception;
use WebDev\config\Database;
use WebDev\Functions\Table;
use WebDev\Functions\RoleManager;

class TableRenderer {
    private static array $instances = []; // Registry for TableRenderer instances
    private $table;

    /**
     * Private constructor to initialize the TableRenderer class.
     * 
     * This method associates a Table instance with the TableRenderer.
     * 
     * @param Table $table The Table instance to render.
     */
    private function __construct(Table $table){
        $this->table = $table;
    }

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
     * Retrieves or creates a TableRenderer instance for a specific table.
     * 
     * This method ensures that only one instance of the TableRenderer class is created
     * for each table during the application's lifecycle.
     * 
     * ### Example usage:
     * ```php
     * use WebDev\Functions\Table;
     * use WebDev\Functions\TableRenderer;
     * 
     * $table = Table::getInstance('users');
     * $renderer = TableRenderer::getInstance($table);
     * ```
     * 
     * @param Table $table The Table instance to render.
     * @return TableRenderer The TableRenderer instance for the given table.
     */
    public static function getInstance(Table $table): TableRenderer {
        $tableName = $table->getTableName();

        if (!isset(self::$instances[$tableName])){
            self::$instances[$tableName] = new self($table);
        }

        return self::$instances[$tableName];
    }

    /**
     * Displays an HTML table for the given result set.
     * 
     * This method renders an HTML table based on the provided result set and the table's header data.
     * 
     * ### Example usage:
     * ```php
     * $table = Table::getInstance('users');
     * $renderer = TableRenderer::getInstance($table);
     * $result = $table->selectAll();
     * $renderer->displayTable($result, true);
     * ```
     * 
     * @param array $result The result set to display in the table.
     * @param bool $endTable Whether to close the table tag after rendering.
     * @return bool True on success, false on failure.
     */
    public function displayTable(array $result, bool $endTable): bool {
        try {
            $headerData = $this->table->getTableHeader();

            echo "<table style='border: var(--border)';>"; // Open the table
            echo "<tr>"; // Open the header row

            foreach ($headerData as $header){
                echo "<th>" . htmlspecialchars($header['Field']) . "</th>";
            }

            echo "</tr>"; // Close the header row

            foreach ($result as $row){
                echo "<tr>";

                foreach ($row as $key => $cell){
                    if ($key === "password" || $key === "salt"){
                        echo "<td> --------- </td>";
                    } else {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                }

                echo "</tr>";
            }

            if ($endTable === true){
                echo "</table>"; // Close the table
            }

            return true;
        } 
        catch (Exception $e){
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Generates a dropdown of roles for a user.
     * 
     * This method retrieves the ENUM values for the `role` column in the `users` table
     * and filters them based on the user's current role.
     * 
     * ### Example usage:
     * ```php
     * $table = Table::getInstance('users');
     * $renderer = TableRenderer::getInstance($table);
     * $dropdown = $renderer->getRolesDropdown(1);
     * echo "<select>$dropdown</select>";
     * ```
     * 
     * @param int $userId The ID of the user for whom the dropdown is being generated.
     * @return string|false The HTML for the dropdown, or false on failure.
     */
    public function getRolesDropdown(int $userId): string|false {
        try {
            if ($this->table->getTableName() !== "users"){
                throw new Exception("Table name isn't users. Can't show roles.");
            }

            $result = Database::getInstance()->query(
                "SELECT COLUMN_TYPE 
                 FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_NAME = 'users' 
                 AND COLUMN_NAME = 'role'"
            );

            if (empty($result)){
                throw new Exception("No ENUM values found for 'role' column.");
            }

            $enumString = $result[0]['COLUMN_TYPE'];
            preg_match("/^enum\((.*)\)$/", $enumString, $matches);

            if (!isset($matches[1])){
                throw new Exception("Failed to parse ENUM values for 'role' column.");
            }

            $enumValues = array_map(function ($value){
                return trim($value, "'");
            }, explode(',', $matches[1]));

            $currentRole = match ($userId){
                1 => "owner",
                2 => "coOwner",
                default => "user"
            };

            $filteredRoles = RoleManager::returnAvailibleRoles($enumValues, $currentRole);

            $options = '';
            foreach ($filteredRoles as $value){
                $options .= "<option value='" . htmlspecialchars($value) . "'>" . htmlspecialchars($value) . "</option>";
            }

            return $options;
        } 
        catch (Exception $e){
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Displays a form for adding a new row to the table.
     * 
     * This method renders an HTML form within the table for adding a new row.
     * 
     * ### Example usage:
     * ```php
     * $table = Table::getInstance('users');
     * $renderer = TableRenderer::getInstance($table);
     * $result = $table->selectAll();
     * $renderer->displayTableForm($result, 'addUser', 1);
     * ```
     * 
     * @param array $result The result set to display in the table.
     * @param string $actionName The name of the action (e.g., 'addUser').
     * @param int $userId The ID of the user performing the action.
     * @return bool True on success, false on failure.
     */
    public function displayTableForm(array $result, string $actionName, int $userId): bool {
        try {
            $this->displayTable($result, false);

            $action = urldecode($actionName);
            $headerData = $this->table->getTableHeader();

            echo "<tr>";

            foreach ($headerData as $header){
                $fieldName = $header['Field'];

                switch ($fieldName){
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
                        if ($rolesDropdown !== false){
                            echo '<td><select id="role" class="addInputs noBorder" name="role">' . $rolesDropdown . '</select></td>';
                        } else {
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

            echo "</tr>";
            echo "</table>";

            return true;
        } 
        catch (Exception $e){
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Returns HTML of options for the provided table names.
     * 
     * This method generates a dropdown of table names.
     * 
     * ### Example usage:
     * ```php
     * $tableNames = ['users', 'roles', 'permissions'];
     * $dropdown = TableRenderer::getTableNamesDropdown($tableNames);
     * echo "<select>$dropdown</select>";
     * ```
     * 
     * @param array $tableNames An array of table names to display.
     * @return string|false The HTML for the dropdown, or false on failure.
     */
    public static function getTableNamesDropdown(array $tableNames): string|false {
        try {
            if (empty($tableNames)){
                throw new Exception("No table names provided to render.");
            }

            $options = '';
            foreach ($tableNames as $tableName){
                $options .= "<option value='" . htmlspecialchars($tableName) . "'>" . htmlspecialchars($tableName) . "</option>";
            }

            return $options;
        } 
        catch (Exception $e){
            error_log("Error rendering table names dropdown: " . $e->getMessage());
            return false;
        }
    }
}