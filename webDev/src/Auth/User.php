<?php
declare(strict_types=1);

namespace WebDev\Auth;

// php/db things
use DateTime;
use PDOException;

// my db class
use WebDev\Database\Database;

// auth
use WebDev\Auth\Auth;

// my db class enums
use WebDev\Database\Enum\Status;
use WebDev\Database\Enum\Role;

// exceptions
use WebDev\Exception\DatabaseException;
use WebDev\Exception\PHPException;

// logger
use WebDev\Logging\Logger;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\Loggers;

/**
 * User class representing a user entity in the application.
 * 
 * This class manages user data, authentication state, and provides methods for
 * user operations like status changes and role management. It implements a registry
 * pattern for caching user instances to reduce database queries.
 * 
 * ### Features:
 * - Registry pattern with time-based cache invalidation
 * - Session management for logged-in users
 * - Role and status validation
 * - Database persistence
 * - Activity tracking
 * 
 * ### Example usage:
 * ```php
 * // Load user by ID
 * $user = User::load(1);
 * 
 * // Get current logged-in user
 * $currentUser = User::current();
 * 
 * // Check user role
 * if ($user->hasRole('admin')){
 *     // Admin actions
 * }
 * ```
 */
class User {
    /**************************************
     * CONSTANTS & STATIC PROPERTIES
     **************************************/
    
    /**
     * @var array Registry of cached User objects indexed by user ID
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
    public const SESSION_LAST_AA_KEY = 'laa'; // Last Activity At
    
    /**
     * @var string Standard date format for database operations
     */
    private const TIME_FORMAT = "Y-m-d H:i:s";

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
    // private array $preferences; todo: implement user preferences

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
     */
    private function __construct(array $userData){
        Logger::log(
            "Creating User instance for ID: {$userData['id']} with username: {$userData['username']}",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $this->id = (int)$userData['id'];
        $this->username = $userData['username'];
        $this->role = $userData['role'];
        $this->status = $userData['status'];
        
        // Convert date strings to DateTime objects
        $this->lastActivityAt = $userData['lastActivityAt'] 
            ? new DateTime($userData['lastActivityAt'])
            : null;
        $this->createdAt = new DateTime($userData['createdAt']);
        $this->updatedAt = new DateTime($userData['updatedAt']);
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
     * @param array $userData Associative array of user data from database
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
        $requiredFields = ['id', 'username', 'role', 'status', 'lastActivityAt', 'createdAt', 'updatedAt'];
        
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
        if (!is_numeric($userData['id'])) {
            Logger::log(
                "Invalid user ID: '{$userData['id']}' - not numeric",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // username must pass validation
        if (!Auth::validateUser($userData['username'])) {
            Logger::log(
                "Invalid username: '{$userData['username']}' for user ID: {$userData['id']}",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // role must be valid
        if (!Role::validateRole($userData['role'])) {
            Logger::log(
                "Invalid role: '{$userData['role']}' for user ID: {$userData['id']}",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }

        // status must be valid
        if (!Status::validateStatus($userData['status'])) {
            Logger::log(
                "Invalid status: '{$userData['status']}' for user ID: {$userData['id']}",
                LogLevel::ERROR,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return false;
        }
        
        // Date validations - check if they're valid datetime strings
        $dateFields = ['lastActivityAt', 'createdAt', 'updatedAt'];
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
            )[0]; // access the first row of data (the only row)
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

        // valid data, create a new instance and add it to the registry
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

    /**************************************
     * SESSION & AUTH METHODS
     **************************************/
    
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
        $threshold = time() - ($minutes * 60); // Convert minutes to seconds
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
        if (!Role::validateRole($role)) {
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

        if ($result) {
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
        if (!Status::validateStatus($status)) {
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

        if ($result) {
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
        if (!Role::validateRole($role)) {
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

        if ($result) {
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

        if ($result) {
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