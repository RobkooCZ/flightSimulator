<?php
/**
 * Abstract Handler for profile picture sources (file or link).
 *
 * @file Handler.php
 * @package Utilities
 * @author Robkoo
 * @since 0.7.7
 */
declare(strict_types=1);

namespace WebDev\Utilities;

use WebDev\Auth\User;
use WebDev\Database\Database;
use WebDev\Exception\LogicException;

/**
 * Abstract Handler Class
 * 
 * This class provides final methods such as the `__construct()`, `load()`, and `save()` for the subclasses that extend this class, alongside abstract methods that the said subclasses **HAVE** to define.
 *
 * @package Utilities
 * @since 0.7.7
 */
abstract class Handler {
    /**
     * The ID of the user for which to save the pfp.
     *
     * @var int
     */
    protected int $uid;

    /**
     * Either a link or a path to the image.
     *
     * @var string
     */
    protected string $source;

    /**
     * The database instance.
     *
     * @var Database
     */
    protected Database $db;

    /**
     * Public constructor for the Handler classes.
     *
     * @param int $uid The user id to tie the handler to
     * @throws DatabaseException If anything goes wrong with the query inside the `User::exists(...)` call.
     * @throws LogicException If the provided `$uid` is not found in the database.
     */
    final public function __construct(int $uid){
        // validate that the provided uid exists in the database
        if (!User::exists($uid)){
            throw new LogicException(
                "User with ID $uid doesn't exist in the database.",
                404,
                "Very uncommon because this constructor is supposed to be called with the id stored in the session, which should be valid, hence this is an logic exception."
            );
        }

        // set the objects property after we validated uid
        $this->uid = $uid;

        // get the database instance
        $this->db = Database::getInstance();
    }

    /**
     * Method to save the image source into the database.
     * 
     * @param string $source The source of the image. Either a link or a path to the image.
     * @return bool True if the save succeeded, false if it didn't.
     * @throws DatabaseException If any path of the `execute()` method fails.
     */
    final public function save(string $source): bool {
        // prepare query
        /**
         * @var string
         */
        $query = "UPDATE users SET profilePicture = :profilePicture WHERE id = :id";

        // execute it and capture the success flag into a var
        /**
         * @var bool
         */
        $success = $this->db->execute(
            $query,
            [
                'profilePicture' => $source,
                'id' => $this->uid
            ]
        );

        // return the result of the query
        return $success;
    }

    /**
     * Load the pfp source from the database.
     *
     * @return string|false The image source or false if the was no result.
     * @throws DatabaseException If any path of the `query()` method fails.
     */
    final public function load(): string {
        // prepare query
        /**
         * @var string
         */
        $query = "SELECT profilePicture FROM users WHERE id = :id";

        // execute it and capture the result
        /**
         * @var array<int,array<string,string|int>>
         */
        $result = $this->db->query(
            $query,
            [
                'id' => $this->uid
            ]
        );
        
        // return the default pfp is the contents are null, otherwise the pfp
        return $result[0]['profilePicture'] ?? "assets/images/pfps/default/default.jpg";
    }

    /**
     * Validate the resource. This method **is** implemented in subclasses that extend this class.
     * @param array|string $resource
     * @return array Validation result.
     */
    abstract public function validate(array|string $resource): array;

    /**
     * One method that does all the backend heavy lifting. This method **is** implemented in subclasses that extend this class.
     * @param array|string $resource
     * @return array Upload result.
     */
    abstract public function upload(array|string $resource): array;
}