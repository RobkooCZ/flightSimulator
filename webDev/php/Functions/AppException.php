<?php

declare(strict_types=1);

namespace WebDev\Functions;

use Exception;
use Throwable;

/**
 * Enum representing different types of exceptions.
 * 
 * This enum is used to categorize exceptions into meaningful types, such as user-related,
 * server-related, database-related, API-related, and more. It provides a foundation for
 * type-safe exception handling and ensures that exceptions are consistently categorized.
 */
enum ExceptionType: string {
    // User-related issues
    case USER_EXCEPTION = 'UserException'; // General user-related exceptions
    case VALIDATION_EXCEPTION = 'ValidationException'; // Input validation errors
    case AUTHENTICATION_EXCEPTION = 'AuthenticationException'; // Authentication failures
    case AUTHORIZATION_EXCEPTION = 'AuthorizationException'; // Authorization failures
    
    // Backend or server-side issues
    case SERVER_EXCEPTION = 'ServerException'; // General server-side errors
    case DATABASE_EXCEPTION = 'DatabaseException'; // Database-related errors
    case API_EXCEPTION = 'APIException'; // API interaction errors
    case CONFIGURATION_EXCEPTION = 'ConfigurationException'; // Configuration-related errors
    case FILE_EXCEPTION = 'FileException'; // File operation errors
    
    // Internal technical errors
    case PHP_EXCEPTION = 'PHPException'; // PHP-related errors
    case LOGIC_EXCEPTION = 'LogicException'; // Application logic errors
}

/**
 * Enum representing different types of validation failures.
 * 
 * This enum is used to categorize validation errors into specific types, such as username,
 * password, and CSRF token validation failures. It provides a structured way to handle
 * validation errors and ensures that they are consistently categorized.
 */
enum ValidationFailureType: string {
    // Username-related validation failures
    case INVALID_USERNAME = 'InvalidUsername'; // Username contains invalid characters
    case USERNAME_TOO_SHORT = 'UsernameTooShort'; // Username is shorter than the minimum length
    case USERNAME_NOT_UNIQUE = 'UsernameNotUnique'; // Username is already taken

    // Password-related validation failures
    case PASSWORD_TOO_SHORT = 'PasswordTooShort'; // Password is shorter than the minimum length
    case PASSWORD_MISSING_UPPERCASE = 'PasswordMissingUppercaseLetter'; // Password lacks an uppercase letter
    case PASSWORD_MISSING_LOWERCASE = 'PasswordMissingLowercaseLetter'; // Password lacks a lowercase letter
    case PASSWORD_MISSING_NUMBER = 'PasswordMissingNumber'; // Password lacks a numeric digit
    case PASSWORD_MISSING_SPECIAL_CHAR = 'PasswordMissingSpecialCharacter'; // Password lacks a special character
    case PASSWORDS_MISMATCHED = 'PasswordsMismatched'; // Passwords do not match

    // CSRF token-related validation failures
    case CSRF_EXPIRED = 'CSRFExpired'; // CSRF token has expired
    case CSRF_INVALID = 'CSRFInvalid'; // CSRF token is invalid
    case CSRF_MISMATCHED = 'CSRFMismatched'; // CSRF token does not match
    case CSRF_MISSING = 'CSRFMissing'; // CSRF token is missing
    case CSRF_COOKIE_MISMATCHED = 'CSRFCookieMismatched'; // cookie data about CSRF is mismatched
}

/**
 * Enum representing different types of authentication actions.
 * 
 * This enum is used to categorize authentication-related actions, such as registering
 * a new user, logging in, or logging out. It provides a structured way to handle
 * authentication actions and ensures that they are consistently categorized.
 */
enum AuthenticationType: string {
    case REGISTER = 'Register'; // User registration
    case LOGIN = 'Login'; // User login
    case LOGOUT = 'Logout'; // User logout
}

