<?php 
/**
 * Status Enum File
 *
 * This file contains the `Status` enum, which represents the possible status values for users or entities in the system.
 * The values of the enums must match exactly as they are stored in the database.
 *
 * @file Status.php
 * @since 0.3.0
 * @package Database\Enum
 * @author TODO
 * @license TODO
 * @version 0.3.4
 * @see https://www.php.net/manual/en/language.enumerations.php
 * @todo Add more statuses if needed
 */

declare(strict_types=1);

namespace WebDev\Database\Enum;

/**
 * Enum Status
 *
 * This enum contains the possible values of the enum in the database alongside a method to check whether a string is a valid status enum or not.
 *
 * @package Database\Enum
 * @since 0.3.0
 * @see https://www.php.net/manual/en/language.enumerations.php
 * @todo Add more statuses if needed
 */
enum Status: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';

    /**
     * Validates if a given string matches any of the defined status values.
     *
     * @param string $status The status string to validate.
     * @return bool True if the status is valid, false otherwise.
     */
    public static function validateStatus(string $status): bool {
        return in_array($status, array_column(self::cases(), 'value'), true);
    }
}