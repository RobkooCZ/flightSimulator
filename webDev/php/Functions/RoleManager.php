<?php

namespace WebDev\Functions;

class RoleManager {
    /**
     * Returns a list of roles that are available for the current user based on their role.
     *
     * @param array $enumValues List of all possible roles.
     * @param string $currentUserRole The role of the current user.
     * @return array Filtered list of roles available for the current user.
     * @throws \Exception If the current user's role is invalid.
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
        if (!isset($roleHierarchy[$currentUserRole])) {
            throw new \Exception("Invalid current user role: $currentUserRole");
        }

        // Filter roles based on the current user's role
        return array_filter($enumValues, function ($role) use ($roleHierarchy, $currentUserRole): bool {
            return isset($roleHierarchy[$role]) &&
                   $roleHierarchy[$role] > $roleHierarchy[$currentUserRole];
        });
    }
}