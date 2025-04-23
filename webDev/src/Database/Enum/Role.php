<?php
/**
 * Role Enum File
 *
 * This file contains the `Role` enum, which represents the different roles available in the system.
 * The values of the enums must match exactly as they are stored in the database.
 *
 * @file Role.php
 * @since 0.3.0
 * @package Database\Enum
 * @author Robkoo
 * @license TODO
 * @version 0.3.4
 * @see https://www.php.net/manual/en/language.enumerations.php
 * @todo Add more roles if needed
 */

declare(strict_types=1);

namespace WebDev\Database\Enum;

/**
 * Enum Role
 *
 * This enum represents the different roles available in the system.
 * The values of the enums must match exactly as they are stored in the database.
 *
 * @package Database\Enum
 * @since 0.3.0
 * @see https://www.php.net/manual/en/language.enumerations.php
 * @todo Add more roles if needed
 */
enum Role: string {
    case OWNER = 'owner';
    case COOWNER = 'coOwner';
    case ADMIN = 'admin';
    case USER = 'user';
    case DELETED = 'deleted';

    /**
     * Validates if the given role exists within the defined enum cases.
     *
     * This method checks if the provided role string matches any of the 
     * values defined in the enum cases of the Role class.
     *
     * @param string $role The role to validate.
     * 
     * @return bool Returns true if the role is valid, false otherwise.
     */
    public static function validateRole(string $role): bool {
        return in_array($role, array_column(self::cases(), 'value'), true);
    }
}