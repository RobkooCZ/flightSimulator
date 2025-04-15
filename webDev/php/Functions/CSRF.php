<?php
declare(strict_types=1);

namespace WebDev\Functions;

use WebDev\Functions\LogicException;
use WebDev\Functions\ValidationException;
use WebDev\Functions\ValidationFailureType;

class CSRF {
    private static ?CSRF $instance = null; // Singleton pattern
    
    // define constants
    private const SESSION_CSRF_KEY = '_csrf_token'; // Key for storing CSRF token in the session
    private const SESSION_EXPIRY_KEY = 'expires'; // Key for storing token expiry in the session

    // cookies
    private const COOKIE_NAME = 'csrf_token'; // Name of the CSRF cookie
    private const COOKIE_EXPIRY_TIME = 1800; // 30 minutes (30 * 60 seconds)
    private const COOKIE_PATH = '/'; // Default path for cookies

    // token
    private const TOKEN_LENGTH = 32; // 256-bit token (32 bytes)

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
        if (session_status() === PHP_SESSION_NONE){
            session_start();
        }
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
            self::$instance = new CSRF();
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
        // Check if the token exists in the session
        if (!isset($_SESSION[self::SESSION_CSRF_KEY]['token'])){
            throw new ValidationException(
                message: "CSRF validation failed: token is missing.",
                code: 400,
                failureType: ValidationFailureType::CSRF_MISSING
            );
        }

        // Check if the token has expired
        if ($_SESSION[self::SESSION_CSRF_KEY]['expires'] <= time()){
            $this->generateToken(); // Regenerate a new token
            throw new ValidationException(
                message: "CSRF validation failed: token has expired.",
                code: 400,
                failureType: ValidationFailureType::CSRF_EXPIRED
            );
        }

        // Check if the token matches the session token
        if ($_SESSION[self::SESSION_CSRF_KEY]['token'] !== $token){
            throw new ValidationException(
                message: "CSRF validation failed: token mismatched.",
                code: 400,
                failureType: ValidationFailureType::CSRF_MISMATCHED
            );
        }

        // Check if the token matches the cookie
        if (!isset($_COOKIE[self::COOKIE_NAME]) || $_COOKIE[self::COOKIE_NAME] !== $token){
            throw new ValidationException(
                message: "CSRF validation failed: cookie data doesn't match.",
                code: 400,
                failureType: ValidationFailureType::CSRF_COOKIE_MISMATCHED
            );
        }

        // If all checks pass, regenerate the token and return true
        $this->regenerateToken(); // Regenerate the token after successful validation
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
        if (!isset($_SESSION[self::SESSION_CSRF_KEY])){
            $this->generateToken();
        }
        
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
        unset($_SESSION[self::SESSION_CSRF_KEY]);
        setcookie(self::COOKIE_NAME, '', [
            self::SESSION_EXPIRY_KEY => time() - self::COOKIE_EXPIRY_TIME, // immediatelly invalidates token
            'path' => self::COOKIE_PATH,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
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
        return !isset($_SESSION[self::SESSION_CSRF_KEY]) || $_SESSION[self::SESSION_CSRF_KEY][self::SESSION_EXPIRY_KEY] <= time();
    }

    /**
     * Adds the CSRF token to the HTTP response headers.
     * 
     * This method sets the CSRF token as a custom HTTP header (`X-CSRF-Token`).
     * 
     * @return void
     */
    public function addTokenToHeader(): void {
        $token = $this->getToken();
        header("X-CSRF-Token: " . $token);
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
        unset($_SESSION[self::SESSION_CSRF_KEY]);
        return $this->getToken();
    }

    /**
     * Generates an HTML hidden input field containing the CSRF token.
     * 
     * This method creates a hidden input field with the CSRF token, which can be
     * included in forms to provide CSRF protection.
     * 
     * @return string The HTML for the hidden input field.`
     */
    public function getCSRFField(): string {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}