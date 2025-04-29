<?php
/**
 * Loads and provides access to global constants from a shared JSON file.
 *
 * This file defines the `ConstantsLoader` class for loading and retrieving
 * configuration constants from `constants.json` for use throughout the codebase.
 * It also exports a `$consts` object for convenient access to key constants.
 *
 * @file constants.php
 * @since 0.7.2
 * @package Constants
 * @author Robkoo
 * @license TBD
 * @version 0.7.4
 * @see constants.json, constants.js
 * @todo Expand `$consts` as new constants are added to the JSON file.
 */

declare(strict_types=1);

namespace WebDev\Assets;

/**
 * Loads and retrieves constants from a shared JSON configuration file.
 *
 * Provides static methods to fetch deeply nested configuration values
 * and to export key constant objects for use in PHP code.
 *
 * @package FlightSimWeb
 * @since 0.7.2
 * @see constants.json
 * @throws \Exception if the configuration file cannot be read or decoded,
 *         or if a requested key is missing.
 */
class ConstantsLoader {
    const CONSTANTS_FILE_PATH = __DIR__ . '/constants.json';

    /** @var array Loaded constants data */
    private static array $data = [];

    /**
     * Loads the JSON constants file into memory if not already loaded.
     *
     * @throws \Exception if the file cannot be read or decoded.
     */
    private static function loadJson(): void {
        if (empty(self::$data)){
            $rawData = file_get_contents(self::CONSTANTS_FILE_PATH);
            self::$data = json_decode($rawData, true);

            if (self::$data === null){
                throw new \Exception("Error decoding configuration file.");
            }
        }
    }

    /**
     * Retrieves a constant value by dot-notated key.
     *
     * @param string $key Dot-notated key (e.g. "api.ajaxApi")
     * @return mixed The value found at the specified key.
     * @throws \Exception if the key does not exist.
     *
     * ### Example
     * 
     * ```php
     * $apiUrl = ConstantsLoader::get('api.ajaxApi.url');
     * ```
     */
    public static function get(string $key){
        self::loadJson();

        $keys = explode('.', $key);
        $value = self::$data;

        foreach ($keys as $keyPart){
            if (!isset($value[$keyPart])){
                throw new \Exception("Config key not found: $key");
            }
            $value = $value[$keyPart];
        }

        return $value;
    }

    /**
     * Returns the admin school configuration as an object.
     *
     * @return object Admin school configuration.
     * @throws \Exception if the configuration cannot be loaded.
     *
     * ### Example
     * 
     * ```php
     * $adminSchool = ConstantsLoader::getAdminSchool();
     * echo $adminSchool->name;
     * ```
     */
    public static function getAdminSchool(): object {
        self::loadJson();

        return json_decode(json_encode(self::$data['adminSchool']), false);
    }

    /**
     * Returns the AJAX API configuration as an object.
     *
     * @return object AJAX API configuration.
     * @throws \Exception if the configuration cannot be loaded.
     *
     * ### Example
     * 
     * ```php
     * $apiAjax = ConstantsLoader::getApiAjax();
     * echo $apiAjax->url;
     * ```
     */
    public static function getApiAjax(): object {
        if (empty(self::$data)){
            self::loadJson();
        }

        return json_decode(json_encode(self::$data['api']['ajaxApi']), false);
    }
}

// Exported constants object for convenient use elsewhere in PHP.
// mostly doesn't work. sometimes does. PHPs way of handling global vars like this SUCKS.
$consts = (object)[
    'adminSchool' => ConstantsLoader::getAdminSchool(),
    'apiAjax' => ConstantsLoader::getApiAjax()
    // more in the future will be added as the consts file will be expanded
];