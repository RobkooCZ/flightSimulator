<?php
/**
 * Log level enumeration for categorizing log messages by severity.
 * 
 * This enum defines a hierarchy of log levels from least severe (TRACE)
 * to most severe (EMERGENCY). The integer values represent the relative
 * severity, with higher values indicating more severe issues.
 *
 * @file LogLevel.php
 * @since 0.6
 * @package Logger
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see Loggers, LoggerType, LogColours
 * @todo Add more log levels or mappings if needed
 */

declare(strict_types=1);

namespace WebDev\Logging\Enum;

/**
 * Log level enumeration for categorizing log messages by severity.
 * 
 * This enum defines a hierarchy of log levels from least severe (TRACE)
 * to most severe (EMERGENCY). The integer values represent the relative
 * severity, with higher values indicating more severe issues.
 *
 * @package Logger
 * @since 0.5
 * @see Loggers, LoggerType, LogColours
 * @todo Add more log levels or mappings if needed
 */
enum LogLevel: int {
    case NONE      = -1;
    case TRACE     = 0;
    case DEBUG     = 1;
    case INFO      = 2;
    case NOTICE    = 3;
    case SUCCESS   = 4;
    case WARNING   = 5;
    case FAILURE   = 6;
    case ERROR     = 7;
    case EXCEPTION = 8;
    case CRITICAL  = 9;
    case ALERT     = 10;
    case EMERGENCY = 11;

    /**
     * Converts a string name to the corresponding LogLevel enum case.
     *
     * @param string $name The name of the log level (e.g., "DEBUG", "INFO").
     * @return self|self::NONE The corresponding LogLevel enum case if a valid name was provided, otherwise returns `self::NONE`.
     */
    public static function fromName(string $name): self {
        foreach (self::cases() as $case){
            if ($case->name === $name){
                return $case;
            }
        }

        // default return NONE
        return self::NONE;
    }
}