<?php
/**
 * Handles AJAX requests for the School Admin Page.
 *
 * Processes AJAX POST requests for table data and action forms, returning HTML fragments
 * in a standardized API response structure. Integrates with user authentication, table rendering,
 * and unified API response handling.
 *
 * @file adminSchoolAjax.php
 * @since 0.7.2
 * @package API
 * @author Robkoo
 * @license TBD
 * @version 0.7.9
 * @see /webDev/src/API/ApiResponse.php, /webDev/assets/constants/constants.php, /webDev/public/adminSchoolPage.php
 * @todo Add CSRF protection, more granular error handling, and logging.
 */
declare(strict_types=1);

use WebDev\Bootstrap;
Bootstrap::init();

include __DIR__ . '/../assets/constants/ConstantsLoader.php';

use WebDev\API\ApiResponse;

session_start();
header('Content-Type: application/json');

// Database
use WebDev\Database\Table;

// UI
use WebDev\UI\TableRenderer;

// User
use WebDev\Auth\User;
use WebDev\Database\Database;
use WebDev\Exception\DatabaseException;

/**
 * Apply filter to table data based on column, operator, and value.
 *
 * @param Table $table - The table instance to filter
 * @param string $column - Column name to filter by
 * @param string $operator - SQL operator (=, !=, >, <, >=, <=, LIKE)
 * @param string $value - Value to filter by
 * @return array Filtered table data
 * @throws DatabaseException If column name or operator is invalid
 */
function applyTableFilter($table, $column, $operator, $value){
    $db = Database::getInstance();
    $tableName = $table->getTableName();
    
    // Validate column name to prevent SQL injection
    $validColumns = array_column($table->getTableHeader(), 'Field');
    if (!in_array($column, $validColumns)){
        throw new DatabaseException("Invalid column name: $column");
    }
    
    // Validate operator
    $validOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
    if (!in_array($operator, $validOperators)){
        throw new DatabaseException("Invalid operator: $operator");
    }
    
    // Build query
    if ($operator === 'LIKE'){
        $query = "SELECT * FROM {$tableName} WHERE {$column} LIKE :value ORDER BY id";
        $params = ['value' => "%{$value}%"];
    } else {
        $query = "SELECT * FROM {$tableName} WHERE {$column} {$operator} :value ORDER BY id";
        $params = ['value' => $value];
    }
    
    try {
        $result = $db->query($query, $params);
        return $result ?: []; // Return empty array if no results
    } catch (Exception $e){
        throw new DatabaseException("Filter query failed: " . $e->getMessage());
    }
}

/**
 * Get table columns for filter dropdown options.
 *
 * @param Table $table - The table instance to get columns from
 * @return array Array of column objects with 'value' and 'label' keys
 */
function getTableColumns($table){
    $headers = $table->getTableHeader();
    $columns = [];
    
    foreach ($headers as $header){
        $fieldName = $header['Field'];
        
        // Skip certain fields that shouldn't be filtered
        if (!in_array($fieldName, ['password', 'salt'])){
            $columns[] = [
                'value' => $fieldName,
                'label' => ucfirst(str_replace(['_', 'At'], [' ', ' At'], $fieldName))
            ];
        }
    }
    
    return $columns;
}

/**
 * Create a joined table view from two tables.
 *
 * @param string $primaryTable - Name of the primary table
 * @param string $secondaryTable - Name of the secondary table
 * @param string $joinColumn - Column to join on
 * @return string HTML table output or error message
 */