/**
 * Abstract base class for application-specific exceptions.
 * 
 * This class provides a foundation for handling exceptions in a structured way.
 * Subclasses should extend this class to define specific exception types.
 */
abstract class AppException extends Exception {
    protected ExceptionType $exceptionType; // The type of exception (e.g., USER_EXCEPTION)

    /**
     * Constructor for the AppException class.
     * 
     * Initializes the exception with a message, code, type, and an optional previous exception.
     * 
     * /**
     * ### Example usage:
     * ```php
     * use WebDev\Functions\UserException;
     * use WebDev\Functions\ExceptionType;
     * 
     * throw new UserException("Invalid username or password", 401, ExceptionType::USER_EXCEPTION);
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param ExceptionType $exceptionType The type of exception (default is SERVER_EXCEPTION).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    public function __construct(
        string $message, 
        int $code = 0, 
        ExceptionType $exceptionType = ExceptionType::SERVER_EXCEPTION,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, $previous);
        $this->exceptionType = $exceptionType;
    }

    /**
     * Initializes the AppException class.
     * 
     * This method ensures that the AppException class is properly loaded and available.
     * It performs a check using `class_exists()` to verify that the class is loaded.
     * If the class is not loaded, it logs an error and throws a critical exception.
     * 
     * **Fun fact:** this function only exists as a way to trick PSR-4 into loading this whole file. 
     * This is necessary to ensure that I can use the subclasses freely, without having to put each and 
     * every one of them into separate files.
     * 
     * ### Example usage:
     * ```php
     * AppException::init();
     * ```
     * 
     * ### Logged Details:
     * - Logs a success message if the class is loaded successfully.
     * - Logs an error message and throws an exception if the class is not loaded.
     * 
     * @throws Exception If the AppException class is not loaded.
     * @return void
     */
    final public static function init(): void {
        if (!class_exists(self::class)){ 
            error_log("AppException class not loaded.");
            throw new Exception("Critical error: AppException failed to load!");
        }
        error_log("AppException initialized successfully.");
    }

    /**
     * Handles the exception based on its type.
     * 
     * This method uses a match expression to determine the appropriate handling
     * method for the exception type. Subclasses can override these methods to
     * provide custom handling logic.
     */
    final public function handle(): void {
        match ($this->exceptionType){
            ExceptionType::USER_EXCEPTION => $this->userException(),
            ExceptionType::SERVER_EXCEPTION => $this->serverException(),
            ExceptionType::DATABASE_EXCEPTION => $this->databaseException(),
            ExceptionType::PHP_EXCEPTION => $this->phpException(),
            ExceptionType::VALIDATION_EXCEPTION => $this->validationException(),
            ExceptionType::FILE_EXCEPTION => $this->fileException(),
            ExceptionType::AUTHENTICATION_EXCEPTION => $this->authenticationException(),
            ExceptionType::AUTHORIZATION_EXCEPTION => $this->authorizationException(),
            ExceptionType::API_EXCEPTION => $this->apiException(),
            ExceptionType::CONFIGURATION_EXCEPTION => $this->configurationException(),
            ExceptionType::LOGIC_EXCEPTION => $this->logicException(),
            default => throw (function(){
                error_log("[UNKNOWN EXCEPTION] " . $this->getMessage() . " | File: " . $this->getFile() . " | Line: " . $this->getLine() . " | Timestamp: " . $this->getDateAndTime());
                return new Exception("Unhandled exception type: " . $this->exceptionType->value);
            })()
        };
    }

