<?php
/**
 * Bootstrap Class File
 *
 * Singleton class responsible for bootstrapping the application:
 * - Loads environment variables from .env
 * - Sets the application timezone
 * - More features coming soon
 *
 * @file Bootstrap.php
 * @since 0.4.2
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.3
 * @see ConfigurationException, FileException, LogicException
 * @todo Add more initialization logic as needed
 */

declare(strict_types=1);

namespace WebDev;

require_once __DIR__ . '/../vendor/autoload.php';

// Default encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Session security
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_secure', '1');

// Memory limits
ini_set('memory_limit', '256M');

// Timezone fallback before .env loads
date_default_timezone_set('Europe/Prague');

// Error handling based on environment
if (getenv('APP_ENV') === 'development'){
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} 
else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

// External dependencies
use Dotenv\Dotenv;

// Internal PHP
use Exception;

// Custom exceptions
use WebDev\Exception\ConfigurationException;
use WebDev\Exception\FileException;
use WebDev\Exception\LogicException;
use webdev\Exception\AppException;

// Logger
use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Logger;

// Database
use WebDev\Database\Database;

/**
 * Handles application bootstrapping and environment setup.
 *
 * Singleton class responsible for bootstrapping the application:
 * - Loads environment variables from .env
 * - Sets the application timezone
 * - More features coming soon
 *
 * @package FlightSimWeb
 * @since 0.4.2
 * @see ConfigurationException, FileException, LogicException
 * @todo Add more initialization logic as needed
 */
class Bootstrap {
    /**
     * @var ?string Current timezone used in the application
     */
    public static ?string $timeZone = null;

    /**
     * @var ?Bootstrap Singleton instance
     */
    public static ?Bootstrap $instance = null;

    /**
     * Prevent unserialize attacks.
     * 
     * This method prevents the unserialization of the singleton instance,
     * ensuring that the class cannot be instantiated through unserialization.
     * 
     * @throws LogicException Always throws a LogicException with a reason indicating
     *                        that unserializing the singleton would violate the singleton pattern.
     */
    public function __wakeup(): never {
        throw new LogicException(message: "Cannot unserialize singleton.", reason: "Would violate the singleton pattern.");
    }

    /**
     * Prevent cloning of the singleton instance.
     * 
     * This method ensures that the singleton instance cannot be cloned,
     * maintaining the integrity of the singleton pattern.
     * 
     * @throws LogicException Always throws a LogicException with a reason indicating
     *                        that cloning the singleton would violate the singleton pattern.
     */
    public function __clone(): void {
        throw new LogicException(message: "Cannot clone singleton", reason: "Would violate the singleton pattern.");
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct(){}

    /**
     * Set security headers for all responses
     */
    private static function setSecurityHeaders(): void {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=()');
    }

    /**
     * Initialize core services (DB, Logger, etc.)
     */
    private static function initCoreServices(): void {
        // Initialize database connection
        Database::init();

        // Initialize logger
        Logger::init();
    }

    /**
     * Loads environment variables using Dotenv.
     *
     * @return void
     * @throws FileException If the .env file cannot be loaded.
     * @throws ConfigurationException If required environment variables are missing.
     */
    private static function loadEnv(): void {
        // Load .env variables
        try {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }
        catch (Exception $e){ // if .env doesn't exist
            throw new FileException(
                "Failed to load .env file.",
                500, // HTTP status code for internal server error
                __DIR__ . '/../.env', // Path to the .env file
                "Loading environment variables", // Action being performed
                $e->getMessage(), // Original error message
                $e->getCode(), // Original error code
                $e // Previous exception for chaining
            );
        }

        // load necessary data from the .env file
        $appTimezone = $_ENV['APP_TIMEZONE'];
        $appEnv = $_ENV['APP_ENV'];
        // more in the future

        // get an array of missing values (value for now but future proof)
        $missing = array_filter([
            'APP_TIMEZONE' => $appTimezone,
            'APP_ENV' => $appEnv
        ], fn(string $value) => !$value);

        // if anything is missing, throw an expection that displays all the missing key variables
        if (!empty($missing)){
            throw new ConfigurationException(
                "Missing environment variables. Please check your .env file.",
                500, // HTTP status code for internal server error
                implode(', ', array_keys($missing)), // The missing configuration keys
                ".env", // The source of the configuration
                "All required environment variables must be set.", // Expected value or explanation
                __DIR__ . '/../.env', // Path to the .env file
                null, // No previous exception
            );
        }
    }

    /**
     * Sets the default timezone from the APP_TIMEZONE environment variable,
     * or falls back to 'Europe/Prague' if not set.
     * Stores the timezone in a static variable.
     *
     * @return void
     */
    private static function timezone(): void {
        // check whether the timezone is not set
        if (self::$timeZone === null){            
            // Set the timezone from the .env var, default to Europe/Prague if not found
            date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Prague');

            // set the timezone in the static variable
            self::$timeZone = date_default_timezone_get();
        }
    }

    private static function setGlobalExceptionHandler(): void {
        set_exception_handler(function (\Throwable $ae){
            if (AppException::globalHandle($ae)){ // appException or its subclasses
                header('Location: /'); // for now
                exit;
            }
            else { // anything but appException and its subclasses
                Logger::log(
                    $ae->getMessage(),
                    LogLevel::WARNING,
                    LoggerType::NORMAL,
                    Loggers::CMD
                );
            }
        });
    }

    private static function shutdownHandler(): void {
        register_shutdown_function(function(){
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])){
                Logger::log(
                    "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}",
                    LogLevel::FAILURE,
                    LoggerType::NORMAL,
                    Loggers::CMD
                );
            }
        });
    }

    private static function validateEnvironment(): void {
        if (version_compare(PHP_VERSION, '8.2.0', '<')){
            throw new ConfigurationException(
                "Unsupported PHP version",
                500,
                "PHP 8.0.0 or higher required",
                "System Configuration",
                "Current version: " . PHP_VERSION,
                null
            );
        }

        $requiredExtensions = ['pdo', 'mbstring'];
        foreach ($requiredExtensions as $ext){
            if (!extension_loaded($ext)){
                throw new ConfigurationException(
                    "Missing required PHP extension: $ext",
                    500,
                    "Extension not loaded",
                    "System Configuration",
                    "Please install $ext extension",
                    null
                );
            }
        }

        Logger::log(
            "Environment validated. PHP version: " . PHP_VERSION,
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );
    }

    /**
     * Internal bootstrapper that performs setup logic.
     * Called once via init().
     *
     * @return void
     */
    private static function boot(): void {
        // construct the class
        self::$instance = new self();

        // ensure correct env
        self::validateEnvironment();

        // load env vars from .env
        self::loadEnv();

        // set the default timezone
        self::timezone();

        // set security headers
        self::setSecurityHeaders();

        // set the global exception handler
        self::setGlobalExceptionHandler();

        // set the reset handler
        self::shutdownHandler();

        // init core services
        self::initCoreServices();

        // other things added soon
    }

    /**
     * Initializes the application.
     * Only runs once (singleton).
     *
     * @return void
     */
    public static function init(): void {
        if (self::$instance !== null) return; // if it's NOT null, this function was already called (do NOT initialize again)

        // if it is NULL, call private boot function that initializes key things
        self::boot();
    }
}