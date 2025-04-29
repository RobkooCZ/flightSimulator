<?php
/**
 * CSRF Class File
 *
 * This file contains the `CSRF` class, which is responsible for handling Cross-Site Request Forgery (CSRF) protection mechanisms.
 * It provides methods to generate and validate CSRF tokens to ensure secure form submissions
 * and prevent unauthorized actions on behalf of authenticated users.
 *
 * @file CSRF.php
 * @since 0.2.3
 * @package Auth
 * @version 0.7.1
 * @see Logger, ValidationException
 * @todo Add support for token rotation
 */

declare(strict_types=1);

namespace WebDev\Auth;

// Exception classes
use WebDev\Exception\LogicException;
use WebDev\Exception\ValidationException;

// Enums
use WebDev\Exception\Enum\ValidationFailureType;

// Logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\Loggers;

/**
 * Class CSRF
 *
 * Handles Cross-Site Request Forgery (CSRF) protection mechanisms.
 * Provides methods to generate and validate CSRF tokens for secure form submissions.
 *
 * @package Auth
 * @since 0.2.3
 * @see Logger, ValidationException
 * @todo Add support for token rotation
 */
class CSRF {
    /**
     * @var CSRF|null $instance Singleton instance of the CSRF class.
     */
    private static ?CSRF $instance = null;

    /**
     * @var string $SESSION_CSRF_KEY Key for storing CSRF token in the session.
     */
    private const SESSION_CSRF_KEY = '_csrf_token';

    /**
     * @var string $SESSION_EXPIRY_KEY Key for storing token expiry in the session.
     */
    private const SESSION_EXPIRY_KEY = 'expires';

    /**
     * @var string $COOKIE_NAME Name of the CSRF cookie.
     */
    private const COOKIE_NAME = 'csrf_token';

    /**
     * @var int $COOKIE_EXPIRY_TIME Cookie expiry time in seconds (30 minutes).
     */
    private const COOKIE_EXPIRY_TIME = 1800;

    /**
     * @var string $COOKIE_PATH Default path for cookies.
     */
    private const COOKIE_PATH = '/';

    /**
     * @var int $TOKEN_LENGTH Length of the CSRF token in bytes (256-bit token).
     */
    private const TOKEN_LENGTH = 32;

    /**
     * Prevent unserialize attacks.
     *
     * This method prevents the unserialization of the singleton instance,
     * ensuring that the class cannot be instantiated through unserialization.
     *
     * @throws LogicException Always throws a LogicException with a reason indicating
     *                        that unserializing the singleton would violate the singleton pattern.
     */
    public function __wakeup(): never {
        throw new LogicException(message: "Cannot unserialize singleton.", reason: "Would violate the singleton pattern.");
    }

    /**
     * Prevent cloning of the singleton instance.
     *
     * This method ensures that the singleton instance cannot be cloned,
     * maintaining the integrity of the singleton pattern.
     *
     * @throws LogicException Always throws a LogicException with a reason indicating
     *                        that cloning the singleton would violate the singleton pattern.
     */
    public function __clone(): void {
        throw new LogicException(message: "Cannot clone singleton", reason: "Would violate the singleton pattern.");
    }

