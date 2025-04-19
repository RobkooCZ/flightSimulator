<?php

declare(strict_types=1);

namespace WebDev\Exception\Enum;

/**
 * Enum representing different types of authentication actions.
 * 
 * This enum is used to categorize authentication-related actions, such as registering
 * a new user, logging in, or logging out. It provides a structured way to handle
 * authentication actions and ensures that they are consistently categorized.
 */
enum AuthenticationType: string {
    case REGISTER = 'Register'; // User registration
    case LOGIN = 'Login'; // User login
    case LOGOUT = 'Logout'; // User logout
}
