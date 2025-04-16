<?php
namespace WebDev;

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Exception;
use WebDev\Functions\ConfigurationException;
use WebDev\Functions\FileException;
use WebDev\Functions\LogicException;

/**
 * Class Bootstrap
 *
 * Singleton class responsible for bootstrapping the application:
 * - Sets the application timezone
 * - More features coming soon
 */
class Bootstrap {
    /**
     * @var string|null Current timezone used in the application
     */
    public static ?string $timeZone = null;

    /**
     * @var Bootstrap|null Singleton instance
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
     * Private constructor to prevent direct instantiation
     */
    private function __construct(){}

    /**
     * Loads environment variables using Dotenv
     *
     * @return void
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
        // more in the future

        // get an array of missing values (value for now but future proof)
        $missing = array_filter([
            'APP_TIMEZONE' => $appTimezone
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

    /**
     * Internal bootstrapper that performs setup logic.
     * Called once via init().
     *
     * @return void
     */
    private static function boot(): void {
        // construct the class
        self::$instance = new self();

        // load env vars from .env
        self::loadEnv();

        // set the default timezone
        self::timezone();

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
