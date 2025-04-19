<?php

declare(strict_types=1);

namespace WebDev\Logging\Enum;

/**
 * Types of log messages.
 * 
 * This enum distinguishes between regular log messages and
 * exception-specific log messages, which require different formatting.
 */
enum LoggerType: string {
    case NORMAL = "NORMAL";
    case EXCEPTION = "EXCEPTION";
}