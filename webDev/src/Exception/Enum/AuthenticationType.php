<?php
/**
 * AuthenticationType Enum File
 *
 * This file contains the `AuthenticationType` enum, which represents different types of authentication actions.
 * It is used to categorize authentication-related actions, such as registering a new user, logging in, or logging out.
 *
 * @file AuthenticationType.php
 * @since 0.3.4
 * @package Exception\Enum
 * @author Robkoo
 * @license TBD
 * @version 0.3.4
 * @see https://www.php.net/manual/en/language.enumerations.php
 * @todo Add more authentication types if needed
 */

declare(strict_types=1);

namespace WebDev\Exception\Enum;

/**
 * Enum AuthenticationType
 *
 * Enum representing different types of authentication actions.
 * This enum is used to categorize authentication-related actions, such as registering
 * a new user, logging in, or logging out. It provides a structured way to handle
 * authentication actions and ensures that they are consistently categorized.
 *
 * @package Exception\Enum
 * @since 0.3.4
 * @see https://www.php.net/manual/en/language.enumerations.php
 * @todo Add more authentication types if needed
 */
enum AuthenticationType: string {
    case REGISTER = 'Register'; // User registration
    case LOGIN = 'Login'; // User login
    case LOGOUT = 'Logout'; // User logout
}
