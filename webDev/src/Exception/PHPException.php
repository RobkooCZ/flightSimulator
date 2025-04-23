<?php
/**
 * PHPException Class File
 *
 * This file contains the `PHPException` class, which handles exceptions that occur due to PHP built-in functions or extensions.
 * It provides detailed context about the error, including error details, error codes, and the function where the exception originated.
 * This information is logged for debugging purposes.
 *
 * @file PHPException.php
 * @since 0.2.1
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see AppException, ExceptionType
 * @todo Add more PHP error context if needed
 */

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
 *
 * @package Exception
 * @since 0.2.1
 * @see AppException, ExceptionType
 * @todo Add more PHP error context if needed
 */
final class PHPException extends AppException {
    private ?string $errorDetails; // Additional details about the PHP error
    private ?int $errorCode; // The PHP-specific error code

    /**
     * Constructs a new PHPException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the PHP error, such as error details and error codes.
     * 
     * ### Example usage:
     * ```php
     * throw new PHPException(
     *     message: "Invalid argument supplied to preg_match",
     *     code: 500,
     *     errorDetails: "Invalid regular expression",
     *     errorCode: 2,
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param ?string $errorDetails Additional details about the PHP error (default is null).
     * @param ?int $errorCode The PHP-specific error code (default is null).
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
     * @since 0.2.1
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