<?php
/**
 * DatabaseException Class File
 *
 * This file contains the `DatabaseException` class, which handles exceptions that occur during database operations,
 * such as connection failures, query errors, or other database-related issues. It provides detailed context about
 * the error, including the query, database type, error code, and error message.
 *
 * @file DatabaseException.php
 * @since 0.6
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see AppException, ExceptionType
 * @todo Add more database error context if needed
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
 * Handles database-related exceptions.
 * 
 * This class is used for exceptions that occur during database operations, such as
 * connection failures, query errors, or other database-related issues. It provides
 * detailed context about the error, including the query, database type, error code,
 * and error message.
 * 
 * ### Features:
 * - Captures the query that caused the exception.
 * - Logs the database error code and message.
 * - Includes the function name where the exception originated.
 * - Supports exception chaining for preserving the original exception context.
 *
 * @package Exception
 * @since 0.4
 * @see AppException, ExceptionType
 * @todo Add more database error context if needed
 */
final class DatabaseException extends AppException {
    private ?string $query; // The SQL query that caused the exception
    private string $dbType = 'PDO'; // The type of database (default is PDO)
    private ?int $dbErrorCode; // The database-specific error code
    private ?string $dbErrorMessage; // The database-specific error message

    /**
     * Constructs a new DatabaseException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the database error, such as the query, error code, and error message.
     * 
     * ### Example usage:
     * ```php
     * throw new DatabaseException(
     *     message: "Failed to execute query",
     *     code: 500,
     *     previous: $previousException,
     *     query: "SELECT * FROM users WHERE id = :id",
     *     dbErrorCode: 1045,
     *     dbErrorMessage: "Access denied for user 'root'@'localhost'"
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     * @param ?string $query The SQL query that caused the exception (default is null).
     * @param ?int $dbErrorCode The database-specific error code (default is null).
     * @param ?string $dbErrorMessage The database-specific error message (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
        ?string $query = null,
        ?int $dbErrorCode = null,
        ?string $dbErrorMessage = null
    ){
        parent::__construct($message, $code, ExceptionType::DATABASE_EXCEPTION, $previous);

        // Initialize database-specific properties
        $this->query = $query ?? 'No query specified.';
        $this->dbErrorCode = $dbErrorCode ?? 0;
        $this->dbErrorMessage = $dbErrorMessage ?? 'No error message specified.';
    }

    /**
     * Handles database-related exceptions.
     * 
     * Logs detailed information about the exception, including the query, database type,
     * error code, error message, and the function where the exception originated.
     * 
     * @return void
     * @since 0.2.1
     */
    final protected function databaseException(): void {
        // Get the function name where the exception was thrown
        $functionName = parent::getFailedFunctionName();

        // Log the exception details
        Logger::getInstance()->log(
            "[DATABASE] " . $this->getMessage() . 
            " | Function: " . $functionName .
            " | Query: " . $this->query . 
            " | Database Type: " . $this->dbType . 
            " | Error Code: " . $this->dbErrorCode . 
            " | Error Message: " . $this->dbErrorMessage,
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}