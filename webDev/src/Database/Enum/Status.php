<?php 

declare(strict_types=1);

namespace WebDev\Database\Enum;

/**
 * Enum Status
 *
 * This enum contains the possible values of the enum in the database alongside a method to check whether a string is a valid status enum or not.
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