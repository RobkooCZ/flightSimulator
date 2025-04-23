<?php
/**
 * ConfigurationException Class File
 *
 * This file contains the `ConfigurationException` class, which handles exceptions that occur during application configuration,
 * such as missing `.env` variables, invalid settings, or misconfigured files.
 * It provides detailed context about the configuration error, including the configuration key, source, expected value, and configuration path.
 *
 * @file ConfigurationException.php
 * @since 0.2.1
 * @package Exception
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see AppException, ExceptionType
 * @todo Add more configuration error context if needed
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
 *
 * @package Exception
 * @since 0.2.1
 * @see AppException, ExceptionType
 * @todo Add more configuration error context if needed
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
    ){
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
     * @return void
     * @since 0.2.1
     */
    final protected function configurationException(): void {
        $fnName = parent::getFailedFunctionName();

        // Log the configuration error details
        Logger::log(
            "[CONFIGURATION] " . $this->getMessage() .
            " | Function: $fnName" .
            " | Config Key: " . $this->configKey .
            " | Source: " . $this->source .
            " | Expected: " . $this->expected .
            " | Path: " . $this->configPath,
            LogLevel::ERROR,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}