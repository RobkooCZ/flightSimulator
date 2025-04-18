<?php
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
 * Class RoleManager
 *
 * This class is responsible for managing user roles within the application.
 * It provides methods to handle role-based access control and permissions.
 */
class RoleManager {
    /**
     * Returns a list of roles that are available for the current user based on their role.
     *
     * @param array $enumValues List of all possible roles.
     * @param string $currentUserRole The role of the current user.
     * @return array Filtered list of roles available for the current user.
     * @throws LogicException If the current user's role is invalid.
     *
     * Example:
     * ```php
     * $roles = ['owner', 'coOwner', 'admin', 'user', 'deleted'];
     * $currentUserRole = 'admin';
     * $availableRoles = RoleManager::returnAvailibleRoles($roles, $currentUserRole);
     * // $availableRoles will be ['user', 'deleted']
     * ```
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