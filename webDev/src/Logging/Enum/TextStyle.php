<?php

declare(strict_types=1);

namespace WebDev\Logging\Enum;

/**
 * Text styling codes for terminal output.
 * 
 * This enum defines ANSI escape sequences for applying text styling
 * effects like bold, italic, and underline to terminal output.
 */
enum TextStyle: string {
    case BOLD      = "\033[1m";
    case ITALIC    = "\033[3m";
    case UNDERLINE = "\033[4m";
    case BLINK     = "\033[5m";
    case REVERSE   = "\033[7m";
    case RESET     = "\033[0m";
}