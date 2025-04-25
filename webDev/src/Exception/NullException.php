<?php
/**
 * NullException Class File
 *
 * This file contains the `NullException` class, which handles exceptions that occur when a null value is encountered
 * where it is not expected. It provides detailed context about the null error, including the expected value and the function where the exception occurred.
 *
 * @file NullException.php
 * @since 0.2.2
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see AppException, ExceptionType
 * @todo Add more null error context if needed
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
 * Handles null-related exceptions.
 * 
 * This class is used for exceptions that occur when a null value is encountered
 * where it is not expected. It provides detailed context about the null error,
 * including the expected value and the function where the exception occurred.
 * 
 * ### Features:
 * - Captures the expected value that was not null.
 * - Logs detailed information about the null exception for debugging purposes.
 * - Supports exception chaining to preserve the original exception context.
 *
 * @package Exception
 * @since 0.2.2
 * @see AppException, ExceptionType
 * @todo Add more null error context if needed
 */
final class NullException extends AppException {

    /**
     * @var string|null The expected value that was not null.
     */
    private ?string $exceptedValue = null;

    /**
     * Constructs a new NullException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the null error, such as the expected value and the previous exception.
     * 
     * ### Example usage:
     * ```php
     * throw new NullException(
     *     message: "Unexpected null value encountered",
     *     code: 400,
     *     expectedValue: "Non-null string",
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param string|null $expectedValue The expected value that was not null (default is 'Not provided').
     * @param Throwable|null $previous The previous throwable used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        ?string $expectedValue = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::NULL_EXCEPTION, $previous);

        $this->exceptedValue = $expectedValue ?? 'Not provided';
    }

    /**
     * Handles null-related exceptions.
     * 
     * Logs detailed information about the exception, including the message, function name,
     * expected value, file, and line number. This information is useful for debugging
     * null-related issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - Expected value, file, and line number.
     * 
     * @return void
     */
    final protected function nullException(): void {
        $functionName = parent::getFailedFunctionName();

        Logger::log(
            "[NULL] " . $this->getMessage() . 
            " | Function: " . $functionName .
            " | Expected value: " . $this->exceptedValue .
            " | File: " . $this->getFile() . 
            " | Line: " . $this->getLine(),
            LogLevel::ALERT,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}