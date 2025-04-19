<?php

declare(strict_types=1);

namespace WebDev\Logging;

use WebDev\Logging\Enum\LogColours;
use WebDev\Logging\Enum\LogLevel;

/**
 * Utility class for retrieving color codes based on log levels.
 */
class LoggerColour {
    /**
     * Retrieves a corresponding LogColours enum case based on the provided log level name.
     * 
     * This method loops through all the cases of the `LogColours` enum and compares each case's
     * name with the provided `$name`. If a matching case is found, it returns that case.
     * If no matching case is found, it returns `null`.
     * 
     * @param string $name The name of the log level to find the corresponding color for.
     * 
     * @return LogColours|null The corresponding `LogColours` enum case or `null` if no match is found.
     */
    private static function fromName(string $name): ?LogColours {
        foreach (LogColours::cases() as $case){
            if ($case->name === $name){
                return $case;
            }
        }

        return null;
    }

    /**
     * Gets the color code associated with the provided log level.
     * 
     * This method calls `fromName()` to find the `LogColours` enum case that matches the
     * provided log level's name. If a match is found, it returns the color value of the matching case.
     * If no match is found (or `fromName()` returns `null`), it defaults to the `RESET` color to avoid
     * a `null` value in the output.
     * 
     * @param LogLevel $ill The log level for which to get the color.
     * 
     * @return string The color code string associated with the log level.
     *               Returns the reset color code if no corresponding color is found.
     */
    public static function getColour(LogLevel $ill): string {
        return self::fromName($ill->name)?->value ?? LogColours::RESET->value;
    }
}