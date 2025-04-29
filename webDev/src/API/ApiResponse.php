<?php
/**
 * Unified API response utility for AJAX endpoints.
 *
 * Provides static methods for sending standardized JSON responses for success and failure cases.
 * Integrates with backend constants to ensure consistent response keys across the application.
 *
 * @file ApiResponse.php
 * @since 0.7.2
 * @package API
 * @author Robkoo
 * @license TBD
 * @version 0.7.2.1
 * @see /webDev/assets/constants/constants.php, /webDev/api/adminSchoolAjax.php
 * @todo Add support for additional response metadata and logging.
 */

declare(strict_types=1);

namespace WebDev\API;

use WebDev\Assets\ConstantsLoader;

/**
 * Utility class for standardized API JSON responses.
 *
 * Use this class to send all AJAX responses in a consistent structure.
 *
 * @package API
 * @since 0.7.2
 * @see ConstantsLoader
 */
class ApiResponse {
    /**
     * Send a standardized success JSON response and terminate execution.
     *
     * @param mixed $data The data to include in the response (HTML, array, etc.)
     * @param string $message Optional human-readable message.
     * @return never This method always terminates script execution.
     *
     * ```php
     * ApiResponse::success('<table>...</table>', 'Table loaded.');
     * // Output:
     * // {
     * //   "success": true,
     * //   "message": "Table loaded.",
     * //   "data": "<table>...</table>"
     * // }
     * ```
     */
    public static function success(
        mixed $data = null, 
        string $message = ''
    ): never {
        $apiAjax = ConstantsLoader::getApiAjax();

        echo json_encode([
            $apiAjax->success->success => true,
            $apiAjax->success->message => $message,
            $apiAjax->success->data => $data
        ]);
        
        exit;
    }

    /**
     * Send a standardized failure JSON response and terminate execution.
     *
     * @param string $message Human-readable error message.
     * @param int $code HTTP status code (default 400).
     * @param ?string $errors Optional error details.
     * @return never This method always terminates script execution.
     *
     * ```php
     * ApiResponse::failure('Invalid request', 400, 'Missing parameters');
     * // Output:
     * // {
     * //   "success": false,
     * //   "message": "Invalid request",
     * //   "error": "Missing parameters"
     * // }
     * ```
     */
    public static function failure(
        string $message = '',
        int $code = 400,
        ?string $errors = null
    ): never {
        $apiAjax = ConstantsLoader::getApiAjax();

        http_response_code($code);

        echo json_encode([
            $apiAjax->failure->success => false,
            $apiAjax->failure->message => $message,
            $apiAjax->failure->error  => $errors
        ]);

        exit;
    }
}