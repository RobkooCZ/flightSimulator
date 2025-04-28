<?php
/**
 * Handles AJAX requests for the School Admin Page.
 *
 * Processes AJAX POST requests for table data and action forms, returning HTML fragments
 * in a standardized API response structure. Integrates with user authentication, table rendering,
 * and unified API response handling.
 *
 * @file adminSchoolAjax.php
 * @since TBD
 * @package API
 * @author Robkoo
 * @license TBD
 * @version TBD
 * @see /webDev/src/API/ApiResponse.php, /webDev/assets/constants/constants.php, /webDev/public/adminSchoolPage.php
 * @todo Add CSRF protection, more granular error handling, and logging.
 */
use WebDev\Bootstrap;
Bootstrap::init();

include __DIR__ . '/../assets/constants/constants.php';

use WebDev\API\ApiResponse;

session_start();
header('Content-Type: application/json');

// Database
use WebDev\Database\Table;

// UI
use WebDev\UI\TableRenderer;

// User
use WebDev\Auth\User;

// some ajax request caught
if (isset($_POST['action'])){

    $user = User::current();
    if ($user) {
        $user->recordActivity();
    }

    switch ($_POST['action']){

        case 'getValue':
            $tableName = urldecode($_POST['value']);
            $table = Table::getInstance($tableName);
            $tableRenderer = TableRenderer::getInstance($table);
            $result = $table->selectAll();

            // Capture the HTML output
            ob_start();
            $tableRenderer->displayTable($result, true);
            $html = ob_get_clean();

            ApiResponse::success($html, 'Successfully sent table.');
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
            break;
    }

    exit;
}

// If no action provided, return error
ApiResponse::failure('No action provided');
exit;