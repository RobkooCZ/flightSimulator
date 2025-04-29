<?php
/**
 * Types of log messages.
 * 
 * This enum distinguishes between regular log messages and
 * exception-specific log messages, which require different formatting.
 *
 * @file LoggerType.php
 * @since 0.6
 * @package Logger
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see Loggers, LogLevel
 * @todo Add more logger types if needed
 */

declare(strict_types=1);

namespace WebDev\Logging\Enum;

/**
 * Types of log messages.
 * 
 * This enum distinguishes between regular log messages and
 * exception-specific log messages, which require different formatting.
 *
 * @package Logger
 * @since 0.5
 * @see Loggers, LogLevel
 * @todo Add more logger types if needed
 */
enum LoggerType: string {
    case NORMAL = "NORMAL";
    case EXCEPTION = "EXCEPTION";
}