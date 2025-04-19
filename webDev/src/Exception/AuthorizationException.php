<?php

declare(strict_types=1);

namespace WebDev\Exception;

# php
use Throwable;

# exception type enum
use WebDev\Exception\Enum\ExceptionType;

# logger stuff
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers; 

/**
 * Handles authorization-related exceptions.
 * 
 * This class is used for exceptions that occur during authorization processes, such as
 * when a user attempts to access a resource or perform an action they do not have
 * permission for. It provides detailed context about the authorization failure, including
 * the action attempted, the resource, the user's role, and the required role.
 * 
 * ### Features:
 * - Captures the action attempted and the resource being accessed.
 * - Logs the user's role, required role, and IP address.
 * - Supports exception chaining to preserve the original exception context.
 */
final class AuthorizationException extends AppException {
    private string $actionAttempted; // The action the user attempted to perform
    private string $resource; // The resource the user tried to access
    private string $userRole; // The role of the user attempting the action
    private ?string $requiredRole; // The role required to perform the action
    private ?string $ipv4; // The user's IP address
    private ?int $userId; // The ID of the user attempting the action

    /**
     * Constructs a new AuthorizationException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the authorization failure, such as the user's role, resource, and action attempted.
     * 
     * ### Example usage:
     * ```php
     * throw new AuthorizationException(
     *     message: "Access denied to admin page",
     *     code: 403,
     *     userRole: "user",
     *     resource: "/admin",
     *     actionAttempted: "view",
     *     requiredRole: "admin",
     *     ipv4: $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
     *     userId: $_SESSION['id'] ?? null,
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param string $userRole The role of the user attempting the action.
     * @param string $resource The resource the user tried to access.
     * @param string $actionAttempted The action the user attempted to perform.
     * @param ?string $requiredRole The role required to perform the action (default is null).
     * @param ?string $ipv4 The user's IP address (default is null).
     * @param ?int $userId The ID of the user attempting the action (default is null).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        string $userRole,
        string $resource,
        string $actionAttempted,
        ?string $requiredRole = null,
        ?string $ipv4 = null,
        ?int $userId = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::AUTHORIZATION_EXCEPTION, $previous);

        // either set the passed ip adress and if its ::1 (the same device) set it to 127.0.0.1 or put it as unknown
        $this->ipv4 = ($ipv4 === '::1') ? '127.0.0.1' : ($ipv4 ?? 'Unknown');
        $this->userId = $userId ?? 0; // 0 - guest
        $this->userRole = $userRole;
        $this->requiredRole = $requiredRole ?? 'Not provided';
        $this->resource = $resource;
        $this->actionAttempted = $actionAttempted;
    }

    /**
     * Handles authorization-related exceptions.
     * 
     * Logs detailed information about the exception, including the user's IP address,
     * the action attempted, the resource, the user's role, and the required role. This
     * information is useful for debugging and auditing authorization failures.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The user's IP address.
     * - The action attempted and the resource.
     * - The user's role and the required role.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     */
    final protected function authorizationException(): void {
        // Log authorization exception details
        Logger::getInstance()->log(
            "[AUTHORIZATION] " . $this->getMessage() .
            " | IPv4: " . $this->ipv4 .
            " | Action: " . $this->actionAttempted .
            " | Resource: " . $this->resource .
            " | User ID: " . $this->userId .
            " | User Role: " . $this->userRole .
            " | Required Role: " . $this->requiredRole,
            LogLevel::WARNING,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}