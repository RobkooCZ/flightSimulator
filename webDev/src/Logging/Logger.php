<?php
/**
 * Main logger class implementing the singleton pattern.
 * 
 * This class serves as the entry point for all logging operations,
 * managing both file and console logging through their respective
 * singleton instances.
 *
 * @file Logger.php
 * @since 0.2.2
 * @package Logger
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see LogLevel, Loggers, LoggerType, ConsoleLogger, FileLogger
 * @todo Add file-based logging implementation
 */

declare(strict_types=1);

namespace WebDev\Logging;

use WebDev\Exception\LogicException;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;

if (!defined('STDOUT')){
    define('STDOUT', fopen('php://stdout', 'w'));
}

/**
 * Main logger class implementing the singleton pattern.
 * 
 * This class serves as the entry point for all logging operations,
 * managing both file and console logging through their respective
 * singleton instances.
 *
 * @package Logger
 * @since 0.2.2
 * @see LogLevel, Loggers, LoggerType, ConsoleLogger, FileLogger
 * @todo Add file-based logging implementation
 */
class Logger {
    /**
     * The singleton instance of the Logger class.
     *
     * @var Logger|null
     */
    private static ?Logger $l = null;
    
    /**
     * The FileLogger instance for writing logs to files.
     *
     * @var FileLogger|null
     */
    public ?FileLogger $fl = null;
    
    /**
     * The ConsoleLogger instance for writing logs to console.
     *
     * @var ConsoleLogger|null
     */
    public ?ConsoleLogger $cl = null;

    /**
     * @var string The minimum log level for logging messages to the console.
     * 
     * - `NONE`   => All messages are logged.
     * - `DEBUG`  => Messages below DEBUG level are not logged.
     * 
     * Refer to the `LogLevel` enum for the full list of log levels and their hierarchy.
     * 
     * This constant's value will be used if a `.env` entry for `MIN_LOG_LEVEL` isn't provided.
     */
    private const MIN_LOG_LEVEL = "NONE";

    /**
     * Prevents unserializing of this singleton class.
     *
     * @throws LogicException When attempting to unserialize the singleton.
     * @return never
     */
    public function __wakeup(): never {
        throw new LogicException(message: "Cannot unserialize singleton.", reason: "Would violate the singleton pattern.");
    }

    /**
     * Prevents cloning of this singleton class.
     *
     * @throws LogicException When attempting to clone the singleton.
     * @return void
     */
    public function __clone(): void {
        throw new LogicException(message: "Cannot clone singleton", reason: "Would violate the singleton pattern.");
    }

    /**
     * Private constructor to prevent direct instantiation.
     * Initializes the FileLogger and ConsoleLogger instances.
     *
     * @return void
     */
    private function __construct(){
        $this->fl = FileLogger::getInstance();
        $this->cl = ConsoleLogger::getInstance();
    }

    /**
     * Gets the singleton instance of the Logger class.
     *
     * @return Logger The singleton instance.
     */
    public static function getInstance(): Logger {
        if (self::$l === null){
            self::$l = new Logger();
        }
        return self::$l;
    }

    /**
     * Gets the minimum log level from environment settings or uses the default.
     *
     * @return string The minimum log level name.
     */
    public static function getMinLogLevel(): string {
        return $_ENV['MIN_LOG_LEVEL'] ?? self::MIN_LOG_LEVEL; // either return the .env entry, or the default consatnt in this class.
    }

    /**
     * Gets the current date and time formatted for log entries.
     *
     * @return string The formatted date and time string.
     */
    public static function dateAndTime(): string {
        $now = new \DateTime();
        $formatted = $now->format('jS F Y, H:i:s.u');
        return substr($formatted, 0, -3); // cuts off last 3 digits
    }

    /**
     * Internal method to log a message to the specified targets.
     *
     * @param string $msg The message to log.
     * @param LogLevel $ill The log level of the message.
     * @param LoggerType $type The type of log (normal or exception).
     * @param Loggers $target The target(s) to log to (file, console, or both).
     * @param int $line The line number where the log was called.
     * @param string $file The file where the log was called.
     * @return void
     */
    private function logMessage(string $msg, LogLevel $ill, LoggerType $type, Loggers $target = Loggers::ALL, int $line = __LINE__, string $file = __FILE__): void {
        if (($target->value & Loggers::CMD->value)){
            try {
                if ($this->cl === null){
                    $this->cl = ConsoleLogger::getInstance();
                }
                // call either the method to log a normal message or log an exception
                ($type->value === "NORMAL") ? $this->cl->cliLog($msg, $ill) : $this->cl->cliLogException($msg, $ill, $line, $file);
            }
            catch (\Throwable $e){
                // Fallback to direct output if ConsoleLogger fails
                fwrite(STDOUT, "[LOGGER ERROR] Failed to log message: $msg" . PHP_EOL);
            }
        }
        
        if (($target->value & Loggers::FILE->value)){
            try {
                if ($this->fl === null){
                    $this->fl = FileLogger::getInstance();
                }
                
                // Logging to file will be added in the future
            }
            catch (\Throwable $e){
                // Fallback to console if file logging fails
                fwrite(STDOUT, "[FILE LOGGER ERROR] Failed to log message: $msg" . PHP_EOL);
            }
        }
    }

    /**
     * Static method to log a message through the singleton instance.
     * 
     * This method checks the minimum log level before proceeding, to avoid
     * logging messages that are below the configured threshold.
     *
     * @param string $msg The message to log.
     * @param LogLevel $ill The log level of the message.
     * @param LoggerType $type The type of log (normal or exception).
     * @param Loggers $target The target(s) to log to (file, console, or both).
     * @param int $line The line number where the log was called.
     * @param string $file The file where the log was called.
     * @return void
     */
    public static function log(string $msg, LogLevel $ill, LoggerType $type, Loggers $target = Loggers::ALL, int $line = __LINE__, string $file = __FILE__): void {
        if ($ill->value < LogLevel::fromName(self::getMinLogLevel())->value) return; // don't log anything below the desired level
        
        self::getInstance()->logMessage($msg, $ill, $type, $target, $line, $file);
    }
}