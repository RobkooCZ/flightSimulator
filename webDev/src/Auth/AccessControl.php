<?php
/**
 * Access Control Helper
 *
 * @file AccessControl.php
 * @since 0.7.10
 * @package FlightSimWeb\Auth
 * @author Robkoo
 */

namespace WebDev\Auth;

/**
 * Class AccessControl
 *
 * Handles access control.
 *
 * @package Auth
 * @since 0.7.10
 * @see Auth
 */
class AccessControl {
    
    /**
     * Check if user has permission to access a resource
     * 
     * @param string $requiredRole The required role to pass the check.
     * @return bool True if the user passes, false if they don't.
     */
    public static function checkAccess(string $requiredRole = 'user'): bool {
        if (!isset($_SESSION[User::SESSION_ID_KEY])) {
            return false;
        }
        
        $userRole = $_SESSION['role'] ?? 'user';
        
        $roleHierarchy = [
            'user' => 1,
            'admin' => 2,
            'co-owner' => 3,
            'owner' => 4
        ];
        
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 1;
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Redirect to 403 if access denied.
     * 
     * @return void
     */
    public static function requireAccess(string $requiredRole = 'user'): void {
        if (!self::checkAccess($requiredRole)) {
            header('Location: /403');
            exit;
        }
    }
    
    /**
     * Redirect to login if not authenticated
     * 
     * @return void
     */
    public static function requireAuth(): void {
        if (!isset($_SESSION[User::SESSION_ID_KEY])) {
            header('Location: /login');
            exit;
        }
    }
}