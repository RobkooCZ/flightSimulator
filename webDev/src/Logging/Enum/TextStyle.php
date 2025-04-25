<?php
/**
 * Text styling codes for terminal output.
 * 
 * This enum defines ANSI escape sequences for applying text styling
 * effects like bold, italic, and underline to terminal output.
 *
 * @file TextStyle.php
 * @since 0.2.2
 * @package Logger
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see LogLevel, LogColours
 * @todo Add more text styles if needed
 */

declare(strict_types=1);

namespace WebDev\Logging\Enum;

/**
 * Text styling codes for terminal output.
 * 
 * This enum defines ANSI escape sequences for applying text styling
 * effects like bold, italic, and underline to terminal output.
 *
 * @package Logger
 * @since 0.2.2
 * @see LogLevel, LogColours
 * @todo Add more text styles if needed
 */
enum TextStyle: string {
    case BOLD      = "\033[1m";
    case ITALIC    = "\033[3m";
    case UNDERLINE = "\033[4m";
    case BLINK     = "\033[5m";
    case REVERSE   = "\033[7m";
    case RESET     = "\033[0m";
}