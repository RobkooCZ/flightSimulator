<?php
declare(strict_types=1);

namespace WebDev\UI;

// Database
use WebDev\Database\Database;

// Exception classes
use WebDev\Exception\LogicException;
use WebDev\Exception\ConfigurationException;
use WebDev\Exception\ValidationException;
use WebDev\Exception\DatabaseException;
use WebDev\Exception\PHPException;

// Logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers;

// Role management
use WebDev\Utilities\RoleManager;

// Table
use WebDev\Database\Table;

class TableRenderer {
    /**
     * @var array $instances Registry for TableRenderer instances
     */
    private static array $instances = [];

    /**
     * @var Table $table The table instance or data associated with the TableRenderer
     */
    private Table $table;

    /**
     * Private constructor to initialize the TableRenderer class.
     * 
     * This method associates a Table instance with the TableRenderer.
     * 
     * @param Table $table The Table instance to render.
     */
    private function __construct(Table $table){
        Logger::log(
            "Initializing TableRenderer instance for table: '{$table->getTableName()}'.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );
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

        if (!isset(self::$instances[$tableName])){
            Logger::log(
                "Creating new TableRenderer instance for table: '$tableName'.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            self::$instances[$tableName] = new self($table);
        }
        else {
            Logger::log(
                "Reusing existing TableRenderer instance for table: '$tableName'.",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
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
        Logger::log(
            "Rendering HTML table for table: '{$this->table->getTableName()}'.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $headerData = $this->table->getTableHeader();

        echo "<table style='border: var(--border)';>"; // Open the table
        echo "<tr>"; // Open the header row

        foreach ($headerData as $header){
            echo "<th>" . htmlspecialchars((string)$header['Field']) . "</th>";
        }

        echo "</tr>"; // Close the header row

        foreach ($result as $row){
            echo "<tr>";

            foreach ($row as $key => $cell){
                if ($key === "password" || $key === "salt"){
                    echo "<td> --------- </td>";
                }
                else {
                    echo "<td>" . htmlspecialchars((string)$cell) . "</td>";
                }
            }

            echo "</tr>";
        }

        if ($endTable === true){
            echo "</table>"; // Close the table
        }

        Logger::log(
            "HTML table rendered successfully for table: '{$this->table->getTableName()}'.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

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
        Logger::log(
            "Generating roles dropdown for user ID: $userId.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        if ($this->table->getTableName() !== "users"){
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

        $enumString = $result[0]['COLUMN_TYPE'];
        preg_match("/^enum\((.*)\)$/", $enumString, $matches);

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
            $options .= "<option value='" . htmlspecialchars((string)$value) . "'>" . htmlspecialchars((string)$value) . "</option>";
        }

        Logger::log(
            "Roles dropdown generated successfully for user ID: $userId.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

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
        Logger::log(
            "Rendering table form for action: '$actionName' and user ID: $userId.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

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

        echo "</tr>";
        echo "</table>";

        Logger::log(
            "Table form rendered successfully for action: '$actionName' and user ID: $userId.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

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
        Logger::log(
            "Generating table names dropdown.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $options = '';
        foreach ($tableNames as $tableName){
            $options .= "<option value='" . htmlspecialchars((string)$tableName) . "'>" . htmlspecialchars((string)$tableName) . "</option>";
        }

        Logger::log(
            "Table names dropdown generated successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return $options;
    }
}