    /**
     * Handles exceptions globally.
     * 
     * This method is designed to be used in a global exception handler. It checks if the
     * provided exception is an instance of `AppException` or its subclasses. If it is,
     * the exception's `handle()` method is called to process it based on its type.
     * 
     * If the exception is not an instance of `AppException`, it logs the exception message
     * as an unhandled exception and returns `false`.
     * 
     * ### Example usage:
     * ```php
     * set_exception_handler(function (Throwable $exception){
     *     if (!AppException::globalHandle($exception)){
     *         // Handle non-AppException cases here
     *         error_log("Unhandled exception: " . $exception->getMessage());
     *         header('Location: /error');
     *         exit;
     *     }
     * });
     * ```
     * 
     * ### Behavior:
     * - Calls the `handle()` method for `AppException` instances.
     * - Returns `false` if the throwable provided isn't an instance of itself or its subclasses.
     * 
     * @param Throwable $ae The exception to handle.
     * @return bool Returns `true` if the exception was handled, `false` otherwise.
     */
    final public static function globalHandle(Throwable $ae): bool {
        if ($ae instanceof self){
            $ae->handle();
            return true;
        } 
        else {
            return false;
        }
    }

    /**
     * Retrieves the name of the function where the exception was thrown.
     * 
     * This method uses the debug backtrace to identify the function that caused
     * the exception. It provides additional context for debugging purposes.
     * 
     * ### Example usage:
     * ```php
     * $failedFunction = $exception->getFailedFunctionName();
     * error_log("Exception occurred in function: $failedFunction");
     * ```
     * 
     * @return string The name of the function that threw the exception, or 'Unknown function' if it cannot be determined.
     */
    final public function getFailedFunctionName(): string {
        // get the backtrace 
        $backtrace = debug_backtrace(limit: 5);

        // find the name of the function that threw the exception
        /*
            Index is 2, because:
            - 0th index will be for 'getFailedFunctionName()' method
            - 1st index will be for whatever exception method was executed
            - 2nd index should be the function that threw the exception
        */
        $fnName = isset($backtrace[2]['function']) ? $backtrace[2]['function'] : 'Unknown function';

        // return it 
        return $fnName;
    }

    /**
     * Retrieves the current date and time in a standardized format.
     * 
     * This method provides a consistent way to format the date and time for all
     * exception logs. The format can be updated here to reflect any changes
     * required across the application.
     * 
     * ### Example output:
     * ```
     * 12th April 2025, 13:17:24
     * ```
     * 
     * @return string The current date and time in the standardized format.
     */
    final protected function getDateAndTime(): string {
        return date('jS F Y, H:i:s');
    }

    /**
     * Handles user-related exceptions.
     * 
     * By default, this method sets a session message with the exception message.
     * Subclasses can override this method to provide custom handling logic.
     * 
     * @return void
     */
    protected function userException(): void {
        $_SESSION['message'] = $this->getMessage();
    }

