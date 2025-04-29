<?php
/**
 * RoleManager Class File
 *
 * This file contains the `RoleManager` class, responsible for managing user roles
 * within the application. It provides methods to handle role-based access control
 * and permissions, including filtering available roles based on the current user's role.
 *
 * @file RoleManager.php
 * @since 0.2
 * @package Utilities
 * @author Robkoo
 * @license TBD
 * @version 0.7.1
 * @see LogicException, Logger, LogLevel, LoggerType, Loggers
 * @todo Add more role management utilities if needed
 */

declare(strict_types=1);

namespace WebDev\Utilities;

// Exception classes
use WebDev\Exception\LogicException;

// Logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers;

/**
 * Manages user roles and role-based access control.
 *
 * This class provides methods to filter available roles for a user and
 * supports role hierarchy logic for permissions.
 *
 * @package Utilities
 * @since 0.2
 * @see LogicException, Logger, LogLevel, LoggerType, Loggers
 * @todo Add more role management utilities if needed
 */
class RoleManager {
    /**
     * Returns a list of roles that are available for the current user based on their role.
     *
     * This method filters the provided list of roles (`$enumValues`) according to the
     * hierarchy defined in the method. Only roles with a higher hierarchy value than
     * the current user's role are returned.
     *
     * ### Example usage:
     * ```php
     * $roles = ['owner', 'coOwner', 'admin', 'user', 'deleted'];
     * $currentUserRole = 'admin';
     * $availableRoles = RoleManager::returnAvailibleRoles($roles, $currentUserRole);
     * // $availableRoles will be ['user', 'deleted']
     * ```
     *
     * @param array $enumValues List of all possible roles.
     * @param string $currentUserRole The role of the current user.
     * @return array Filtered list of roles available for the current user.
     * @throws LogicException If the current user's role is invalid.
     */
    public static function returnAvailibleRoles(array $enumValues, string $currentUserRole): array {
        // Define the role hierarchy
        $roleHierarchy = [
            'owner' => 1,
            'coOwner' => 2,
            'admin' => 3,
            'user' => 4,
            'deleted' => 5
        ];

        // Check if the current user's role exists in the hierarchy
        if (!isset($roleHierarchy[$currentUserRole])){
            throw new LogicException(
                message: "Invalid current user role.",
                reason: "Role $currentUserRole doesn't exist in the hierarchy,",
                expectedState: implode(', ', $roleHierarchy),
                actualState: $currentUserRole
            );
        }

        Logger::log(
            "Current user role '$currentUserRole' is valid. Filtering available roles.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // Filter roles based on the current user's role
        $filteredRoles = array_filter($enumValues, function ($role) use ($roleHierarchy, $currentUserRole): bool {
            return isset($roleHierarchy[$role]) &&
                   $roleHierarchy[$role] > $roleHierarchy[$currentUserRole];
        });

        Logger::log(
            "Available roles for user with role '$currentUserRole': " . implode(', ', $filteredRoles),
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return $filteredRoles;
    }
}