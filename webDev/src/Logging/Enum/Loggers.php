<?php

declare(strict_types=1);

namespace WebDev\Logging\Enum;

/**
 * Logging target destinations.
 * 
 * This enum defines where log messages can be sent, using bitwise flags
 * to allow combination of multiple targets.
 */
enum Loggers: int {
    case FILE = 1;
    case CMD  = 2;
    case ALL = self::FILE->value | self::CMD->value;
}