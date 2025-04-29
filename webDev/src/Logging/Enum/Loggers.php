<?php
/**
 * Logging target destinations.
 * 
 * This enum defines where log messages can be sent, using bitwise flags
 * to allow combination of multiple targets.
 *
 * @file Loggers.php
 * @since 0.6
 * @package Logger
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see Logger, LoggerType, LogLevel
 * @todo Add more logging targets if needed
 */

declare(strict_types=1);

namespace WebDev\Logging\Enum;

/**
 * Logging target destinations.
 * 
 * This enum defines where log messages can be sent, using bitwise flags
 * to allow combination of multiple targets.
 *
 * @package Logger
 * @since 0.5
 * @see Logger, LoggerType, LogLevel
 * @todo Add more logging targets if needed
 */
enum Loggers: int {
    case FILE = 1;
    case CMD  = 2;
    case ALL = self::FILE->value | self::CMD->value;
}