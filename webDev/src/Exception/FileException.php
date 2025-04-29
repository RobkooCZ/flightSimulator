<?php
/**
 * FileException Class File
 *
 * This file contains the `FileException` class, which handles exceptions that occur during file operations,
 * such as reading, writing, or deleting files. It provides detailed context about the file error, including
 * the file path, action, error message, and error code.
 *
 * @file FileException.php
 * @since 0.6
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see AppException, ExceptionType
 * @todo Add more file error context if needed
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
 * Handles file-related exceptions.
 * 
 * This class is used for exceptions that occur during file operations, such as
 * reading, writing, or deleting files. It provides detailed context about the
 * file error, including the file path, action, error message, and error code.
 * 
 * ### Features:
 * - Captures the file path and action that caused the exception.
 * - Logs the error message and error code for debugging purposes.
 * - Supports exception chaining to preserve the original exception context.
 *
 * @package Exception
 * @since 0.4
 * @see AppException, ExceptionType
 * @todo Add more file error context if needed
 */
final class FileException extends AppException {
    private string $filePath; // The file path that caused the exception
    private string $action; // The action being performed (e.g., read, write, delete)
    private ?string $fileErrorMessage; // The error message associated with the file operation
    private ?int $fileErrorCode; // The error code associated with the file operation

    /**
     * Constructs a new FileException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the file error, such as the file path, action, error message,
     * and error code.
     * 
     * ### Example usage:
     * ```php
     * throw new FileException(
     *     message: "Failed to read the file",
     *     code: 500,
     *     filePath: "/path/to/file.txt",
     *     action: "read",
     *     fileErrorMessage: "Permission denied",
     *     fileErrorCode: 13,
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param string $filePath The file path that caused the exception.
     * @param string $action The action being performed (e.g., read, write, delete).
     * @param ?string $fileErrorMessage The error message associated with the file operation (default is 'No specific error message').
     * @param ?int $fileErrorCode The error code associated with the file operation (default is 0).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        string $filePath,
        string $action,
        ?string $fileErrorMessage = null,
        ?int $fileErrorCode = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::FILE_EXCEPTION, $previous);

        $this->filePath = $filePath;
        $this->action = $action;
        $this->fileErrorMessage = $fileErrorMessage ?? 'No specific error message';
        $this->fileErrorCode = $fileErrorCode ?? 0;
    }

    /**
     * Handles file-related exceptions.
     * 
     * Logs detailed information about the exception, including the file path, action,
     * error message, error code, and the function where the exception originated.
     * This information is useful for debugging file-related issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - File path, action, error message, and error code.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     * @since 0.2.1
     */
    final protected function fileException(): void {
        $fnName = parent::getFailedFunctionName();

        // Log the file error details
        Logger::log(
            "[FILE] " . $this->getMessage() .
            " | Function: $fnName" .
            " | File Path: " . $this->filePath .
            " | Action: " . $this->action .
            " | Error Message: " . $this->fileErrorMessage .
            " | Error Code: " . $this->fileErrorCode,
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}