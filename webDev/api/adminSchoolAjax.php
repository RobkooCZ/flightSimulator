<?php
declare(strict_types=1);

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
