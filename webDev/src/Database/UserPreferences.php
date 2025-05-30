<?php
/**
 * UserPreferences Class File
 *
 * This file contains the `UserPreferences` class, which handles saving, loading and validating preferences.
 *
 * @file UserPreferences.php
 * @since 0.7.8
 * @package Database
 * @author Robkoo
 * @license TBD
 * @version 0.7.8
 * @see Database
 */

declare(strict_types=1);

namespace WebDev\Database;

/**
 * Class UserPreferences
 *
 * Provides methods to work with preferences.
 *
 * @package Database
 * @since 0.7.8
 * @see Database
 */
final class UserPreferences {
    /**
     * Save a preference into the database. Overwrites the previous reference if the keys and user id's match.
     *
     * @param int $uid The user id.
     * @param string $key The key of the preference.
     * @param string $value Value for the key.
     * @return array<string,bool|string> An associative array containing a success flag and a backend message.
     * 
     * @throws DatabaseException If anything inside the `execute` method, that this method uses, fails.
     */
    final public static function savePref(int $uid, string $key, string $value): array {
        // first validate the data
        /**
         * @var array<string,bool|string> $validationResult
         */
        $validationResult = self::validatePref($uid, $key);

        // if validation didn't succeed, return the assoc array as they match.
        if ($validationResult['success'] === false) return $validationResult;
        
        // Get database instance
        $db = Database::getInstance();

        // prepare query
        /**
         * Query that will insert the data into the table and if the prefKey or id is duplicate, it updates it.
         * @var string $sql
         */
        $sql = "INSERT INTO userPreferences (uid, prefKey, value)
                VALUES (:uid, :prefKey, :value)
                ON DUPLICATE KEY UPDATE value = VALUES(value)
                ";

        // attempt to save it into the database
        /**
         * Success flag for the execute method.
         * @var bool
         */
        $success = $db->execute(
            $sql,
            [
                'uid' => $uid,
                'prefKey' => $key,
                'value' => $value
            ]
        );
        
        return [
            'success' => $success
        ];
    }

    /**
     * Loads the preference based on the provided `$key`.
     *
     * @param int $uid The user ID to look for.
     * @param string $key The key to load the pref from.
     * @return string|false The value of the preference or false on no result or other failure.
     */
    final public static function loadPref(int $uid, string $key): string|false {
        // first validate the data
        /**
         * @var array<string,bool|string> $validationResult
         */
        $validationResult = self::validatePref($uid, $key);

        // if validation didn't succeed, return the assoc array as they match.
        if ($validationResult['success'] === false) return false;
        
        // Get database instance
        $db = Database::getInstance();

        // prepare query
        /**
         * Query that will load the value from the database based on the provided UID and preference key.
         * @var string $sql
         */
        $sql = "SELECT value
                FROM userPreferences
                WHERE uid = :uid
                AND   prefKey = :prefKey
        ";

        // attempt to fetch the data
        /**
         * The result of the query.
         * @var array<int,array<string,string>> $data
         */
        $data = $db->query(
            $sql,
            [
                'uid' => $uid,
                'prefKey' => $key
            ]  
        );

        // no result or something went wrong
        if (empty($data)) return false;

        // return the value
        return $data[0]['value'];
    }

    /**
     * Validate the provided preference values.
     *
     * @param int $uid The user ID to check.
     * @param string $key The key to check.
     * @return array<string,bool|string> An associative array containing a success flag and a backend message.
     */
    final public static function validatePref(int $uid, string $key): array {
        // validate id to not be lower than 1 or higher than next id
        if ($uid < 0){
            return [
                'success' => false,
                'backendMessage' => "User ID lower than 0. Value: $uid"
            ];
        }

        /**
         * The table instance for the table users.
         * @var Table
         */
        $table = Table::getInstance("users");

        /**
         * The next ID in the table users.
         * @var int
         */
        $nextId = $table->getNextId();

        // if the provided uid is the same or higher than the next id
        if ($uid >= $nextId){
            return [
                'success' => false,
                'backendMessage' => "User ID higher than $nextId. Value: $uid"
            ];
        }

        // validate the key against the possible key values from consts
        // dogshit way but im too lazy to change the obj to an assoc array to use a foreach loop xd
        // I HAVE TO MAKE THE DUMB CONSTS WORK BUT NO TIME
        if ($key !== "userTheme" && $key !== "gameWishlistBool"){
            return [
                'success' => false,
                'backendMessage' => "Pref key doesn't match the whitelist. Value: $key"
            ];
        }

        return [
            'success' => true
        ];
    }
}