<?php
/**
 * AppException Base Class File
 *
 * This file contains the abstract `AppException` class, which provides a foundation for handling
 * application-specific exceptions in a structured way. Subclasses should extend this class to define
 * specific exception types for the application.
 *
 * @file AppException.php
 * @since 0.4
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see ExceptionType, ConfigurationException, Logger
 * @todo Expand with more granular exception handling as needed
 */

declare(strict_types=1);

namespace WebDev\Exception;

# php stuff
use Exception;
use Throwable;

# exceptiontype enum
use WebDev\Exception\Enum\ExceptionType;

# logger stuff
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers; 

# configuration exception
use WebDev\Exception\ConfigurationException;

/**
 * Abstract base class for application-specific exceptions.
 * 
 * This class provides a foundation for handling exceptions in a structured way.
 * Subclasses should extend this class to define specific exception types.
 *
 * @package Exception
 * @since 0.4
 * @see ExceptionType, ConfigurationException, Logger
 * @todo Expand with more granular exception handling as needed
 */
abstract class AppException extends Exception {
    /**
     * The type of exception (e.g., USER_EXCEPTION).
     * 
     * @var ExceptionType 
     */
    protected ExceptionType $exceptionType; // The type of exception (e.g., USER_EXCEPTION)

    /**
     * Constructor for the AppException class.
     * 
     * Initializes the exception with a message, code, type, and an optional previous exception.
     * 
     * ### Example usage:
     * ```php
     * use WebDev\Exception\UserException;
     * use WebDev\Exception\Enum\ExceptionType;
     * 
     * throw new UserException(
     *     message: "Invalid username or password",
     *     code: 401,
     *     previous: $previousException
     * );
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
     * @throws ConfigurationException If the AppException class is not loaded.
     * @return void
     */
    final public static function init(): void {
        if (!class_exists(self::class)){ 
            throw new ConfigurationException(
                "AppException class failed to load!",
                500,
                "AppException",
                "PSR-4 Autoloader"
            );
        }

        Logger::log(
            "AppException initialized successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
    }

    /**
     * Handles the exception based on its type.
     * 
     * This method uses a match expression to determine the appropriate handling
     * method for the exception type. Subclasses can override these methods to
     * provide custom handling logic.
     *
     * @return void
     */
    final public function handle(): void {
        match ($this->exceptionType){
            // user exceptions
            ExceptionType::USER_EXCEPTION => $this->userException(),
            ExceptionType::VALIDATION_EXCEPTION => $this->validationException(),
            ExceptionType::AUTHENTICATION_EXCEPTION => $this->authenticationException(),
            ExceptionType::AUTHORIZATION_EXCEPTION => $this->authorizationException(),

            // server/backend exceptions
            ExceptionType::SERVER_EXCEPTION => $this->serverException(),
            ExceptionType::DATABASE_EXCEPTION => $this->databaseException(),
            ExceptionType::API_EXCEPTION => $this->apiException(),
            ExceptionType::CONFIGURATION_EXCEPTION => $this->configurationException(),
            ExceptionType::FILE_EXCEPTION => $this->fileException(),

            // internal exceptions
            ExceptionType::PHP_EXCEPTION => $this->phpException(),
            ExceptionType::NULL_EXCEPTION => $this->nullException(),
            ExceptionType::LOGIC_EXCEPTION => $this->logicException(),

            // default case if it doesn't match
            default => (function(){
                Logger::log(
                    "Invalid exceptionType provided.",
                    LogLevel::WARNING,
                    LoggerType::NORMAL,
                    Loggers::CMD,
                    __LINE__,
                    __FILE__
                );
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
     * Logs a validation exception.
     * 
     * Default implementation for logging validation exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function validationException(): void {
        Logger::log(
            "[VALIDATION] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::WARNING,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs an authentication exception.
     * 
     * Default implementation for logging authentication exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function authenticationException(): void {
        Logger::log(
            "[AUTHENTICATION] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::WARNING,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs an authorization exception.
     * 
     * Default implementation for logging authorization exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function authorizationException(): void {
        Logger::log(
            "[AUTHORIZATION] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::WARNING,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs a server exception.
     * 
     * Default implementation for logging server exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function serverException(): void {
        Logger::log(
            "[SERVER] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs a database exception.
     * 
     * Default implementation for logging database exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function databaseException(): void {
        Logger::log(
            "[DB] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs an API exception.
     * 
     * Default implementation for logging API exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function apiException(): void {
        Logger::log(
            "[API] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs a configuration exception.
     * 
     * Default implementation for logging configuration exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function configurationException(): void {
        Logger::log(
            "[CONFIGURATION] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs a file exception.
     * 
     * Default implementation for logging file exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function fileException(): void {
        Logger::log(
            "[FILE] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs a PHP exception.
     * 
     * Default implementation for logging PHP exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function phpException(): void {
        Logger::log(
            "[PHP] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs a null exception.
     * 
     * Default implementation for logging null exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function nullException(): void {
        Logger::log(
            "[NULL] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }

    /**
     * Logs a logic exception.
     * 
     * Default implementation for logging logic exceptions. Subclasses should override this method for specific handling.
     * 
     * @return void
     */
    protected function logicException(): void {
        Logger::log(
            "[LOGIC] " . $this->getMessage() .
            " | Exception code: " . $this->getCode(),
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}