    /**
     * Private constructor to initialize the CSRF class.
     *
     * This method ensures that the session is started if it hasn't already been started.
     */
    private function __construct(){
        Logger::log(
            "Initializing the CSRF class...",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        if (session_status() === PHP_SESSION_NONE){
            Logger::log(
                "Starting a new session for the CSRF class.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            session_start();
        }

        Logger::log(
            "CSRF class initialized successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
    }

    /**
     * Retrieves the singleton instance of the CSRF class.
     *
     * This method ensures that only one instance of the CSRF class is created
     * during the application's lifecycle. If the instance does not already exist,
     * it initializes a new one.
     *
     * @return CSRF The singleton instance of the CSRF class.
     */
    public static function getInstance(): CSRF {
        if (self::$instance === null){
            Logger::log(
                "Creating a new CSRF singleton instance.",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            self::$instance = new CSRF();
        }
        else {
            Logger::log(
                "Reusing the existing CSRF singleton instance.",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        return self::$instance;
    }

    /**
     * Generates a new CSRF token and stores it in the session and a secure cookie.
     *
     * This method creates a cryptographically secure random token, stores it in the session,
     * and sets it as a secure cookie with a 1-hour expiration time.
     *
     * @return void
     */
    private function generateToken(): void {
        Logger::log(
            "Generating a new CSRF token.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::SESSION_CSRF_KEY] = [
            'token' => $token,
            self::SESSION_EXPIRY_KEY => time() + self::COOKIE_EXPIRY_TIME
        ];
        setcookie(self::COOKIE_NAME, $token, [
            self::SESSION_EXPIRY_KEY => time() + self::COOKIE_EXPIRY_TIME,
            'path' => self::COOKIE_PATH,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        Logger::log(
            "CSRF token generated and stored successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
    }

    /**
     * Validates the submitted CSRF token against the session and cookie.
     *
     * This method checks if the submitted token matches the token stored in the session
     * and the secure cookie. It also ensures that the token has not expired.
     *
     * @param string $token The CSRF token submitted by the client.
     *
     * @return bool True if the token is valid, false otherwise.
     * @throws ValidationException If the token is mismatched, invalid, expired, or the cookie doesn't match.
     */
    public function validateToken(string $token): bool {
        Logger::log(
            "Validating CSRF token.",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        if (!isset($_SESSION[self::SESSION_CSRF_KEY]['token'])){
            throw new ValidationException(
                message: "CSRF validation failed: token is missing.",
                code: 400,
                failureType: ValidationFailureType::CSRF_MISSING
            );
        }

        if ($_SESSION[self::SESSION_CSRF_KEY]['expires'] <= time()){
            $this->generateToken();
            throw new ValidationException(
                message: "CSRF validation failed: token has expired.",
                code: 400,
                failureType: ValidationFailureType::CSRF_EXPIRED
            );
        }

        if ($_SESSION[self::SESSION_CSRF_KEY]['token'] !== $token){
            throw new ValidationException(
                message: "CSRF validation failed: token mismatched.",
                code: 400,
                failureType: ValidationFailureType::CSRF_MISMATCHED
            );
        }

        if (!isset($_COOKIE[self::COOKIE_NAME]) || $_COOKIE[self::COOKIE_NAME] !== $token){
            throw new ValidationException(
                message: "CSRF validation failed: cookie data doesn't match.",
                code: 400,
                failureType: ValidationFailureType::CSRF_COOKIE_MISMATCHED
            );
        }

        Logger::log(
            "CSRF token validated successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $this->regenerateToken();
        return true;
    }

    /**
     * Retrieves the current CSRF token.
     *
     * This method generates a new token if one does not already exist in the session.
     *
     * @return string The current CSRF token.
     */
    public function getToken(): string {
        Logger::log(
            "Retrieving the current CSRF token.",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        if (!isset($_SESSION[self::SESSION_CSRF_KEY])){
            Logger::log(
                "No CSRF token found in the session. Generating a new token.",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            $this->generateToken();
        }

        Logger::log(
            "CSRF token retrieved successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return $_SESSION[self::SESSION_CSRF_KEY]['token'];
    }

    /**
     * Clears the CSRF token from the session and removes the corresponding cookie.
     *
     * This method unsets the CSRF token stored in the session and deletes the CSRF
     * cookie by setting its expiration time to a past timestamp. The cookie is
     * configured with secure attributes to enhance security.
     *
     * @return void
     */
    public function clearToken(): void {
        Logger::log(
            "Clearing the CSRF token from the session and removing the cookie.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        unset($_SESSION[self::SESSION_CSRF_KEY]);
        setcookie(self::COOKIE_NAME, '', [
            self::SESSION_EXPIRY_KEY => time() - self::COOKIE_EXPIRY_TIME,
            'path' => self::COOKIE_PATH,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        Logger::log(
            "CSRF token cleared successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
    }

    /**
     * Checks if the CSRF token has expired.
     *
     * This method verifies whether the CSRF token stored in the session is either
     * missing or has an expiration time that is less than or equal to the current
     * time.
     *
     * @return bool Returns true if the CSRF token is expired or missing, false otherwise.
     */
    public function isTokenExpired(): bool {
        Logger::log(
            "Checking if the CSRF token has expired.",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $expired = !isset($_SESSION[self::SESSION_CSRF_KEY]) || $_SESSION[self::SESSION_CSRF_KEY][self::SESSION_EXPIRY_KEY] <= time();

        Logger::log(
            $expired ? "CSRF token is expired or missing." : "CSRF token is valid.",
            $expired ? LogLevel::FAILURE : LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return $expired;
    }

    /**
     * Adds the CSRF token to the HTTP response headers.
     *
     * This method sets the CSRF token as a custom HTTP header (`X-CSRF-Token`).
     *
     * @return void
     */
    public function addTokenToHeader(): void {
        Logger::log(
            "Adding the CSRF token to the HTTP response headers.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $token = $this->getToken();
        header("X-CSRF-Token: " . $token);

        Logger::log(
            "CSRF token added to the HTTP headers successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );
    }

    /**
     * Regenerates the CSRF token.
     *
     * This method invalidates the current token by removing it from the session
     * and generates a new token.
     *
     * @return string The newly generated CSRF token.
     */
    public function regenerateToken(): string {
        Logger::log(
            "Regenerating the CSRF token.",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        unset($_SESSION[self::SESSION_CSRF_KEY]);
        $newToken = $this->getToken();

        Logger::log(
            "CSRF token regenerated successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return $newToken;
    }

    /**
     * Generates an HTML hidden input field containing the CSRF token.
     *
     * This method creates a hidden input field with the CSRF token, which can be
     * included in forms to provide CSRF protection.
     *
     * @return string The HTML for the hidden input field.
     */
    public function getCSRFField(): string {
        Logger::log(
            "Generating an HTML hidden input field containing the CSRF token.",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        $field = '<input type="hidden" name="csrf_token" value="' . $token . '">';

        Logger::log(
            "CSRF hidden input field generated successfully.",
            LogLevel::SUCCESS,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return $field;
    }
}