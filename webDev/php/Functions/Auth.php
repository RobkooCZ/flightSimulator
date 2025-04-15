<?php
declare(strict_types=1);

namespace WebDev\Functions;

use WebDev\config\Database;
use WebDev\Functions\CSRF;
use WebDev\Functions\LogicException;
use WebDev\Functions\PHPException;
use WebDev\Functions\DatabaseException;
use WebDev\Functions\AuthenticationException;
use WebDev\Functions\AuthenticationType;
use WebDev\Functions\ValidationException;
use WebDev\Functions\ValidationFailureType;

class Auth {
    private static ?Auth $instance = null; // singleton
    private Database $db; // connection to the db

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
     * Private constructor to initialize the Auth class.
     * 
     * This method establishes a database connection and ensures that the session
     * is started if it hasn't already been started.
     */
    private function __construct(){
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE){
            session_start();
        }
    }

    /**
     * Retrieves the singleton instance of the Auth class.
     * 
     * This method ensures that only one instance of the Auth class is created
     * during the application's lifecycle.
     * 
     * ### Example usage:
     * ```php
     * use WebDev\Functions\Auth;
     * 
     * $auth = Auth::getInstance();
     * ```
     * 
     * @return Auth The singleton instance of the Auth class.
     */
    final public static function getInstance(): Auth {
        if (self::$instance === null){
            self::$instance = new Auth();
        }
        return self::$instance;
    }

    /**
     * Validates a username.
     * 
     * This method checks if the username contains only alphanumeric characters
     * and underscores. Throws an exception if the username is invalid.
     * 
     * ### Example usage:
     * ```php
     * use WebDev\Functions\Auth;
     * 
     * Auth::validateUser('valid_username'); // Returns true
     * ```
     * 
     * @param string $user The username to validate.
     * @return bool True if the username is valid.
     * @throws ValidationException If the username is invalid.
     */
    final public static function validateUser(string $user): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $user)){
            throw new ValidationException(message: "Invalid characters in username.", failureType: ValidationFailureType::INVALID_USERNAME);
        }
        return true;
    }

    /**
     * Validates a password.
     * 
     * This method checks if the password meets the following criteria:
     * - At least 8 characters long
     * - Contains at least one uppercase letter
     * - Contains at least one lowercase letter
     * - Contains at least one number
     * - Contains at least one special character
     * 
     * ### Example usage:
     * ```php
     * use WebDev\Functions\Auth;
     * 
     * Auth::validatePass('StrongP@ssw0rd'); // No exception thrown
     * ```
     * 
     * @param string $pass The password to validate.
     * @return void
     * @throws ValidationException If the password does not meet the criteria.
     */
    final public static function validatePass(string $pass): void {
        if (strlen($pass) < 8){
            throw new ValidationException("Password must be at least 8 characters long.", failureType: ValidationFailureType::PASSWORD_TOO_SHORT);
        }
        if (!preg_match('/[A-Z]/', $pass)){
            throw new ValidationException("Password must include at least one uppercase letter.", failureType: ValidationFailureType::PASSWORD_MISSING_UPPERCASE);
        }
        if (!preg_match('/[a-z]/', $pass)){
            throw new ValidationException("Password must include at least one lowercase letter.", failureType: ValidationFailureType::PASSWORD_MISSING_LOWERCASE);
        }
        if (!preg_match('/[0-9]/', $pass)){
            throw new ValidationException("Password must include at least one number.", failureType: ValidationFailureType::PASSWORD_MISSING_NUMBER);
        }
        if (!preg_match('/[\W]/', $pass)){
            throw new ValidationException("Password must include at least one special character.", failureType: ValidationFailureType::PASSWORD_MISSING_SPECIAL_CHAR);
        }
    }

    /**
     * Registers a new user.
     * 
     * This method hashes the user's password with a unique salt and stores the
     * user in the database.
     * 
     * ### Example usage:
     * ```php
     * use WebDev\Functions\Auth;
     * 
     * $auth = Auth::getInstance();
     * $auth->register('new_user', 'StrongP@ssw0rd');
     * ```
     * 
     * @param string $username The username of the new user.
     * @param string $password The password of the new user.
     * @return void
     * @throws PHPException If the password hash fails.
     * @throws DatabaseException If the query execution fails.
     */
    final public function register(string $username, string $password): void {
        $salt = bin2hex(random_bytes(16)); // Generate a 128-bit random salt
        $combinedPassword = $password . $salt; // Combine the password with the salt
        $hashedPassword = $this->hashPass($combinedPassword); // Hash the password

        if ($hashedPassword === false){
            throw new PHPException("Failed to hash password.");
        }

        $parameters = [
            "username" => $username,
            "password" => $hashedPassword,
            "salt" => $salt
        ];

        if (!$this->db->execute("INSERT INTO users (username, password, salt, lastActivityAt, createdAt, updatedAt) VALUES (:username, :password, :salt, NOW(), NOW(), NOW())", $parameters)){
            throw new DatabaseException("Failed to execute query.", query: "INSERT INTO users (username, password, salt, lastActivityAt, createdAt, updatedAt)");
        }
    }

    /**
     * Logs in a user.
     * 
     * This method verifies the username and password, and returns the user's
     * details if the login is successful.
     * 
     * ### Example usage:
     * ```php
     * use WebDev\Functions\Auth;
     * 
     * $auth = Auth::getInstance();
     * $user = $auth->login('existing_user', 'StrongP@ssw0rd');
     * echo $user['username']; // Outputs: 'existing_user'
     * ```
     * 
     * @param string $user The username of the user.
     * @param string $pass The password of the user.
     * @return array An associative array containing the user's details.
     * @throws AuthenticationException If the login fails.
     * @throws DatabaseException If the query resulted in no results.
     */
    final public function login(string $user, string $pass): array {
        // Query the database for the user's credentials
        $result = $this->db->query(
            sql: "SELECT id, username, password, salt FROM users WHERE username = :username",
            parameters: ["username" => $user]
        );

        // Check if the query returned results
        if ($result && count($result) > 0){
            $hashedPassword = $result[0]['password'];
            $salt = $result[0]['salt'];

            // Verify the password
            if (password_verify($pass . $salt, $hashedPassword)){
                session_regenerate_id(true); // Prevent session fixation
                return [
                    'id' => $result[0]['id'],
                    'username' => $result[0]['username']
                ];
            } 
            else {
                throw new AuthenticationException(
                    message: "Invalid username or password.",
                    previous: null,
                    authType: AuthenticationType::LOGIN,
                    code: 401 // Unauthorized
                );
            }
        }

        // If no results were found, throw a database exception
        throw new DatabaseException(
            message: "No results found.",
            code: 404, // Not found
            query: "SELECT id, username, password, salt FROM users"
        );
    }

    /**
     * Logs out the current user.
     * 
     * This method clears the session data, destroys the session, and removes
     * the session cookie.
     * 
     * ### Example usage:
     * ```php
     * use WebDev\Functions\Auth;
     * 
     * $auth = Auth::getInstance();
     * $auth->logout();
     * ```
     * 
     * @return void
     */
    final public function logout(): void {
        if (session_status() === PHP_SESSION_ACTIVE){
            session_unset();
            session_destroy();

            if (ini_get("session.use_cookies")){
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
            }

            session_start();
            session_regenerate_id(true);

            CSRF::getInstance()->clearToken();
        } 
        else {
            error_log("Logout attempted without an active session.");
        }
    }

    /**
     * Hashes a password using a secure algorithm.
     * 
     * @param string $pass The password to hash.
     * @return string The hashed password.
     */
    private function hashPass(string $pass): string {
        return password_hash($pass, PASSWORD_DEFAULT);
    }
}