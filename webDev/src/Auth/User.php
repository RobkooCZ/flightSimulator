<?php
/**
 * User Class File
 *
 * This file contains the `User` class, which represents a user entity in the application.
 * It provides methods for user operations such as authentication, role and status management,
 * session handling, and database persistence.
 *
 * @file User.php
 * @since 0.7
 * @package Auth
 * @version 0.7.6
 * @see Database, Auth, Logger
 * @todo Implement user preferences
 */

declare(strict_types=1);

namespace WebDev\Auth;

// PHP/DB dependencies
use DateTime;
use PDOException;

// Database
use WebDev\Database\Database;

// Enums
use WebDev\Database\Enum\Status;
use WebDev\Database\Enum\Role;

// Exceptions
use WebDev\Exception\DatabaseException;
use WebDev\Exception\PHPException;

// Logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\Loggers;

/**
 * Class User
 *
 * Represents a user entity in the application. This class manages user data, authentication state,
 * and provides methods for user operations like status changes, role management, and activity tracking.
 * It implements a registry pattern for caching user instances to reduce database queries.
 *
 * @package Auth
 * @since 0.7
 * @see Database, Auth, Logger
 * @todo Implement user preferences
 */
class User {
    /**************************************
     * CONSTANTS & STATIC PROPERTIES
     **************************************/
    
    /**
     * @var array<string,int|User> Registry of cached User objects indexed by user ID
     */
    private static array $userRegistry = [];
    
    /**
     * @var int Time-to-live for cached user objects in seconds
     */
    private const USER_CACHE_TTL = 300;
    
    /**
     * @var string Session key for user ID
     */
    public const SESSION_ID_KEY = 'id';
    
    /**
     * @var string Session key for username
     */
    public const SESSION_USERNAME_KEY = 'username';
    
    /**
     * @var string Session key for user role
     */
    public const SESSION_ROLE_KEY = 'role';
    
    /**
     * @var string Session key for last activity timestamp
     */
    public const SESSION_LAST_AA_KEY = 'laa';// Last Activity At
    
    /**
     * @var string Standard date format for database operations
     */
    private const TIME_FORMAT = "Y-m-d H:i:s";

    /**
     * @var int
     */
    private const MAX_FAILED_LOGIN_ATTEMPTS = 5;

    /**************************************
     * INSTANCE PROPERTIES
     **************************************/
    
    /**
     * @var int User's unique identifier
     */
    private int $id;
    
    /**
     * @var string User's username
     */
    private string $username;
    
    /**
     * @var string User's role (e.g., 'admin', 'user', 'owner')
     */
    private string $role;
    
    /**
     * @var string User's status (e.g., 'active', 'inactive', 'deleted')
     */
    private string $status;

    /**
     * @var DateTime Timestamp when the user last logged in
     */
    private DateTime $lastLoginAt;
    
    /**
     * @var ?DateTime Timestamp of user's last activity
     */
    private ?DateTime $lastActivityAt;
    
    /**
     * @var DateTime Timestamp when the user was created
     */
    private DateTime $createdAt;
    
    /**
     * @var DateTime Timestamp when the user was last updated
     */
    private DateTime $updatedAt;

    private int $failedLoginAttempts;

    /**
     * The user agent for this specific object.
     *
     * @var UserAgent
     */
    private UserAgent $userAgent;

    // private array $preferences;todo: implement user preferences

    /**
     * The ipv4 adress of the user.
     *
     * Null if it couldn't be retrieved, otherwise contains the ipv4 adress.
     * 
     * @var ?string
     */
    private ?string $ipv4 = null;

    /**************************************
     * CONSTRUCTOR & FACTORY METHODS
     **************************************/    
    
