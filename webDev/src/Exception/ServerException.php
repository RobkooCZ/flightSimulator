<?php
/**
 * ServerException Class File
 *
 * This file contains the `ServerException` class, which handles exceptions that occur on the server side,
 * such as internal errors or unexpected failures. It provides detailed context about the server environment
 * and logs the error for debugging purposes.
 *
 * @file ServerException.php
 * @since 0.6
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see AppException, ExceptionType
 * @todo Add more server error context if needed
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
 * Handles server-related exceptions.
 * 
 * This class is used for exceptions that occur on the server side, such as
 * internal errors or unexpected failures. It provides detailed context about
 * the server environment and logs the error for debugging purposes.
 * 
 * ### Features:
 * - Captures server-specific details such as hostname, environment, PHP version, memory usage, and request information.
 * - Logs the exception message along with server details for easier debugging.
 * - Supports exception chaining to preserve the original exception context.
 *
 * @package Exception
 * @since 0.4
 * @see AppException, ExceptionType
 * @todo Add more server error context if needed
 */
final class ServerException extends AppException {
    private ?string $serverName; // The name of the server where the exception occurred
    private ?string $environment; // The application environment (e.g., production, development)
    private ?string $phpVersion; // The PHP version running on the server
    private ?int $memoryUsage; // The memory usage at the time of the exception
    private ?string $requestUrl; // The URL of the request that caused the exception
    private ?string $requestMethod; // The HTTP method of the request (e.g., GET, POST)

    /**
     * Constructs a new ServerException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * server-specific details such as hostname, environment, PHP version, memory usage,
     * and request information.
     * 
     * ### Example usage:
     * ```php
     * throw new ServerException(
     *     message: "Failed to process the request",
     *     code: 500,
     *     previous: $previousException,
     *     serverName: "MyServer",
     *     environment: "production",
     *     phpVersion: "8.1.0",
     *     requestUrl: "https://example.com/api",
     *     requestMethod: "POST"
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     * @param ?string $serverName The name of the server where the exception occurred (default is null).
     * @param ?string $environment The application environment (e.g., production, development) (default is null).
     * @param ?string $phpVersion The PHP version running on the server (default is null).
     * @param ?string $requestUrl The URL of the request that caused the exception (default is null).
     * @param ?string $requestMethod The HTTP method of the request (e.g., GET, POST) (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
        ?string $serverName = null,
        ?string $environment = null,
        ?string $phpVersion = null,
        ?string $requestUrl = null,
        ?string $requestMethod = null
    ){
        parent::__construct($message, $code, ExceptionType::SERVER_EXCEPTION, $previous);

        // Initialize server-specific properties with fallback values
        $this->serverName = $serverName ?? (gethostname() ?: 'Unknown'); // Default to server hostname
        $this->environment = $environment ?? (getenv('APP_ENV') ?: 'production');
        $this->phpVersion = $phpVersion ?? phpversion();
        $this->memoryUsage = memory_get_usage();
        $this->requestUrl = $requestUrl ?? 'N/A';
        $this->requestMethod = $requestMethod ?? 'N/A';
    }

    /**
     * Handles server-related exceptions.
     * 
     * Logs detailed information about the exception, including the message, server details,
     * and the function where the exception originated. This information is useful for debugging
     * server-side issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - Server name, environment, PHP version, and memory usage.
     * - Request URL and HTTP method.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     * @since 0.2.1
     */
    final protected function serverException(): void {
        // Get the function name where the exception was thrown
        $functionName = parent::getFailedFunctionName();

        // Log the server exception details
        Logger::getInstance()->log(
            "[SERVER] " . $this->getMessage() .
            " | Function: " . $functionName .
            " | Server: " . $this->serverName .
            " | Environment: " . $this->environment .
            " | PHP Version: " . $this->phpVersion .
            " | Memory Usage: " . $this->memoryUsage .
            " | Request Method: " . $this->requestMethod .
            " | Request URL: " . $this->requestUrl,
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}