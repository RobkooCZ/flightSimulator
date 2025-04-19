<?php

declare(strict_types=1);

namespace WebDev\Exception\Enum;

/**
 * Enum representing different types of exceptions.
 * 
 * This enum is used to categorize exceptions into meaningful types, such as user-related,
 * server-related, database-related, API-related, and more. It provides a foundation for
 * type-safe exception handling and ensures that exceptions are consistently categorized.
 */
enum ExceptionType: string {
    // User-related issues
    case USER_EXCEPTION = 'UserException'; // General user-related exceptions
    case VALIDATION_EXCEPTION = 'ValidationException'; // Input validation errors
    case AUTHENTICATION_EXCEPTION = 'AuthenticationException'; // Authentication failures
    case AUTHORIZATION_EXCEPTION = 'AuthorizationException'; // Authorization failures
    
    // Backend or server-side issues
    case SERVER_EXCEPTION = 'ServerException'; // General server-side errors
    case DATABASE_EXCEPTION = 'DatabaseException'; // Database-related errors
    case API_EXCEPTION = 'APIException'; // API interaction errors
    case CONFIGURATION_EXCEPTION = 'ConfigurationException'; // Configuration-related errors
    case FILE_EXCEPTION = 'FileException'; // File operation errors
    
    // Internal technical errors
    case PHP_EXCEPTION = 'PHPException'; // PHP-related errors
    case NULL_EXCEPTION = 'NullException'; // Whenever key variable, object or anything else is null
    case LOGIC_EXCEPTION = 'LogicException'; // Application logic errors
}
