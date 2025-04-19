<?php

declare(strict_types=1);

namespace WebDev\Exception;

# php
use Throwable;

# enums
use WebDev\Exception\Enum\ExceptionType;
use WebDev\Exception\Enum\AuthenticationType;

# logger stuff
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers; 
/**
 * Handles authentication-related exceptions.
 * 
 * This class is used for exceptions that occur during authentication processes, such as
 * login, logout, or registration failures. It provides detailed context about the
 * authentication error, including the type of action and the failure reason.
 * 
 * ### Features:
 * - Captures the type of authentication action (e.g., login, logout, register).
 * - Logs the failure reason for debugging purposes.
 * - Supports exception chaining to preserve the original exception context.
 */
final class AuthenticationException extends AppException {
    private AuthenticationType $authType; // The type of authentication action (e.g., login, logout, register)
    private ?string $failureReason; // The reason for the authentication failure

    /**
     * Constructs a new AuthenticationException instance.
     * 
     * This constructor initializes the exception with a message, code, and optional
     * details about the authentication error, such as the type of action and the failure reason.
     * 
     * ### Example usage:
     * ```php
     * throw new AuthenticationException(
     *     message: "Invalid credentials provided",
     *     code: 401,
     *     authType: AuthenticationType::LOGIN,
     *     failureReason: "The username or password is incorrect.",
     *     previous: $previousException
     * );
     * ```
     * 
     * @param string $message The exception message.
     * @param int $code The exception code (default is 0).
     * @param AuthenticationType $authType The type of authentication action (e.g., login, logout, register).
     * @param ?string $failureReason The reason for the authentication failure (default is null).
     * @param ?Throwable $previous The previous exception used for exception chaining (default is null).
     */
    final public function __construct(
        string $message,
        int $code = 0,
        AuthenticationType $authType,
        ?string $failureReason = null,
        ?Throwable $previous = null
    ){
        parent::__construct($message, $code, ExceptionType::AUTHENTICATION_EXCEPTION, $previous);

        $this->authType = $authType;
        $this->failureReason = $failureReason ?? 'No reason provided.';
    }

    /**
     * Handles authentication-related exceptions.
     * 
     * Logs detailed information about the exception, including the type of authentication action,
     * the failure reason, and the function where the exception originated. This information is
     * useful for debuggin fg authentication-related issues.
     * 
     * ### Logged Details:
     * - Exception message and code.
     * - The function where the exception was thrown.
     * - Authentication action type and failure reason.
     * - File, line, and timestamp of the exception.
     * 
     * @return void
     */
    final protected function authenticationException(): void {
        Logger::getInstance()->log(
            "[AUTHENTICATION] " . $this->getMessage() .
            " | Action: " . $this->authType->value .
            " | Failure Reason: " . $this->failureReason,
            LogLevel::WARNING,
            LoggerType::EXCEPTION,
            Loggers::CMD,
            $this->getLine(),
            $this->getFile()
        );
    }
}