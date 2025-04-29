<?php
/**
 * Terminal color codes for different log levels.
 * 
 * This enum defines ANSI escape sequences for colorizing log output
 * in the terminal. Each case corresponds to a different log level or
 * text formatting need.
 *
 * @file LogColours.php
 * @since 0.6
 * @package Logger
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see LogLevel
 * @todo Add more color codes if needed
 */

declare(strict_types=1);

namespace WebDev\Logging\Enum;

/**
 * Terminal color codes for different log levels.
 * 
 * This enum defines ANSI escape sequences for colorizing log output
 * in the terminal. Each case corresponds to a different log level or
 * text formatting need.
 *
 * @package Logger
 * @since 0.5
 * @see LogLevel
 * @todo Add more color codes if needed
 */
enum LogColours: string {
    case EMERGENCY = "\033[38;2;255;255;255;48;2;255;0;0m";
    case ALERT     = "\033[38;2;255;0;255m";
    case CRITICAL  = "\033[38;2;255;0;0m";
    case ERROR     = "\033[38;2;255;85;85m";
    case WARNING   = "\033[38;2;255;255;0m";
    case NOTICE    = "\033[38;2;255;215;0m";
    case INFO      = "\033[38;2;30;144;255m";
    case DEBUG     = "\033[38;2;0;255;255m";
    case TRACE     = "\033[38;2;128;128;128m";

    case RESET     = "\033[0m";
    
    case EXCEPTION = "\033[38;2;255;20;147m";

    case SUCCESS   = "\033[38;2;0;255;0m";
    case FAILURE   = "\033[38;2;255;69;0m";
}