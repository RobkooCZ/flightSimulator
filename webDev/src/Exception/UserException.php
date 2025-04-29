<?php
/**
 * UserException Class File
 *
 * This file contains the `UserException` class, which handles exceptions that are user-facing,
 * such as invalid input or authentication errors. It sets a session message and logs the error.
 *
 * @file UserException.php
 * @since 0.6
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see AppException, ExceptionType, PHPException
 * @todo Add more user error context if needed
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
 * Handles user-related exceptions.
 * 
 * This class is used for exceptions that are user-facing, such as invalid input
 * or authentication errors. It sets a session message and logs the error.
 *
 * @package Exception
 * @since 0.4
 * @see AppException, ExceptionType, PHPException
 * @todo Add more user error context if needed
 */
final class UserException extends AppException {
    /**
     * Constructs a new UserException instance.
     * 
     * This constructor initializes the exception with a message, code, and an optional previous exception.
     * The exception type is always set to `ExceptionType::USER_EXCEPTION`.
     * 
     * ### Example usage:
     * ```php
     * throw new UserException(
     *     message: "Failed to connect to the database",
     *     code: 500,
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
    ){
        parent::__construct($message, $code, ExceptionType::USER_EXCEPTION, $previous);
    }

    /**
     * Handles user-related exceptions.
     * 
     * This method ensures that the session is started before setting a session message
     * with the exception message. It also logs the exception details, including the
     * function where the exception originated, for debugging purposes.
     * 
     * ### Behavior:
     * - Starts the session if it is not already active.
     * - Throws a `PHPException` if the session cannot be started.
     * - Sets a session message with the exception message for user feedback.
     * - Logs the exception details, including the function name, exception code, file, and line.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     * @throws PHPException If the session cannot be started.
     */
    final protected function userException(): void {
        // make sure the session is started
        if (session_status() === PHP_SESSION_NONE){
            // handle the case where session can't be started
            if (!@session_start()){
                throw new PHPException(
                    "Failed to start session for userException",
                    500
                );
                return;
            }
        }
        // for now just display to the user
        $_SESSION['message'] = $this->getMessage();
        // get the function name
        $functionName = parent::getFailedFunctionName();
        // log it to the console, just for fun
        Logger::log(
            "[USER] " . $this->getMessage() .
            " | Function: " . $functionName .
            " | Code: " . $this->getCode(),
            LogLevel::WARNING,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}