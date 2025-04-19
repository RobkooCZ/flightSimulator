<?php

declare(strict_types=1);

namespace WebDev\Exception\Enum;

/**
 * Enum representing different types of validation failures.
 * 
 * This enum is used to categorize validation errors into specific types, such as username,
 * password, and CSRF token validation failures. It provides a structured way to handle
 * validation errors and ensures that they are consistently categorized.
 */
enum ValidationFailureType: string {
    // Username-related validation failures
    case INVALID_USERNAME = 'InvalidUsername'; // Username contains invalid characters
    case USERNAME_TOO_SHORT = 'UsernameTooShort'; // Username is shorter than the minimum length
    case USERNAME_NOT_UNIQUE = 'UsernameNotUnique'; // Username is already taken

    // Password-related validation failures
    case PASSWORD_TOO_SHORT = 'PasswordTooShort'; // Password is shorter than the minimum length
    case PASSWORD_MISSING_UPPERCASE = 'PasswordMissingUppercaseLetter'; // Password lacks an uppercase letter
    case PASSWORD_MISSING_LOWERCASE = 'PasswordMissingLowercaseLetter'; // Password lacks a lowercase letter
    case PASSWORD_MISSING_NUMBER = 'PasswordMissingNumber'; // Password lacks a numeric digit
    case PASSWORD_MISSING_SPECIAL_CHAR = 'PasswordMissingSpecialCharacter'; // Password lacks a special character
    case PASSWORDS_MISMATCHED = 'PasswordsMismatched'; // Passwords do not match

    // CSRF token-related validation failures
    case CSRF_EXPIRED = 'CSRFExpired'; // CSRF token has expired
    case CSRF_INVALID = 'CSRFInvalid'; // CSRF token is invalid
    case CSRF_MISMATCHED = 'CSRFMismatched'; // CSRF token does not match
    case CSRF_MISSING = 'CSRFMissing'; // CSRF token is missing
    case CSRF_COOKIE_MISMATCHED = 'CSRFCookieMismatched'; // cookie data about CSRF is mismatched
}
