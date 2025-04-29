<?php
/**
 * ValidationException Class File
 *
 * This file contains the `ValidationException` class, which handles exceptions that occur during input validation,
 * such as invalid form data or failed CSRF token validation. It provides detailed context about the validation error,
 * including the field name, failure type, and error message.
 *
 * @file ValidationException.php
 * @since 0.6
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see AppException, ExceptionType, ValidationFailureType
 * @todo Add more validation error context if needed
 */

declare(strict_types=1);

namespace WebDev\Exception;

# php
use Throwable;

# eenums
use WebDev\Exception\Enum\ExceptionType;
use WebDev\Exception\Enum\ValidationFailureType;

# logger stuff
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers; 

/**
 * Handles validation-related exceptions.
 * 
 * This class is used for exceptions that occur during input validation, such as invalid
 * form data or failed CSRF token validation. It provides detailed context about the
 * validation error, including the field name, failure type, and error message.
 * 
 * ### Features:
 * - Captures the field name that triggered the exception.
 * - Logs the validation failure type and error message.
 * - Supports exception chaining to preserve the original exception context.
 *
 * @package Exception
 * @since 0.4
 * @see AppException, ExceptionType, ValidationFailureType
 * @todo Add more validation error context if needed
 */
final class ValidationException extends AppException {
    private ?string $fieldName; // The name of the field which triggered the exception
    private ValidationFailureType $failureType; // Enum for all kinds of validation failure types
    private ?string $errorMessage; // Error message to log alongside other data

    /**
     * Constructs a new ValidationException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the validation error, such as the field name, failure type, and
     * error message.
     * 
     * ### Example usage:
     * ```php
     * throw new ValidationException(
     *     message: "Invalid username provided",
     *     code: 422,
     *     failureType: ValidationFailureType::INVALID_USERNAME,
     *     fieldName: "username",
     *     errorMessage: "The username contains invalid characters.",
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param ValidationFailureType $failureType The type of validation failure.
     * @param ?string $fieldName The name of the field which triggered the exception (default is null).
     * @param ?string $errorMessage Additional error message to log (default is null).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        ValidationFailureType $failureType,
        ?string $fieldName = null,
        ?string $errorMessage = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::VALIDATION_EXCEPTION, $previous);

        // Initialize validation-specific properties
        $this->fieldName = $fieldName ?? 'No field name provided.';
        $this->failureType = $failureType;
        $this->errorMessage = $errorMessage ?? 'No error message provided.';
    }

    /**
     * Handles validation-related exceptions.
     * 
     * Logs detailed information about the exception, including the field name, failure type,
     * error message, and the function where the exception originated. This information is
     * useful for debugging validation-related issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - Field name, failure type, and error message.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     * @since 0.2.1
     */
    final protected function validationException(): void {
        // Log the validation error details using Logger::writeLog
        Logger::getInstance()->log(
            "[VALIDATION] " . $this->getMessage() .
            " | Field: " . $this->fieldName .
            " | Failure Type: " . $this->failureType->value .
            " | Error Message: " . $this->errorMessage,
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}