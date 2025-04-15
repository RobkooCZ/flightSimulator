<?php
declare(strict_types=1);
namespace WebDev\Functions;

use WebDev\config\Database;
use WebDev\Functions\Table;
use WebDev\Functions\RoleManager;
use WebDev\Functions\PHPException;
use WebDev\Functions\DatabaseException;
use WebDev\Functions\ConfigurationException;
use WebDev\Functions\LogicException;

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
    private function __construct(Table $table) {
        $this->table = $table;
    }

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

        if (!isset(self::$instances[$tableName])) {
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
     * @throws DatabaseException If the table header cannot be retrieved.
     */
    public function displayTable(array $result, bool $endTable): bool {
        $headerData = $this->table->getTableHeader();

        echo "<table style='border: var(--border)';>"; // Open the table
        echo "<tr>"; // Open the header row

        foreach ($headerData as $header) {
            echo "<th>" . htmlspecialchars((string)$header['Field']) . "</th>";
        }

        echo "</tr>"; // Close the header row

        foreach ($result as $row) {
            echo "<tr>";

            foreach ($row as $key => $cell) {
                if ($key === "password" || $key === "salt") {
                    echo "<td> --------- </td>";
                } else {
                    echo "<td>" . htmlspecialchars((string)$cell) . "</td>";
                }
            }

            echo "</tr>";
        }

        if ($endTable === true) {
            echo "</table>"; // Close the table
        }

        return true;
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
     * @throws ConfigurationException If the table is not configured as `users`.
     * @throws DatabaseException If the database query for ENUM values fails.
     * @throws PHPException If parsing the ENUM values fails.
     */
    public function getRolesDropdown(int $userId): string|false {
        if ($this->table->getTableName() !== "users") {
            throw new ConfigurationException(
                message: "Table name isn't 'users'. Can't show roles.",
                code: 400,
                configKey: "tableName",
                source: "TableRenderer",
                expected: "'users'",
                configPath: null
            );
        }

        $result = Database::getInstance()->query(
            "SELECT COLUMN_TYPE 
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_NAME = 'users' 
             AND COLUMN_NAME = 'role'"
        );

        if (empty($result)) {
            throw new DatabaseException(
                "No ENUM values found for 'role' column.",
                500
            );
        }

        $enumString = $result[0]['COLUMN_TYPE'];
        preg_match("/^enum\((.*)\)$/", $enumString, $matches);

        if (!isset($matches[1])) {
            throw new PHPException(
                "Failed to parse ENUM values for 'role' column.",
                500
            );
        }

        $enumValues = array_map(function ($value) {
            return trim($value, "'");
        }, explode(',', $matches[1]));

        $currentRole = match ($userId) {
            1 => "owner",
            2 => "coOwner",
            default => "user"
        };

        $filteredRoles = RoleManager::returnAvailibleRoles($enumValues, $currentRole);

        $options = '';
        foreach ($filteredRoles as $value) {
            $options .= "<option value='" . htmlspecialchars((string)$value) . "'>" . htmlspecialchars((string)$value) . "</option>";
        }

        return $options;
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
     * @throws ConfigurationException If the table is not configured correctly.
     */
    public function displayTableForm(array $result, string $actionName, int $userId): bool {
        $this->displayTable($result, false);

        $action = urldecode($actionName);
        $headerData = $this->table->getTableHeader();

        echo "<tr>";

        foreach ($headerData as $header) {
            $fieldName = $header['Field'];

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
                    if ($rolesDropdown !== false) {
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
     * @throws ValidationException If no table names are provided.
     */
    public static function getTableNamesDropdown(array $tableNames): string|false {
        if (empty($tableNames)) {
            throw new ConfigurationException(
                message: "No table names provided to render.",
                code: 400,
                configKey: "Table",
                source: "Database"
            );
        }

        $options = '';
        foreach ($tableNames as $tableName) {
            $options .= "<option value='" . htmlspecialchars((string)$tableName) . "'>" . htmlspecialchars((string)$tableName) . "</option>";
        }

        return $options;
    }
}