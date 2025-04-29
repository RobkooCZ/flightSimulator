<?php
/**
 * File logger class for writing logs to files.
 * 
 * This class implements the singleton pattern and provides a foundation
 * for file-based logging, though the actual implementation is pending.
 *
 * @file FileLogger.php
 * @since 0.6
 * @package Logger
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see Logger, ConsoleLogger
 * @todo Implement file-based logging methods
 */

declare(strict_types=1);

namespace WebDev\Logging;

use WebDev\Exception\LogicException;

/**
 * File logger class for writing logs to files.
 * 
 * This class implements the singleton pattern and provides a foundation
 * for file-based logging, though the actual implementation is pending.
 *
 * @package Logger
 * @since 0.5
 * @see Logger, ConsoleLogger
 * @todo Implement file-based logging methods
 */
class FileLogger {
    /**
     * The singleton instance of the FileLogger class.
     *
     * @var FileLogger|null
     */
    private static ?FileLogger $fl = null;

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
     *
     * @return void
     */
    private function __construct(){}

    /**
     * Gets the singleton instance of the FileLogger class.
     *
     * @return FileLogger The singleton instance.
     */
    public static function getInstance(): FileLogger {
        if (self::$fl === null){
            self::$fl = new FileLogger();
        }
        return self::$fl;
    }

    /**
     * Logs a message to a file (to be implemented).
     *
     * @return void
     */
    public function fileLog(){ /* implemented in the future */ }

    /**
     * Logs an exception message to a file (to be implemented).
     *
     * @return void
     */
    public function fileExceptionlog(){ /* implemented in the future */ }
}