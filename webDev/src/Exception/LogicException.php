<?php
/**
 * LogicException Class File
 *
 * This file contains the `LogicException` class, which handles exceptions that occur due to application logic errors,
 * such as invalid state transitions or unexpected null values. It provides detailed context about the logic error,
 * including the reason, expected state, and actual state.
 *
 * @file LogicException.php
 * @since 0.6
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see AppException, ExceptionType
 * @todo Add more logic error context if needed
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
 * Handles logic-related exceptions.
 * 
 * This class is used for exceptions that occur due to application logic errors,
 * such as invalid state transitions or unexpected null values. It provides detailed
 * context about the logic error, including the reason, expected state, and actual state.
 * 
 * ### Features:
 * - Captures the reason for the logic error.
 * - Logs the expected and actual states for debugging purposes.
 * - Supports exception chaining to preserve the original exception context.
 *
 * @package Exception
 * @since 0.4
 * @see AppException, ExceptionType
 * @todo Add more logic error context if needed
 */
final class LogicException extends AppException {
    private string $reason; // The reason for the logic error
    private ?string $expectedState; // The expected state of the application
    private ?string $actualState; // The actual state of the application

    /**
     * Constructs a new LogicException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the logic error, such as the reason, expected state, and actual state.
     * 
     * ### Example usage:
     * ```php
     * throw new LogicException(
     *     message: "Invalid state transition",
     *     code: 500,
     *     reason: "State transition not allowed",
     *     expectedState: "Active",
     *     actualState: "Inactive",
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param string $reason The reason for the logic error.
     * @param ?string $expectedState The expected state of the application (default is 'Not specified').
     * @param ?string $actualState The actual state of the application (default is 'Not specified').
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        string $reason,
        ?string $expectedState = null,
        ?string $actualState = null,
        ?Throwable $previous = null,
    ){
        parent::__construct($message, $code, ExceptionType::LOGIC_EXCEPTION, $previous);

        $this->reason = $reason;
        $this->expectedState = $expectedState ?? 'Not specified';
        $this->actualState = $actualState ?? 'Not specified';
    }

    /**
     * Handles logic-related exceptions.
     * 
     * Logs detailed information about the exception, including the reason, expected state,
     * actual state, and the function where the exception originated. This information is
     * useful for debugging logic-related issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - Reason, expected state, and actual state.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     */
    final protected function logicException(): void {
        $fnName = parent::getFailedFunctionName();

        // Log the logic exception details
        Logger::log(
            "[LOGIC] " . $this->getMessage() .
            " | Function: $fnName" .
            " | Reason: " . $this->reason .
            " | Expected State: " . $this->expectedState .
            " | Actual State: " . $this->actualState,
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->line,
            $this->file
        );
    }
}