<?php
/**
 * APIException Class File
 *
 * This file contains the `APIException` class, which handles exceptions that occur during API interactions.
 * It provides detailed context about API errors, including endpoint, HTTP method, response code, failure reason, and API name.
 *
 * @file APIException.php
 * @since 0.6
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see AppException, ExceptionType
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
 * Handles API-related exceptions.
 *
 * This class is used for exceptions that occur during API interactions, such as
 * failed HTTP requests, invalid API responses, or rate limit violations. It provides
 * detailed context about the API error, including the endpoint, HTTP method, response
 * code, failure reason, and the API name.
 *
 * ### Features:
 * - Captures the endpoint and HTTP method that caused the exception.
 * - Logs the response code and failure reason for debugging purposes.
 * - Supports exception chaining to preserve the original exception context.
 *
 * @package Exception
 * @since 0.4
 * @see AppException, ExceptionType
 */
final class APIException extends AppException {
    private string $endpoint; // The API endpoint that caused the exception
    private string $method; // The HTTP method used for the API request
    private ?int $responseCode; // The HTTP response code returned by the API
    private string $failureReason; // The reason for the API failure
    private ?string $apiName; // The name of the API (optional)

    /**
     * Constructs a new APIException instance.
     *
     * This constructor initializes the exception with a message, code, and optional
     * details about the API error, such as the endpoint, HTTP method, response code,
     * failure reason, and API name.
     *
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param string $endpoint The API endpoint that caused the exception.
     * @param string $method The HTTP method used for the API request (e.g., GET, POST).
     * @param string $failureReason The reason for the API failure.
     * @param ?int $responseCode The HTTP response code returned by the API (default is null).
     * @param ?string $apiName The name of the API (default is 'Internal').
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     *
     * @since 0.2.1
     * @see ExceptionType
     *
     * ```php
     * throw new APIException(
     *     message: "Failed to fetch data from API",
     *     code: 500,
     *     endpoint: "/v1/resource",
     *     method: "GET",
     *     failureReason: "Internal Server Error",
     *     responseCode: 500,
     *     apiName: "ExternalService",
     *     previous: $previousException
     * );
     * ```
     */
    final public function __construct(
        string $message,
        int $code = 0,
        string $endpoint,
        string $method,
        string $failureReason,
        ?int $responseCode = null,
        ?string $apiName = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::API_EXCEPTION, $previous);

        $this->endpoint = $endpoint;
        $this->method = strtoupper($method);
        $this->responseCode = $responseCode;
        $this->failureReason = $failureReason;
        $this->apiName = $apiName ?? 'Internal';
    }

    /**
     * Handles API-related exceptions.
     *
     * This method logs detailed information about API-related errors, including the
     * endpoint, HTTP method, response code, failure reason, and the function where
     * the exception originated. This information is useful for debugging API issues.
     *
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - API name, endpoint, and HTTP method.
     * - Response code and failure reason.
     * - File, line, and timestamp of the exception.
     *
     * @return void
     * @since 0.2.1
     */
    final protected function apiException(): void {
        $fnName = parent::getFailedFunctionName();

        Logger::log(
            "[API] " . $this->getMessage() .
            " | Function: $fnName" .
            " | API: " . $this->apiName .
            " | Endpoint: " . $this->endpoint .
            " | Method: " . $this->method .
            " | Response Code: " . ($this->responseCode ?? 'N/A') .
            " | Reason: " . $this->failureReason,
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}