function createJoinedTable($primaryTable, $secondaryTable, $joinColumn){
    $db = Database::getInstance();
    
    // Determine join condition based on tables
    $joinCondition = '';
    if ($primaryTable === 'users' && $secondaryTable === 'userPreferences'){
        $joinCondition = "users.id = userPreferences.uid";
    } elseif ($primaryTable === 'users' && $secondaryTable === 'userAgents'){
        $joinCondition = "users.id = userAgents.userId";
    } else {
        // Generic join using the selected column
        $joinCondition = "{$primaryTable}.{$joinColumn} = {$secondaryTable}.{$joinColumn}";
    }
    
    $query = "SELECT 
                {$primaryTable}.*, 
                {$secondaryTable}.*
                FROM {$primaryTable} 
                LEFT JOIN {$secondaryTable} ON {$joinCondition}
                ORDER BY {$primaryTable}.id";
    
    try {
        $result = $db->query($query);
        
        if (empty($result)){
            return '<p class="no-data">No connected data found between these tables.</p>';
        }
        
        // Build HTML table
        $html = '<table class="tablePrintout">';
        
        // Headers
        $html .= '<thead><tr>';
        foreach (array_keys($result[0]) as $column){
            $html .= '<th>' . htmlspecialchars($column) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Data rows
        $html .= '<tbody>';
        foreach ($result as $row){
            $html .= '<tr>';
            foreach ($row as $cell){
                $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        
        return $html;
        
    } catch (Exception $e){
        return '<p class="error">Error creating joined view: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// some ajax request caught
if (isset($_POST['action'])){

    $user = User::current();
    if ($user){
        $user->recordActivity();
    }

    switch ($_POST['action']){
        case 'getValue':
            $tableName = urldecode($_POST['value']);
            $table = Table::getInstance($tableName);
            $tableRenderer = TableRenderer::getInstance($table);
            
            // Check for filter parameters
            $filterColumn = isset($_POST['filterColumn']) ? urldecode($_POST['filterColumn']) : null;
            $filterOperator = isset($_POST['filterOperator']) ? urldecode($_POST['filterOperator']) : null;
            $filterValue = isset($_POST['filterValue']) ? urldecode($_POST['filterValue']) : null;
            
            // Debug logging
            
            if ($filterColumn && $filterOperator && $filterValue !== null && $filterValue !== ''){
                // Apply filter
                $result = applyTableFilter($table, $filterColumn, $filterOperator, $filterValue);
            }
            else {
                // No filter, get all data
                $result = $table->selectAll();
            }

            // Capture the HTML output
            ob_start();
            $tableRenderer->displayTable($result, true);
            $html = ob_get_clean();

            ApiResponse::success($html, 'Successfully sent table.');
            break;

        case 'getTableColumns':
            $tableName = urldecode($_POST['tableName']);
            
            // Check if table exists
            if (!Database::getInstance()->tableExists($tableName)){
                ApiResponse::failure("Table does not exist: $tableName");
                break;
            }
            
            $table = Table::getInstance($tableName);
            $columns = getTableColumns($table);
            
            ApiResponse::success($columns, 'Successfully retrieved table columns.');
            break;

        case 'tableActionChoice':
            $action = urldecode($_POST['value']);
            $tableName = urldecode($_POST['tableName']);
            $table = Table::getInstance($tableName);
            $tableRenderer = TableRenderer::getInstance($table);
            $result = $table->selectAll();

            // Capture the HTML output
            ob_start();
            $tableRenderer->displayTableForm($result, $action, $_SESSION['id'] ?? 0);
            $html = ob_get_clean();

            ApiResponse::success($html, 'Successfully sent table form.');

            case 'loadTwoTables':
                $primaryTable = urldecode($_POST['primaryTable']);
                $secondaryTable = urldecode($_POST['secondaryTable']);
                $joinColumn = urldecode($_POST['joinColumn']);
    
                // Load primary table
                $table1 = Table::getInstance($primaryTable);
                $tableRenderer1 = TableRenderer::getInstance($table1);
                $result1 = $table1->selectAll();
    
                ob_start();
                $tableRenderer1->displayTable($result1, true);
                $primaryHtml = ob_get_clean();
    
                // Load secondary table
                $table2 = Table::getInstance($secondaryTable);
                $tableRenderer2 = TableRenderer::getInstance($table2);
                $result2 = $table2->selectAll();
    
                ob_start();
                $tableRenderer2->displayTable($result2, true);
                $secondaryHtml = ob_get_clean();
    
                // Create joined view
                $joinedHtml = createJoinedTable($primaryTable, $secondaryTable, $joinColumn);
    
                ApiResponse::success([
                    'primary' => $primaryHtml,
                    'secondary' => $secondaryHtml,
                    'joined' => $joinedHtml,
                    'primaryTitle' => ucfirst($primaryTable),
                    'secondaryTitle' => ucfirst($secondaryTable)
                ], 'Successfully loaded connected tables.');
        
    }

    exit;
}

// If no action provided, return error
ApiResponse::failure('No action provided');