    /**
     * Handles server-related exceptions.
     * 
     * By default, this method logs the exception message to the error log.
     * Subclasses can override this method to provide custom handling logic.
     * 
     * @return void
     */
    protected function serverException(): void {
        error_log("[SERVER ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles database-related exceptions.
     * 
     * By default, this method logs the exception message to the error log.
     * Subclasses can override this method to provide custom handling logic.
     * 
     * @return void
     */
    protected function databaseException(): void {
        error_log("[DB ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles PHP-related exceptions.
     * 
     * This method is triggered for errors related to PHP built-in functions or extensions.
     * Examples include errors from `preg_match`, `mb_convert_encoding`, or `iconv`.
     * 
     * @return void
     */
    protected function phpException(): void {
        error_log("[PHP ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles validation-related exceptions.
     * 
     * This method is triggered for errors related to input validation, such as invalid form data
     * or failed CSRF token validation.
     * 
     * @return void
     */
    protected function validationException(): void {
        error_log("[VALIDATION ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles file-related exceptions.
     * 
     * This method is triggered for errors related to file operations, such as reading, writing,
     * or uploading files. Examples include file not found or permission errors.
     * 
     * @return void
     */
    protected function fileException(): void {
        error_log("[FILE I/O ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles authentication-related exceptions.
     * 
     * This method is triggered for errors related to user authentication, such as login failures,
     * invalid session tokens, or registration issues.
     * 
     * @return void
     */
    protected function authenticationException(): void {
        error_log("[AUTHENTICATION ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles authorization-related exceptions.
     * 
     * This method is triggered for errors related to user permissions, such as access to restricted
     * resources or insufficient privileges.
     * 
     * @return void
     */
    protected function authorizationException(): void {
        error_log("[AUTHORIZATION ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles API-related exceptions.
     * 
     * This method is triggered for errors related to API calls or external services, such as failed
     * HTTP requests, invalid API responses, or rate limit violations.
     * 
     * @return void
     */
    protected function apiException(): void {
        error_log("[API ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles configuration-related exceptions.
     * 
     * This method is triggered for errors related to application configuration, such as missing
     * `.env` variables or invalid settings.
     * 
     * @return void
     */
    protected function configurationException(): void {
        error_log("[CONFIGURATION ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }

    /**
     * Handles logic-related exceptions.
     * 
     * This method is triggered for errors related to application logic, such as invalid state
     * transitions or unexpected null values.
     * 
     * @return void
     */
    protected function logicException(): void {
        error_log("[LOGIC ERROR] " . $this->getMessage() . " | Exception code: " . $this->getCode());
    }
}

/*
    #######################################
    #                                     #
    #       USER RELATED SUBCLASSES       #
    #                                     #
    #######################################
*/

/**
 * Handles user-related exceptions.
 * 
 * This class is used for exceptions that are user-facing, such as invalid input
 * or authentication errors. It sets a session message and logs the error.
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
     * Ensures the session is started before setting a session message. Logs the
     * exception message for debugging purposes.
     * 
     * @return void
     */
    final protected function userException(): void {
        // make sure the session is started
        if (session_status() === PHP_SESSION_NONE){
            // handle the case where session can't be started
            if (!@session_start()){
                error_log("[SESSION ERROR] Failed to start session for UserException.");
                return;
            }
        }
        // for now just display to the user
        $_SESSION['message'] = $this->getMessage();
        // get the function name
        $functionName = parent::getFailedFunctionName();
        // log it to the console, just for fun
        error_log("[USER ERROR] " . $this->getMessage() . " | Function: " . $functionName . " | Code: " . $this->getCode() . " | File: " . $this->getFile() . " | Line: " . $this->getLine() . " | Timestamp: " . parent::getDateAndTime());
        // next update this func will be extended and tweaked with the addition of logging
    }
}

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
     */
    final protected function validationException(): void {
        // Log the validation error details
        error_log("[VALIDATION ERROR] " . $this->getMessage() .
            " | Field: " . $this->fieldName .
            " | Failure Type: " . $this->failureType->value .
            " | Error Message: " . $this->errorMessage .
            " | File: " . $this->getFile() .
            " | Line: " . $this->getLine() .
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}

/**
 * Handles authentication-related exceptions.
 * 
 * This class is used for exceptions that occur during authentication processes, such as
 * login, logout, or registration failures. It provides detailed context about the
 * authentication error, including the type of action and the failure reason.
 * 
 * ### Features:
 * - Captures the type of authentication action (e.g., login, logout, register).
 * - Logs the failure reason for debugging purposes.
 * - Supports exception chaining to preserve the original exception context.
 */
final class AuthenticationException extends AppException {
    private AuthenticationType $authType; // The type of authentication action (e.g., login, logout, register)
    private ?string $failureReason; // The reason for the authentication failure

    /**
     * Constructs a new AuthenticationException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the authentication error, such as the type of action and the failure reason.
     * 
     * ### Example usage:
     * ```php
     * throw new AuthenticationException(
     *     message: "Invalid credentials provided",
     *     code: 401,
     *     authType: AuthenticationType::LOGIN,
     *     failureReason: "The username or password is incorrect.",
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param AuthenticationType $authType The type of authentication action (e.g., login, logout, register).
     * @param ?string $failureReason The reason for the authentication failure (default is null).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        AuthenticationType $authType,
        ?string $failureReason = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::AUTHENTICATION_EXCEPTION, $previous);

        $this->authType = $authType;
        $this->failureReason = $failureReason ?? 'No reason provided.';
    }

    /**
     * Handles authentication-related exceptions.
     * 
     * Logs detailed information about the exception, including the type of authentication action,
     * the failure reason, and the function where the exception originated. This information is
     * useful for debuggin fg authentication-related issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - Authentication action type and failure reason.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     */
    final protected function authenticationException(): void {
        // Log the authentication failure details
        error_log("[AUTHENTICATION ERROR] " . $this->getMessage() .
            " | Action: " . $this->authType->value .
            " | Failure Reason: " . $this->failureReason .
            " | File: " . $this->getFile() .
            " | Line: " . $this->getLine() .
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}

/**
 * Handles authorization-related exceptions.
 * 
 * This class is used for exceptions that occur during authorization processes, such as
 * when a user attempts to access a resource or perform an action they do not have
 * permission for. It provides detailed context about the authorization failure, including
 * the action attempted, the resource, the user's role, and the required role.
 * 
 * ### Features:
 * - Captures the action attempted and the resource being accessed.
 * - Logs the user's role, required role, and IP address.
 * - Supports exception chaining to preserve the original exception context.
 */
final class AuthorizationException extends AppException {
    private string $actionAttempted; // The action the user attempted to perform
    private string $resource; // The resource the user tried to access
    private string $userRole; // The role of the user attempting the action
    private ?string $requiredRole; // The role required to perform the action
    private ?string $ipv4; // The user's IP address
    private ?int $userId; // The ID of the user attempting the action

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
     *     userRole: "user",
     *     resource: "/admin",
     *     actionAttempted: "view",
     *     requiredRole: "admin",
     *     ipv4: $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
     *     userId: $_SESSION['id'] ?? null,
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
        string $userRole,
        string $resource,
        string $actionAttempted,
        ?string $requiredRole = null,
        ?string $ipv4 = null,
        ?int $userId = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::AUTHORIZATION_EXCEPTION, $previous);

        // either set the passed ip adress and if its ::1 (the same device) set it to 127.0.0.1 or put it as unknown
        $this->ipv4 = ($ipv4 === '::1') ? '127.0.0.1' : ($ipv4 ?? 'Unknown');
        $this->userId = $userId ?? 0; // 0 - guest
        $this->userRole = $userRole;
        $this->requiredRole = $requiredRole ?? 'Not provided';
        $this->resource = $resource;
        $this->actionAttempted = $actionAttempted;
    }

    /**
     * Handles authorization-related exceptions.
     * 
     * Logs detailed information about the exception, including the user's IP address,
     * the action attempted, the resource, the user's role, and the required role. This
     * information is useful for debugging and auditing authorization failures.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The user's IP address.
     * - The action attempted and the resource.
     * - The user's role and the required role.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     */
    final protected function authorizationException(): void {
        error_log("[AUTHORIZATION ERROR] " . $this->getMessage() .
            " | IPv4: " . $this->ipv4 .
            " | Action: " . $this->actionAttempted .
            " | Resource: " . $this->resource .
            " | User ID: " . $this->userId .
            " | User Role: " . $this->userRole .
            " | Required Role: " . $this->requiredRole .
            " | File: " . $this->getFile() .
            " | Line: " . $this->getLine() .
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}

/*
    #######################################
    #                                     #
    #     BACKEND RELATED SUBCLASSES      #
    #                                     #
    #######################################
*/

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
     */
    final protected function serverException(): void {
        // Get the function name where the exception was thrown
        $functionName = parent::getFailedFunctionName();

        // Log the server error details
        error_log("[SERVER ERROR] " . $this->getMessage() .
            " | Function: " . $functionName .
            " | Server: " . $this->serverName .
            " | Environment: " . $this->environment .
            " | PHP Version: " . $this->phpVersion .
            " | Memory Usage: " . $this->memoryUsage .
            " | Request Method: " . $this->requestMethod .
            " | Request URL: " . $this->requestUrl .
            " | File: " . $this->getFile() .
            " | Line: " . $this->getLine() .
            " | Timestamp: " . parent::getDateAndTime());
    }
}

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
        $this->dbErrorCode = $dbErrorCode;
        $this->dbErrorMessage = $dbErrorMessage ?? 'No error message specified.';
    }

    /**
     * Handles database-related exceptions.
     * 
     * Logs detailed information about the exception, including the query, database type,
     * error code, error message, and the function where the exception originated.
     * 
     * @return void
     */
    final protected function databaseException(): void {
        // Get the function name where the exception was thrown
        $functionName = parent::getFailedFunctionName();

        // Log the error details
        error_log("[DATABASE ERROR] " . $this->getMessage() . 
            " | Function: " . $functionName .
            " | Query: " . $this->query . 
            " | Database Type: " . $this->dbType . 
            " | Error Code: " . $this->dbErrorCode ?? 'No error code specified.' . 
            " | Error Message: " . $this->dbErrorMessage . 
            " | File: " . $this->getFile() . 
            " | Line: " . $this->getLine() . 
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}

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
     * ### Example usage:
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
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param string $endpoint The API endpoint that caused the exception.
     * @param string $method The HTTP method used for the API request (e.g., GET, POST).
     * @param string $failureReason The reason for the API failure.
     * @param ?int $responseCode The HTTP response code returned by the API (default is null).
     * @param ?string $apiName The name of the API (default is 'Internal').
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
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
    ) {
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
     * ### Example Log Output:
     * ```
     * [API ERROR] Failed to fetch data from API
     * | Function: fetchData
     * | API: ExternalService
     * | Endpoint: /v1/resource
     * | Method: GET
     * | Response Code: 500
     * | Reason: Internal Server Error
     * | File: /path/to/file.php
     * | Line: 42
     * | Timestamp: 2025-04-13 14:30:00
     * ```
     * 
     * @return void
     */
    final protected function apiException(): void {
        $fnName = parent::getFailedFunctionName();

        error_log("[API ERROR] " . $this->getMessage() .
            " | Function: $fnName" .
            " | API: " . $this->apiName .
            " | Endpoint: " . $this->endpoint .
            " | Method: " . $this->method .
            " | Response Code: " . ($this->responseCode ?? 'N/A') .
            " | Reason: " . $this->failureReason .
            " | File: " . $this->getFile() .
            " | Line: " . $this->getLine() .
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}

/**
 * Handles configuration-related exceptions.
 * 
 * This class is used for exceptions that occur during application configuration,
 * such as missing `.env` variables, invalid settings, or misconfigured files.
 * It provides detailed context about the configuration error, including the
 * configuration key, source, expected value, and configuration path.
 * 
 * ### Features:
 * - Captures the configuration key and source that caused the exception.
 * - Logs the expected value and configuration path for debugging purposes.
 * - Supports exception chaining to preserve the original exception context.
 */
final class ConfigurationException extends AppException {
    private string $configKey; // The configuration key that caused the exception
    private string $source; // The source of the configuration (e.g., .env, config.php)
    private ?string $expected; // The expected value for the configuration key
    private ?string $configPath; // The path to the configuration file (if applicable)

    /**
     * Constructs a new ConfigurationException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the configuration error, such as the configuration key, source,
     * expected value, and configuration path.
     * 
     * ### Example usage:
     * ```php
     * throw new ConfigurationException(
     *     message: "Missing required configuration key",
     *     code: 500,
     *     configKey: "DATABASE_URL",
     *     source: ".env",
     *     expected: "A valid database connection string",
     *     configPath: "/path/to/.env",
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param string $configKey The configuration key that caused the exception.
     * @param string $source The source of the configuration (e.g., .env, config.php).
     * @param ?string $expected The expected value for the configuration key (default is 'Not specified').
     * @param ?string $configPath The path to the configuration file (default is 'Not specified').
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        string $configKey,
        string $source,
        ?string $expected = null,
        ?string $configPath = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, ExceptionType::CONFIGURATION_EXCEPTION, $previous);

        // Initialize configuration-specific properties with fallback values
        $this->configKey = $configKey;
        $this->source = $source;
        $this->expected = $expected ?? 'Not specified';
        $this->configPath = $configPath ?? 'Not specified';
    }

    /**
     * Handles configuration-related exceptions.
     * 
     * Logs detailed information about the exception, including the configuration key,
     * source, expected value, configuration path, and the function where the exception
     * originated. This information is useful for debugging configuration-related issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - Configuration key, source, expected value, and configuration path.
     * - File, line, and timestamp of the exception.
     * 
     * ### Example Log Output:
     * ```
     * [CONFIGURATION ERROR] Missing required configuration key
     * | Function: loadConfig
     * | Config Key: DATABASE_URL
     * | Source: .env
     * | Expected: A valid database connection string
     * | Path: /path/to/.env
     * | File: /path/to/file.php
     * | Line: 42
     * | Timestamp: 2025-04-13 14:30:00
     * ```
     * 
     * @return void
     */
    final protected function configurationException(): void {
        $fnName = parent::getFailedFunctionName();

        // Log the configuration error details
        error_log("[CONFIGURATION ERROR] " . $this->getMessage() .
            " | Function: $fnName" .
            " | Config Key: " . $this->configKey .
            " | Source: " . $this->source .
            " | Expected: " . $this->expected .
            " | Path: " . $this->configPath .
            " | File: " . $this->getFile() .
            " | Line: " . $this->getLine() .
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}

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
    ) {
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
     * ### Example Log Output:
     * ```
     * [FILE ERROR] Failed to read the file
     * | Function: readFile
     * | File Path: /path/to/file.txt
     * | Action: read
     * | Error Message: Permission denied
     * | Error Code: 13
     * | File: /path/to/file.php
     * | Line: 42
     * | Timestamp: 2025-04-13 14:30:00
     * ```
     * 
     * @return void
     */
    final protected function fileException(): void {
        $fnName = parent::getFailedFunctionName();

        // Log the file error details
        error_log("[FILE ERROR] " . $this->getMessage() .
            " | Function: $fnName" .
            " | File Path: " . $this->filePath .
            " | Action: " . $this->action .
            " | Error Message: " . $this->fileErrorMessage .
            " | Error Code: " . $this->fileErrorCode .
            " | File: " . $this->getFile() .
            " | Line: " . $this->getLine() .
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}

/*
    #######################################
    #                                     #
    #     INTERNAL RELATED SUBCLASSES     #
    #                                     #
    #######################################
*/

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

        // error log
        error_log("[PHP ERROR] " . $this->getMessage() . 
            " | Function: " . $functionName . 
            " | Error Details: " . $this->errorDetails . 
            " | PHP Error Code: " . $this->errorCode . 
            " | File: " . $this->getFile() . 
            " | Line: " . $this->getLine() . 
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}

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
        ?Throwable $previous = null
    ) {
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
     * ### Example Log Output:
     * ```
     * [LOGIC ERROR] Invalid state transition
     * | Function: transitionState
     * | Reason: State transition not allowed
     * | Expected State: Active
     * | Actual State: Inactive
     * | File: /path/to/file.php
     * | Line: 42
     * | Timestamp: 2025-04-13 14:30:00
     * ```
     * 
     * @return void
     */
    final protected function logicException(): void {
        $fnName = parent::getFailedFunctionName();

        // Log the logic error details
        error_log("[LOGIC ERROR] " . $this->getMessage() .
            " | Function: $fnName" .
            " | Reason: " . $this->reason .
            " | Expected State: " . $this->expectedState .
            " | Actual State: " . $this->actualState .
            " | File: " . $this->getFile() .
            " | Line: " . $this->getLine() .
            " | Timestamp: " . parent::getDateAndTime()
        );
    }
}