    /**
     * Private constructor to create a User instance from database data.
     * 
     * This constructor is private to enforce the use of factory methods like
     * `load()` for creating User instances, maintaining the registry pattern.
     * 
     * @param array $userData Associative array of user data from the database
     * @throws DatabaseException If setting the failedLoginAttempts to zero fails.
     */
    private function __construct(array $userData){
        Logger::log(
            "Creating User instance for ID: {$userData['id']} with username: {$userData['username']}",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $this->id = (int)$userData['id'];
        $this->ipv4 = $userData['ipAddress'];
        $this->username = $userData['username'];
        $this->role = $userData['role'];
        $this->status = $userData['status'];
        
        // check if the failedLoginAttempts from the db is not zero
        if ((int)$userData['failedLoginAttempts'] !== 0){
            if (!$this->setFailedAttemptsToZero()){
                throw new DatabaseException(
                    "Failed to set failedLoginAttempts to zero.",
                    500
                );
            }

            // set the local variable to zero
            $this->failedLoginAttempts = 0;
        }
        else { // it is zero
            $this->failedLoginAttempts = (int)$userData['failedLoginAttempts'];
        }

        if (!$userData['lastLoginAt']){
            $this->lastLoginAt = new DateTime();
        }

        // Convert date strings to DateTime objects
        $this->lastActivityAt = $userData['lastActivityAt']
            ? new DateTime($userData['lastActivityAt'])
            : null;
        $this->createdAt = new DateTime($userData['createdAt']);
        $this->updatedAt = new DateTime($userData['updatedAt']);

        // Put the current User's UA into the variable
        $this->userAgent = UserAgent::for($this->id);
    }
    
    /**
     * Validates user data from the database to ensure it contains all required fields with valid values.
     * 
     * This method performs comprehensive validation on user data including:
     * - Checking for required fields
     * - Validating data types
     * - Validating username format
     * - Validating role and status values
     * - Validating date formats
     * 
     * @param array<string, int|string> $userData Associative array of user data from database
     * @return bool True if data is valid, false otherwise
     */
    private static function validateUserData(array $userData): bool {
        Logger::log(
            "Validating user data for ID: {$userData['id']}",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // Required fields
        $requiredFields = ['id', 'username', 'ipAddress', 'role', 'status', 'failedLoginAttempts', 'lastLoginAt', 'lastActivityAt', 'createdAt', 'updatedAt'];
        
        // Check if all required fields exist
        foreach ($requiredFields as $field){
            if (!array_key_exists($field, $userData)){
                Logger::log(
                    "Missing required field: '$field' for user ID: {$userData['id']}",
                    LogLevel::ERROR,
                    LoggerType::NORMAL,
                    Loggers::CMD
                );
                return false;
            }
        }
        
        // id must be an integer or numeric string
        if (!is_numeric($userData['id'])){
            Logger::log(
                "Invalid user ID: '{$userData['id']}' - not numeric",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // username must pass validation
        if (!Auth::validateUser($userData['username'])){
            Logger::log(
                "Invalid username: '{$userData['username']}' for user ID: {$userData['id']}",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // ip must be a valid ipv4 
        // also handle the case where the ipv4 is null from the database (don't set it but log it)
        if (is_null($userData['ipAddress'])){
            Logger::log(
                "IP address from database is NULL for user ID: {$userData['id']}",
                LogLevel::NOTICE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        elseif (!self::validateIpv4($userData['ipAddress'])){
            Logger::log(
                "Invalid IP address: '{$userData['ipAddress']}' for user ID: {$userData['id']}",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // role must be valid
        if (!Role::validateRole($userData['role'])){
            Logger::log(
                "Invalid role: '{$userData['role']}' for user ID: {$userData['id']}",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // status must be valid
        if (!Status::validateStatus($userData['status'])){
            Logger::log(
                "Invalid status: '{$userData['status']}' for user ID: {$userData['id']}",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // Validate failedLoginAttempts
        if (!is_numeric($userData['failedLoginAttempts'])){
            Logger::log(
            "Invalid failedLoginAttempts: '{$userData['failedLoginAttempts']}' - not numeric",
            LogLevel::ERROR,
            LoggerType::NORMAL,
            Loggers::CMD
            );
            return false;
        }

        $failedLoginAttempts = (int)$userData['failedLoginAttempts'];

        if ($failedLoginAttempts < 0 || $failedLoginAttempts > self::MAX_FAILED_LOGIN_ATTEMPTS){
            Logger::log(
                "failedLoginAttempts out of valid range (0-" . self::MAX_FAILED_LOGIN_ATTEMPTS . "): '$failedLoginAttempts'",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        if ($failedLoginAttempts !== 0){
            Logger::log(
                "failedLoginAttempts is not zero: '$failedLoginAttempts'",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        
        // Date validations - check if they're valid datetime strings
        $dateFields = ['lastLoginAt', 'lastActivityAt', 'createdAt', 'updatedAt'];
        foreach ($dateFields as $dateField){
            // Allow null for lastActivityAt
            if ($dateField === 'lastActivityAt' && $userData[$dateField] === null){
                continue;
            }
            
            // Check if it's a valid date string
            if ($userData[$dateField] && !strtotime($userData[$dateField])){
                Logger::log(
                    "Invalid date format in field '$dateField': '{$userData[$dateField]}' for user ID: {$userData['id']}",
                    LogLevel::ERROR,
                    LoggerType::NORMAL,
                    Loggers::CMD
                );
                return false;
            }
        }
        
        // All validations passed
        Logger::log(
            "User data validation successful for ID: {$userData['id']}",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        
        return true;
    }
    
    /**
     * Load a user by ID from database or registry cache.
     * 
     * This method implements the registry pattern, first checking the cache
     * for a valid user instance before querying the database. If found in
     * the database, the user is validated, cached, and returned.
     * 
     * ### Example usage:
     * ```php
     * try {
     *     $user = User::load(123);
     *     echo "Loaded user: " . $user->getUsername();
     * }
     * catch (DatabaseException $e){
     *     echo "Failed to load user: " . $e->getMessage();
     * }
     * ```
     * 
     * @param int $id The user ID to load
     * @return User The user object
     * @throws DatabaseException If user cannot be loaded or data is invalid
     */
    public static function load(int $id): User {
        // get current time
        $currentTime = time();

        // if the provided id is already added and is no older than USER_CACHE_TTL
        if (isset(self::$userRegistry[$id]) && $currentTime - self::$userRegistry[$id]['time'] < self::USER_CACHE_TTL){
            // return the cached object
            Logger::log(
                "Using cached user with ID: $id",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return self::$userRegistry[$id]['obj'];
        }

        // either the provided id isn't added or it's older than USER_CACHE_TTL
        Logger::log(
            "Loading user with ID: $id from database",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // get database instance
        $db = Database::getInstance();

        // query to select everything
        $query = "SELECT * FROM users WHERE id = :id LIMIT 1";

        // get the data using query method from Database class
        try {
            $userData = $db->query(
                $query,
                ['id' => $id]
            )[0];// access the first row of data (the only row)
        }
        catch (PDOException|DatabaseException $e){
            Logger::log(
                "Database error while loading user ID: $id - " . $e->getMessage(),
                LogLevel::ERROR,
                LoggerType::EXCEPTION,
                Loggers::CMD,
                __LINE__,
                __FILE__
            );
            throw new DatabaseException(
                "PDO Database error.",
                500, // internal server error
                $e,
                $query,
                $e->getCode(),
                $e->getMessage()
            );
        }

        // if the returned array is empty, something went wrong
        if (empty($userData)){
            Logger::log(
                "No user found with ID: $id",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            throw new DatabaseException(
                "Query failed to yield any result.",
                404, // not found
                null,
                $query
            );
        }

        // everything good so far, proceed with validation
        if (self::validateUserData($userData) === false){
            // data isnt valid, throw an exception
            Logger::log(
                "Invalid user data returned from database for ID: $id",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            throw new DatabaseException(
                "Query yielded invalid data.",
                400, // bad request
                null,
                $query
            );
        }

        // the data is valid, create a new instance and add it to the registry
        self::$userRegistry[$id] = [
            'time' => $currentTime,
            'obj' => new self($userData)
        ];

        Logger::log(
            "User loaded successfully from database - ID: $id, Username: {$userData['username']}",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // return the obj
        return self::$userRegistry[$id]['obj'];
    }

    /**
     * Load a user by username from the database or registry cache.
     * 
     * This method implements the registry pattern, first checking the cache
     * for a valid user instance before querying the database. If found in
     * the database, the user is validated, cached, and returned.
     * 
     * ### Example:
     * ```php
     * try {
     *     $user = User::loadUsername('john_doe');
     *     echo "Loaded user: " . $user->getUsername();
     * }
     * catch (DatabaseException $e){
     *     echo "Failed to load user: " . $e->getMessage();
     * }
     * ```
     * 
     * @param string $username The username to load
     * @return User The user object
     * @throws DatabaseException If user cannot be loaded or data is invalid
     */
    public static function loadUsername(string $username): User {
        // get current time
        $currentTime = time();

        // if the provided username is already added and is no older than USER_CACHE_TTL
        foreach (self::$userRegistry as $cachedUser){
            if ($cachedUser['obj']->getUsername() === $username && $currentTime - $cachedUser['time'] < self::USER_CACHE_TTL){
                // return the cached object
                Logger::log(
                    "Using cached user with Username: $username",
                    LogLevel::DEBUG,
                    LoggerType::NORMAL,
                    Loggers::CMD
                );
                return $cachedUser['obj'];
            }
        }

        // either the provided username isn't added or it's older than USER_CACHE_TTL
        Logger::log(
            "Loading user with Username: $username from database",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // get database instance
        $db = Database::getInstance();

        // query to select everything
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";

        // get the data using query method from Database class
        try {
            $userData = $db->query(
                $query,
                ['username' => $username]
            )[0]; // access the first row of data (the only row)
        } catch (PDOException|DatabaseException $e){
            Logger::log(
                "Database error while loading user Username: $username - " . $e->getMessage(),
                LogLevel::ERROR,
                LoggerType::EXCEPTION,
                Loggers::CMD,
                __LINE__,
                __FILE__
            );
            throw new DatabaseException(
                "PDO Database error.",
                500, // internal server error
                $e,
                $query,
                $e->getCode(),
                $e->getMessage()
            );
        }

        // if the returned array is empty, something went wrong
        if (empty($userData)){
            Logger::log(
                "No user found with Username: $username",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            throw new DatabaseException(
                "Query failed to yield any result.",
                404, // not found
                null,
                $query
            );
        }

        // everything good so far, proceed with validation
        if (self::validateUserData($userData) === false){
            // data isn't valid, throw an exception
            Logger::log(
                "Invalid user data returned from database for Username: $username",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            throw new DatabaseException(
                "Query yielded invalid data.",
                400, // bad request
                null,
                $query
            );
        }

        // the data is valid, create a new instance and add it to the registry
        self::$userRegistry[$userData['id']] = [
            'time' => $currentTime,
            'obj' => new self($userData)
        ];

        Logger::log(
            "User loaded successfully from database - ID: {$userData['id']}, Username: $username",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // return the obj
        return self::$userRegistry[$userData['id']]['obj'];
    }

    /**************************************
     * SESSION & AUTH METHODS
     **************************************/

    /**
     * Compare provided IPv4 and the IPv4 from the database.
     *
     * @param string $ipv4
     * @return ?bool True if the IPv4 addresses match, false if they don't. Null if the provided `$ipv4` isn't a valid IPv4 address.
     */
    private function compareIpv4db(string $ipv4): ?bool {
        // first validate the provided argument (you can never be safe enough)
        if (!self::validateIpv4($ipv4)){
            // spoiler: that's how the logs will look next update ;) (unless i change my mind)
            Logger::log("Invalid IPv4 address provided: $ipv4", LogLevel::WARNING, LoggerType::NORMAL, Loggers::CMD);
            return null;
        }

        // get database instance
        /**
         * @var Database
         */
        $db = Database::getInstance();

        // query
        /**
         * @var string
         */
        $query = "SELECT ipAddress FROM users WHERE id = :id";

        // execute the query
        /**
         * Associative array containing the found IP address.
         * @var array<string,string>
         */
        $queryResult = $db->query(
            $query,
            [
                'id' => $this->id
            ]
        )[0]; // access the first result

        /**
         * @var string
         */
        $dbIp = $queryResult['ipAddress'];

        // strcmp returns 0 if the two provided strings are exactly the same
        if (strcmp($ipv4, $dbIp) === 0){
            // ip addresses are equal
            return true;
        }
        else {
            Logger::log(
                "IP address mismatch for user ID: {$this->id}. Provided: $ipv4, Database: $dbIp",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }
    }

    /**
     * Validate a provided IPv4 address.
     * 
     * This method checks whether the provided string is a valid IPv4 address (format 0-255.0-255.0-255.0-255) or not.
     *
     * @param string $ipv4 The IP to check
     * @return bool True if the provided string is a valid IPv4 address, false otherwise
     */
    private static function validateIpv4(string $ipv4): bool {
        // return true if the provided IP is valid
        if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return true;
        return false;
    }

    /**
     * Retrieve the IPv4 address of the user.
     * 
     * This method attempts to determine the user's IPv4 address by checking
     * various server environment variables. If no valid address is found,
     * it returns null.
     * 
     * @return ?string The IPv4 address or null if not available
     */
    private static function retrieveIpv4(): ?string {
        /**
         * Array containing the trusted IPs
         * 
         * @var array<int,string>
         */
        $trustedIps = [
            '127.0.0.1' // localhost
        ];

        /**
         * Variable that holds the return value. Either null or a valid IPv4 adress.
         * 
         * @var ?string
         */
        $ipv4 = null;

        /**
         * Array containing the headers the method checks for the IPv4 adress.
         * 
         * @var array<int,string>
         */
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
    
        // loop through all the headers
        foreach ($headers as $key){
            // if the server variable isn't empty for a specific header
            if (!empty($_SERVER[$key])){
                // get the ip list of the server superglobal at the specific header
                $ipList = explode(',', $_SERVER[$key]);

                // loop through all the ips in that list and return the first valid one
                foreach ($ipList as $ip){
                    // strip whitespace
                    $ip = trim($ip);
                    
                    // if the IP is ::1 (thats localhost) change it to 127.0.0.1 to pass the filter.
                    if ($ip = "::1") $ip = "127.0.0.1";

                    // validate the provided IP with the IPV4 filter flag
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
                        $ipv4 = $ip;
                        
                        // ip found, check if its trusted
                        if (!in_array($ipv4, $trustedIps, true)){
                            Logger::log(
                                "User with untrusted IPv4 adress: $ipv4",
                                LogLevel::WARNING,
                                LoggerType::NORMAL,
                                Loggers::CMD
                            );
                        }

                        // break out either way
                        break 2;// break out of this foreach and out of the outer foreach
                    }
                }
            }
        }
    
        // return the ip adress or null
        return $ipv4 ?: null;
    }

   
    
    /**
     * Get the currently logged in user from session.
     * 
     * This method retrieves the current user from the registry by session ID.
     * If no user is logged in or the session isn't started, it returns null.
     * 
     * ### Example usage:
     * ```php
     * $currentUser = User::current();
     * if ($currentUser){
     *     echo "Welcome back, " . $currentUser->getUsername();
     * }
     * else {
     *     echo "Please log in";
     * }
     * ```
     * 
     * @return ?User The current user or null if not logged in
     * @throws PHPException If session start fails
     */
    public static function current(): ?User {
        // make sure the session is started
        if (session_status() === PHP_SESSION_NONE){
            // handle the case where session can't be started
            if (!@session_start()){
                Logger::log(
                    "Failed to start session in User::current()",
                    LogLevel::ERROR,
                    LoggerType::EXCEPTION,
                    Loggers::CMD,
                    __LINE__,
                    __FILE__
                );
                throw new PHPException(
                    "Failed to start session for userException",
                    500
                );
            }
        }

        // Check if user ID exists in session
        if (!isset($_SESSION['id'])){
            Logger::log(
                "No user ID found in session",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return null;
        }
        
        // put it in a var for simplicity
        $id = $_SESSION['id'];

        // not set
        if (!isset(self::$userRegistry[$id])){
            Logger::log(
                "User ID: $id found in session but not in registry",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return null;
        }

        // otherwise return current object
        Logger::log(
            "Current user retrieved - ID: $id, Username: {$_SESSION['username']}",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        return self::$userRegistry[$id]['obj'];
    }

    /**
     * Store user data in session.
     * 
     * This method saves essential user data in the session for persistence
     * across requests. Data is validated before storage to ensure integrity.
     * 
     * ### Example usage:
     * ```php
     * $user = User::load(123);
     * $user->storeInSession();
     * // User is now available through User::current() in future requests
     * ```
     * 
     * @return void
     */
    public function storeInSession(): void {
        Logger::log(
            "Storing user in session - ID: {$this->id}, Username: {$this->username}",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        if (is_numeric($this->id)){
            $_SESSION[self::SESSION_ID_KEY] = $this->id;
        }

        if (Auth::validateUser($this->username)){
            $_SESSION[self::SESSION_USERNAME_KEY] = $this->username;
        }

        if (Role::validateRole($this->role)){
            $_SESSION[self::SESSION_ROLE_KEY] = $this->role;
        }

        if ($this->lastActivityAt instanceof DateTime){
            $_SESSION[self::SESSION_LAST_AA_KEY] = $this->lastActivityAt->format(self::TIME_FORMAT);
        }

        Logger::log(
            "User session data stored successfully",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
    }
    
    /**
     * Remove all user data from session (logout).
     * 
     * This method clears all user-related session variables,
     * effectively logging the user out.
     * 
     * ### Example usage:
     * ```php
     * User::removeFromSession();
     * // User is now logged out
     * ```
     * 
     * @return void
     */
    public static function removeFromSession(): void {
        $username = $_SESSION[self::SESSION_USERNAME_KEY] ?? 'Unknown';
        $id = $_SESSION[self::SESSION_ID_KEY] ?? 'Unknown';
        
        Logger::log(
            "Removing user from session - ID: $id, Username: $username",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // unset all set variables
        unset(
            $_SESSION[self::SESSION_ID_KEY],
            $_SESSION[self::SESSION_USERNAME_KEY],
            $_SESSION[self::SESSION_ROLE_KEY],
            $_SESSION[self::SESSION_LAST_AA_KEY]
        );

        Logger::log(
            "User session data cleared successfully",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
    }
    
    /**************************************
     * GETTERS
     **************************************/
    
    /**
     * Get the user's ID.
     * 
     * @return int User's unique identifier
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Get the user's username.
     * 
     * @return string User's username
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * Get the user's IPv4 address.
     * 
     * @return string User's IPv4 address
     */
    public function getIpv4(): string {
        return $this->ipv4;
    }

    /**
     * Get the user's role.
     * 
     * @return string User's role (e.g., 'admin', 'user', 'owner')
     */
    public function getRole(): string {
        return $this->role;
    }

    /**
     * Get the user's status.
     * 
     * @return string User's status (e.g., 'active', 'inactive', 'deleted')
     */
    public function getStatus(): string {
        return $this->status;
    }

    /**
     * Get the user's failed login attempts.
     * 
     * This method is useless as the failedAttempts var is always set to 0 after successful login.
     * 
     * @deprecated 0.7.6
     *
     * @return int The failed login attempts (always 0).
     */
    public function getFailedLoginAttempts(): int {
        return $this->failedLoginAttempts;
    }

    /**
     * Get the timestamp of the user's last login.
     * 
     * @return DateTime Timestamp of user's last login
     */
    public function getLastLoginAt(): DateTime {
        return $this->lastLoginAt;
    }

    /**
     * Get the timestamp of user's last activity.
     * 
     * @return ?DateTime Timestamp of last activity or null if not available
     */
    public function getLastActivityAt(): ?Datetime {
        return ($this->lastActivityAt) ? $this->lastActivityAt : null;
    }

    /**
     * Get the timestamp when the user was created.
     * 
     * @return DateTime Timestamp of user creation
     */
    public function getCreatedAt(): DateTime {
        return $this->createdAt;
    }

    /**
     * Get the timestamp when the user was last updated.
     * 
     * @return DateTime Timestamp of last update
     */
    public function getUpdatedAt(): DateTime {
        return $this->updatedAt;
    }

    /**************************************
     * STATUS METHODS
     **************************************/
    
    /**
     * Checks if the user account is active.
     * 
     * This method verifies if the user's status is set to "active".
     * 
     * ### Example usage:
     * ```php
     * if (!$user->isActive()){
     *     echo "This account is not active";
     * }
     * ```
     * 
     * @return bool True if status is "active", false otherwise
     */
    public function isActive(): bool {
        return ($this->status === Status::ACTIVE) ? true : false;
    }
    
    /**
     * Checks if the user account has been deleted.
     * 
     * This method verifies if the user's status is set to "deleted".
     * 
     * ### Example usage:
     * ```php
     * if ($user->isDeleted()){
     *     echo "This account has been deleted";
     * }
     * ```
     * 
     * @return bool True if status is "deleted", false otherwise
     */
    public function isDeleted(): bool {
        return ($this->status === Status::DELETED) ? true : false;
    }
    
    /**
     * Checks if the user is currently online based on their last activity time.
     * 
     * This method determines if a user is currently active by comparing
     * their last activity timestamp against a specified threshold.
     * 
     * ### Example usage:
     * ```php
     * if ($user->isOnline()){
     *     echo "User is currently online";
     * }
     * else {
     *     echo "User is offline";
     * }
     * ```
     *
     * @param int $minutes Number of minutes to consider a user "online" after their last activity
     * @return bool True if the user has been active within the specified time frame
     */
    public function isOnline(int $minutes = 15): bool {
        $threshold = time() - ($minutes * 60);// Convert minutes to seconds
        $isOnline = $this->lastActivityAt && $this->lastActivityAt->getTimestamp() >= $threshold;
        
        Logger::log(
            "User online check - ID: {$this->id}, Username: {$this->username}, Online: " . ($isOnline ? "Yes" : "No") . " (threshold: $minutes minutes)",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        
        return $isOnline;
    }
    
    /**************************************
     * ROLE METHODS
     **************************************/
    
    /**
     * Checks if user has the specified role.
     * 
     * This method validates the provided role and checks if the user
     * has been assigned that role.
     * 
     * ### Example usage:
     * ```php
     * if ($user->hasRole('admin')){
     *     // Perform admin-only operations
     * }
     * ```
     * 
     * @param string $role Role to check against
     * @return bool True if user has this role, false otherwise
     */
    public function hasRole(string $role): bool {
        // validate the role 
        if (!Role::validateRole($role)){
            Logger::log(
                "Invalid role check: '$role' for user ID: {$this->id}",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        $hasRole = $this->role === $role;
        Logger::log(
            "Role check for user ID: {$this->id}, Username: {$this->username} - Has '$role': " . ($hasRole ? "Yes" : "No"),
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // return a bool based on whether the user has the selected role or not
        return $hasRole;
    }
    
    /**************************************
     * DATA MANIPULATION METHODS
     **************************************/
    
    /**
     * Record current user activity.
     * 
     * This method updates the user's last activity timestamp in both
     * the object instance and the database. It is advised to use this whenever the user does
     * anything.
     * 
     * ### Example usage:
     * ```php
     * $user->recordActivity();
     * // Last activity timestamp is now updated
     * ```
     * 
     * @return bool True if activity was recorded successfully
     * @throws DatabaseException If database update fails
     */
    public function recordActivity(): bool {
        Logger::log(
            "Recording activity for user ID: {$this->id}, Username: {$this->username}",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // get new date and time
        $currentDate = date(self::TIME_FORMAT);

        // update this object's local property
        $this->lastActivityAt = new DateTime($currentDate);

        // update the static registry
        self::$userRegistry[$this->id] = [
            'time' => time(), // Current timestamp 
            'obj' => $this // The user object
        ];

        // update the db "lastActivityAt" field
        $db = Database::getInstance();

        // query
        $query = "UPDATE users SET lastActivityAt = :lastActivityAt WHERE id = :id";

        // execute the query
        $result = $db->execute(
            $query,
            [
                'lastActivityAt' => $currentDate,
                'id' => $this->id
            ]
        );

        if ($result){
            Logger::log(
                "Activity recorded successfully for user ID: {$this->id}",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "Failed to record activity for user ID: {$this->id}",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return $result;
    }

    /**
     * Reset the user's failed login attempts to zero and update the database.
     *
     * Sets the user's failed login attempts to zero in the object, updates the registry cache,
     * and persists the change to the database. Logs the outcome for auditing and debugging.
     *
     * @return bool True if the database update was successful, false otherwise
     *
     * ### Example
     * ```php
     * $user->setFailedAttemptsToZero();
     * // failedLoginAttempts is now 0 in the object, registry, and database
     * ```
     */
    public function setFailedAttemptsToZero(): bool {
        Logger::log(
            "Resetting failed login attempts for user ID: {$this->id}, Username: {$this->username}",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $this->failedLoginAttempts = 0;

        self::$userRegistry[$this->id] = [
            'time' => time(),
            'obj' => $this
        ];

        $db = Database::getInstance();
        $query = "UPDATE users SET failedLoginAttempts = :failedLoginAttempts WHERE id = :id";

        $result = $db->execute(
            $query,
            [
                'failedLoginAttempts' => 0,
                'id' => $this->id
            ]
        );

        if ($result){
            Logger::log(
                "Failed login attempts reset successfully for user ID: {$this->id}, Username: {$this->username}",
                LogLevel::SUCCESS,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "Failed to reset failed login attempts for user ID: {$this->id}, Username: {$this->username}",
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return $result;
    }

    /**
     * Update the database after a successful login.
     *
     * Updates the user's status, failed login attempts, last login, and last activity timestamps
     * in both the object and the database in a single query. Also updates the registry cache.
     * Logs the operation and its result.
     *
     * @return bool True if the update was successful, false otherwise
     *
     * ### Example
     * ```php
     * $user->updateDbAfterLogin();
     * // User's login and activity timestamps, status, and failed attempts are updated in DB and object
     * ```
     */
    public function updateDbAfterLogin(): bool {
        Logger::log(
            "Starting updateDbAfterLogin for user ID: {$this->id}, Username: {$this->username}",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        /**
         * @var bool
         */
        $result = true; // assume success until proven otherwise

        /**
         * @var Database
         */
        $db = Database::getInstance();

        /**
         * @var string
         */
        $currentDate = date(self::TIME_FORMAT);

        /**
         * @var int
         */
        $timestamp = strtotime($currentDate);

        // set the variables in this instance
        $this->ipv4 = self::retrieveIpv4();
        $this->failedLoginAttempts = 0;
        $this->lastActivityAt = new DateTime($currentDate);
        $this->lastLoginAt = new DateTime($currentDate);
        $this->status = "active";

        // update the static registry with the new instance and current timestamp
        self::$userRegistry[$this->id] = [
            'time' => $timestamp,
            'obj' => $this
        ];

        // prepare the query to update the database in one go
        /**
         * @var string
         */
        $query = "UPDATE users
                  SET ipAddress = :ipAddress,
                      status = :status,
                      failedLoginAttempts = :failedLoginAttempts,
                      lastLoginAt = :lastLoginAt,
                      lastActivityAt = :lastActivityAt
                  WHERE id = :id";

        /**
         * Success flag for the execute method.
         * @var bool
         */
        $queryResult = $db->execute(
            $query,
            [
                'ipAddress' => $this->ipv4,
                'status' => $this->status,
                'failedLoginAttempts' => $this->failedLoginAttempts,
                'lastLoginAt' => $this->lastLoginAt->format(self::TIME_FORMAT),
                'lastActivityAt' => $this->lastActivityAt->format(self::TIME_FORMAT),
                'id' => $this->id
            ]
        );

        if ($queryResult){
            $result = true;
            Logger::log(
                "Database update successful for user ID: {$this->id}, Username: {$this->username}",
                LogLevel::SUCCESS,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            $result = false;
            Logger::log(
                "Database update failed for user ID: {$this->id}, Username: {$this->username}",
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return $result;
    }

    /**
     * Increment the user's failed login attempts and update the database.
     *
     * Increments the failed login attempts counter in the object, updates the registry,
     * and persists the change to the database. If the maximum allowed attempts is reached,
     * returns null. Logs the operation and its result.
     *
     * @return ?bool True if update was successful, false if not, null if max attempts reached
     *
     * ### Example
     * ```php
     * $user->incrementFailedLogin();
     * // failedLoginAttempts is incremented and updated in DB and object
     * ```
     */
    public function incrementFailedLogin(): ?bool {
        Logger::log(
            "Incrementing failed login attempts for user ID: {$this->id}, Username: {$this->username} (current: {$this->failedLoginAttempts})",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        if ($this->failedLoginAttempts++ >= self::MAX_FAILED_LOGIN_ATTEMPTS){
            Logger::log(
                "User ID: {$this->id}, Username: {$this->username} has reached the maximum failed login attempts (" . self::MAX_FAILED_LOGIN_ATTEMPTS . ")",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return null;
        }

        self::$userRegistry[$this->id] = [
            'time' => time(),
            'obj' => $this
        ];

        $db = Database::getInstance();
        $query = "UPDATE users SET failedLoginAttempts = :failedLoginAttempts WHERE id = :id";

        $result = $db->execute(
            $query,
            [
                'failedLoginAttempts' => $this->failedLoginAttempts,
                'id' => $this->id
            ]
        );

        if ($result){
            Logger::log(
                "failedLoginAttempts updated successfully for user ID: {$this->id}, Username: {$this->username} (now: {$this->failedLoginAttempts})",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "Failed to update failedLoginAttempts for user ID: {$this->id}, Username: {$this->username}",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return $result;
    }
    
    /**
     * Update user status.
     * 
     * This method updates the user's status in both the object instance
     * and the database after validating the provided status.
     * 
     * ### Example usage:
     * ```php
     * $user->setStatus('inactive');
     * // User is now marked as inactive
     * ```
     * 
     * @param string $status New status
     * @return bool True if update was successful, false if invalid status
     * @throws DatabaseException If database update fails
     */
    public function setStatus(string $status): bool {
        Logger::log(
            "Attempting to change status for user ID: {$this->id}, Username: {$this->username} from '{$this->status}' to '$status'",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // check if the provided status is valid
        if (!Status::validateStatus($status)){
            Logger::log(
                "Invalid status: '$status' for user ID: {$this->id}",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // update the updatedAt local var
        $currentDate = date(self::TIME_FORMAT);
        $this->updatedAt = new DateTime($currentDate);

        // set the local variable in the object
        $this->status = $status;

        // update the static registry
        self::$userRegistry[$this->id] = [
            'time' => time(), // current timestamp
            'obj' => $this // updated object
        ];

        // update the status in the database alongside updatedAt
        $query = "UPDATE users SET status = :status, updatedAt = :updatedAt WHERE id = :id";

        // get db instance
        $db = Database::getInstance();

        // execute the query and return the bool success
        $result = $db->execute(
            $query,
            [
                'status' => $status,
                'updatedAt' => $currentDate,
                'id' => $this->id
            ]
        );

        if ($result){
            Logger::log(
                "Status updated successfully for user ID: {$this->id} to '$status'",
                LogLevel::SUCCESS,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "Failed to update status for user ID: {$this->id} to '$status'",
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return $result;
    }
    
    /**
     * Update user role.
     * 
     * This method updates the user's role in both the object instance
     * and the database after validating the provided role.
     * 
     * ### Example usage:
     * ```php
     * $user->setRole('admin');
     * // User is now an admin
     * ```
     * 
     * @param string $role New role
     * @return bool True if update was successful, false if invalid role
     * @throws DatabaseException If database update fails
     */
    public function setRole(string $role): bool {
        Logger::log(
            "Attempting to change role for user ID: {$this->id}, Username: {$this->username} from '{$this->role}' to '$role'",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // check if the provided role is valid
        if (!Role::validateRole($role)){
            Logger::log(
                "Invalid role: '$role' for user ID: {$this->id}",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // update the updatedAt local var
        $currentDate = date(self::TIME_FORMAT);
        $this->updatedAt = new DateTime($currentDate);

        // set the local variable in the object
        $this->role = $role;

        // update the static registry
        self::$userRegistry[$this->id] = [
            'time' => time(), // current timestamp
            'obj' => $this // updated object
        ];

        // update the role in the database alongside updatedAt
        $query = "UPDATE users SET role = :role, updatedAt = :updatedAt WHERE id = :id";

        // get db instance
        $db = Database::getInstance();

        // execute the query and return the bool success
        $result = $db->execute(
            $query,
            [
                'role' => $role,
                'updatedAt' => $currentDate,
                'id' => $this->id
            ]
        );

        if ($result){
            Logger::log(
                "Role updated successfully for user ID: {$this->id} to '$role'",
                LogLevel::SUCCESS,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "Failed to update role for user ID: {$this->id} to '$role'",
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return $result;
    }
    
    /**
     * Save all user data.
     * 
     * This method persists all current user property values to the database.
     * It's useful after making multiple changes to a user object.
     * 
     * ### Example usage:
     * ```php
     * $user->setRole('admin');
     * $user->setStatus('active');
     * $user->save();
     * // All changes are now saved to the database
     * ```
     * 
     * @return bool True if save was successful
     * @throws DatabaseException If database update fails
     */
    public function save(): bool {
        Logger::log(
            "Saving all user data for ID: {$this->id}, Username: {$this->username}",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // update the updatedAt local var
        $currentDate = date(self::TIME_FORMAT);
        $this->updatedAt = new DateTime($currentDate);

        // update the static registry
        self::$userRegistry[$this->id] = [
            'time' => time(), // current timestamp
            'obj' => $this // updated object
        ];

        // save all the data into the database
        $query = "UPDATE users
                  SET username = :username, role = :role, status = :status, lastActivityAt = :lastActivityAt, updatedAt = :updatedAt
                  WHERE id = :id";

        // get db instance
        $db = Database::getInstance();

        // execute the query and return the bool success
        $result = $db->execute(
            $query,
            [
                'username' => $this->username,
                'role' => $this->role,
                'status' => $this->status,
                'lastActivityAt' => $this->lastActivityAt ? $this->lastActivityAt->format(self::TIME_FORMAT) : null,
                'updatedAt' => $currentDate,
                'id' => $this->id
            ]
        );

        if ($result){
            Logger::log(
                "User data saved successfully for ID: {$this->id}",
                LogLevel::SUCCESS,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "Failed to save user data for ID: {$this->id}",
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return $result;
    }
    
    /**************************************
     * REGISTRY MANAGEMENT METHODS
     **************************************/
    
    /**
     * Remove a user from the registry cache.
     * 
     * This method invalidates a specific user's cache, forcing
     * the next request to this ID to reload data from the database.
     * 
     * ### Example usage:
     * ```php
     * User::invalidateCache(123);
     * // User with ID 123 is now removed from the registry.
     * ```
     * 
     * @param int $id ID of user to remove from registry
     * @return void
     */
    public static function invalidateCache(int $id): void {
        if (isset(self::$userRegistry[$id])){
            Logger::log(
                "Invalidating cache for user ID: $id",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            unset(self::$userRegistry[$id]);
        }
        else {
            Logger::log(
                "Attempted to invalidate cache for non-cached user ID: $id",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
    }
    
    /**
     * Clear all users from registry.
     * 
     * This method purges the entire user registry cache, forcing
     * all subsequent user requests to reload from the database.
     * 
     * ### Example usage:
     * ```php
     * User::clearRegistry();
     * // The whole registry is now cleared.
     * ```
     * 
     * @return void
     */
    public static function clearRegistry(): void {
        $count = count(self::$userRegistry);
        Logger::log(
            "Clearing entire user registry cache ($count users)",
            LogLevel::INFO,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        self::$userRegistry = [];
    }
}