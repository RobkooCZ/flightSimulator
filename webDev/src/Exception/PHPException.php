<?php

declare(strict_types=1);

namespace WebDev\Exception;

# php
use Throwable;

# exception type enum
use WebDev\Exception\Enum\ExceptionType;

# logger stuff
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers; 

/**
 * Handles PHP-related exceptions.
 * 
 * This class is used for exceptions that occur due to PHP built-in functions or extensions.
 * It provides detailed context about the error, including error details, error codes, and
 * the function where the exception originated. This information is logged for debugging purposes.
 * 
 * ### Features:
 * - Captures error details and PHP error codes.
 * - Logs the exception message along with error details for easier debugging.
 * - Supports exception chaining to preserve the original exception context.
 */
final class PHPException extends AppException {
    private ?string $errorDetails; // Additional details about the PHP error
    private ?int $errorCode; // The PHP-specific error code

    /**
     * Constructs a new AuthorizationException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the authorization failure, such as the user's role, resource, and action attempted.
     * 
     * ### Example usage:
     * ```php
     * throw new AuthorizationException(
     *     message: "Access denied to admin page",
     *     code: 403,
     *     userRole: "guest",
     *     resource: "/admin",
     *     actionAttempted: "view",
     *     requiredRole: "admin",
     *     ipv4: $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
     *     userId: $_SESSION['id'] ?? null, // Pass null if the user is not logged in
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param string $userRole The role of the user attempting the action.
     * @param string $resource The resource the user tried to access.
     * @param string $actionAttempted The action the user attempted to perform.
     * @param ?string $requiredRole The role required to perform the action (default is null).
     * @param ?string $ipv4 The user's IP address (default is null).
     * @param ?int $userId The ID of the user attempting the action (default is null).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        ?string $errorDetails = null,
        ?int $errorCode = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::PHP_EXCEPTION, $previous);

        $this->errorDetails = $errorDetails;
        $this->errorCode = $errorCode;
    }

    /**
     * Handles PHP-related exceptions.
     * 
     * Logs detailed information about the exception, including the message, error details,
     * PHP error code, and the function where the exception originated. This information
     * is useful for debugging PHP-related issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - Error details and PHP error code.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     */
    final protected function phpException(): void {
        // get the function name
        $functionName = parent::getFailedFunctionName();

        // Log the PHP exception
        Logger::log(
            "[PHP] " . $this->getMessage() .
            " | Function: " . $functionName .
            " | Error Details: " . $this->errorDetails .
            " | PHP Error Code: " . $this->errorCode,
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}