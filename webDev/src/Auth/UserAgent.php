<?php
/**
 * UserAgent Class File
 *
 * This file contains a class which has methods to parse, extract data and detect suspicious UA.
 *
 * @file UserAgent.php
 * @since 0.7.6
 * @package Auth
 * @version 0.7.6
 * @author Robkoo
 * @license TBD
 * @see Database, Logger, User
 */

declare(strict_types=1);

namespace WebDev\Auth;

// database class
use WebDev\Database\Database;

// custom exception
use WebDev\Exception\LogicException;

// logging
use WebDev\Logging\Enum\Loggers;
use WebDev\Logging\Enum\LoggerType;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Logger;

/**
 * Class UserAgent
 *
 * Contains methods for extracting and parsing User Agent data.
 *
 * @package Auth
 * @since 0.7.6
 * @see Database, Logger, User
 */
final class UserAgent {
    // static registry to hold all instances of UserAgent
    /**
     * Holds all instances of `UserAgent` that are TIED to the `User` class.
     *
     * @var array<int,UserAgent> $registry
     */
    private static array $registry = [];

    // unique user id which tells us which user this instance is tied to
    private int $uid;

    // data
    private string $raw;
    private string $browser;
    private string $engine;
    private string $os;
    private string $device;
    private string $architecture;

    // consts
    /**
     * @var int
     */
    private const MAX_UA_LENGTH = 200;

    public function __toString(): string {
        return "
            \tBrowser: {$this->browser}
            \tEngine: {$this->engine}
            \tOS: {$this->os}
            \tDevice: {$this->device}
            \tArchitecture: {$this->architecture}";
    }

    /**
     * Private constructor for the `UserAgent` class.
     *
     * @param ?int $uid Either the user id we will link the UserAgent instance to, or null if we don't wanna tie it.
     */
    private function __construct(?int $uid = null){
        Logger::log(
            "UserAgent::__construct called for UID: " . ($uid ?? 'null'),
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        if (!is_null($uid)){ // only set the uid if we had one provided
            $this->uid = $uid;
        }

        // call the private static function to get an associative array with parsed UA data
        /**
         * This associative array contains the parsed and safe UA data.
         * @var array<string,string> $data
         */
        $data = self::getUserAgentData();

        Logger::log(
            "Parsed UA data: " . json_encode($data),
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        // assign the parsed data to variables in this class
        $this->raw = $data['raw'];
        $this->browser = $data['browser'];
        $this->engine = $data['engine'];
        $this->os = $data['os'];
        $this->device = $data['device'];
        $this->architecture = $data['architecture'];

        // compare the instance's data and the db data
        if (!$this->compareUADb()){ // new UA found
            Logger::log("User with ID {$this->uid} has a new UA:" . $this, LogLevel::INFO, LoggerType::NORMAL, Loggers::CMD);
            $this->insertUAIntoDb(); // insert the data into the database
        }
    }

    /**
     * This method returns an UserAgent instance based on the passed id.
     *
     * @param int $uid The user id
     * @return UserAgent The instance
     */
    public static function for(int $uid): UserAgent {
        Logger::log(
            "UserAgent::for called for UID: $uid",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        // if the instance doesn't already exist, create it
        if (!isset(self::$registry[$uid])){
            Logger::log(
                "Creating new UserAgent instance for UID: $uid",
                LogLevel::INFO,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            self::$registry[$uid] = new self($uid);
        }
        else {
            Logger::log(
                "Returning cached UserAgent instance for UID: $uid",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        // return the current instance
        return self::$registry[$uid];
    }

    /**
     * This method tries to detect a bot based on the provided `$ua` string.
     *
     * @param string $ua The raw UA data.
     * @return array<string,bool|array<int,string>> Associative array that contains the name of the bot if found and a flag.
     */
    public static function detectBot(string $ua): array {
        Logger::log(
            "UserAgent::detectBot called with UA: $ua",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        // check common keywords to determine a bot
        $botNames = [];
        $isBot = false;

        // List of common bots to detect
        $botPatterns = '/(bot|crawler|spider|slurp|bingpreview|googlebot|duckduckbot|yandex|facebookexternalhit|baidu|slurp|twitterbot|applebot|feedburner|botify)/i';

        if (preg_match_all($botPatterns, $ua, $matches)){
            $botNames = $matches[0]; // all the found bots
            $isBot = true;
            Logger::log(
                "Bot detected in UA: $ua | Bots: " . implode(', ', $botNames),
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "No bot detected in UA: $ua",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return [
            'isBot' => $isBot,
            'botNames' => $botNames
        ];
    }

    /**
     * This method tries to detect a script based on the provided `$ua` string.
     *
     * @param string $ua
     * @return array<string,bool|array<int,string>>
     */
    public static function detectScript(string $ua): array {
        Logger::log(
            "UserAgent::detectScript called with UA: $ua",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        $scriptNames = [];
        $isScript = false;
        $scriptPattern = '/(curl|wget|httpie|python-requests|axios|PostmanRuntime|Go-http-client)/i';

        if (preg_match_all($scriptPattern, $ua, $matches)){
            $scriptNames = $matches[0]; // all the matches
            $isScript = true;
            Logger::log(
                "Script detected in UA: $ua | Scripts: " . implode(', ', $scriptNames),
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            Logger::log(
                "No script detected in UA: $ua",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return [
            'isScript' => $isScript,
            'scriptNames' => $scriptNames
        ];
    }
    
    /**
     * This function parses the provided raw UA data and returns an associative array with crucial data for further processing.
     *
     * @param string $rawUaData The raw User Agent data extracted using `$_SERVER['HTTP_USER_AGENT']`.
     * @return array<string,bool|string|array<string,bool|array<int,string>>> An associative array that either contains strings (such as the OS name) or a boolean flag (such as success) or an associative array about the script and bot.
     */
    private static function parseUserAgentData(string $rawUaData): array {
        Logger::log(
            "UserAgent::parseUserAgentData called with UA: $rawUaData",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );
        $suspicious = false;
        $success = !$suspicious;

        $violations = [];

        // first check the length of the raw data before proceeding
        if (strlen($rawUaData) > self::MAX_UA_LENGTH){
            $suspicious = true;
            $success = false;
            $violations[] = "User Agent data too long: " . strlen($rawUaData) . " > " . self::MAX_UA_LENGTH;
        }

        // then check if it isnt empty
        if (empty($rawUaData)){
            $suspicious = true;
            $success = false;
            $violations[] = "User agent data is empty.";
        }
        
        // early return to prevent pointless execution (=> waste of CPU) of code below
        if (!empty($violations)){
            Logger::log(
                "UA parse violations: " . implode(' | ', $violations),
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            return [
                'success' => $success,
                'suspicious' => $suspicious,
                'violations' => $violations
            ];
        }

        /**
         * @var string
         */
        $os = $browser = $device = $engine  = $architecture = 'Unknown';

        // detect the user browser and engine
        if (preg_match('/(Chrome|Firefox|Safari|MSIE|Edge|Opera)\/(\d+\.\d+)/', $rawUaData, $matches)){
            $browser = $matches[1] . ' ' . $matches[2];
            $engine = match($matches[1]){
                'Chrome', 'Edge', 'Opera' => 'Blink',
                'Firefox' => 'Gecko',
                'Safari' => 'WebKit',
                'MSIE' => 'Trident',
                default => 'Unknown',
            };
            Logger::log(
                "Detected browser: $browser, engine: $engine",
                LogLevel::DEBUG,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }
        else {
            $suspicious = true;
            Logger::log(
                "Suspicious UA: Could not detect browser/engine in UA: $rawUaData",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        // detect the OS of the user
        if (preg_match('/Windows NT (\d+\.\d+)/', $rawUaData, $matches)){
            $os = 'Windows ' . $matches[1];
        }
        elseif (preg_match('/Mac OS X (\d+[_\.]\d+)/', $rawUaData, $matches)){
            $os = 'Mac OS X ' . $matches[1];
        }
        elseif (preg_match('/Linux/', $rawUaData)){
            $os = 'Linux';
        }
        elseif (preg_match('/Android/', $rawUaData)){
            $os = 'Android';
        }
        elseif (preg_match('/iPhone|iPad/', $rawUaData)){
            $os = 'iOS';
        }

        // detect the device
        $device = preg_match('/Mobile/', $rawUaData) ? 'Mobile' : (preg_match('/Tablet/', $rawUaData) ? 'Tablet' : 'Desktop');

        // find the architecture
        if (preg_match('/(x86_64|Win64|WOW64|amd64)/i', $rawUaData)){
            $architecture = '64-bit';
        }
        elseif (preg_match('/(i386|i686|x86)/i', $rawUaData)){
            $architecture = '32-bit';
        }
        elseif (preg_match('/(arm|aarch64)/i', $rawUaData)){
            $architecture = 'ARM';
        }

        // attempt to detect a bot and save the results into variables
        $botData = self::detectBot($rawUaData);
        $isBot = $botData['isBot'];
        $botNames = $botData['botNames'];

        // attempt to detect a script and save the results into variables
        $scriptData = self::detectScript($rawUaData);
        $isScript = $scriptData['isScript'];
        $scriptNames = $scriptData['scriptNames'];

        if (($isScript || $isBot) && !$suspicious){
            $suspicious = true;
            Logger::log(
                "UA flagged as suspicious due to script/bot detection.",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        if ($isScript){
            Logger::log(
                "Suspicious activity detected: User Agent indicates a script. Script names: " . implode(', ', $scriptNames),
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        if ($isBot){
            Logger::log(
                "Suspicious activity detected: User Agent indicates a bot. Bot names: " . implode(', ', $botNames),
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        return [
            'success' => $success,
            'suspicious' => $suspicious,
            'browser' => $browser,
            'engine' => $engine,
            'os' => $os,
            'device' => $device,
            'script' => [
                'isScript' => $isScript,
                'scriptNames' => $scriptNames
            ],
            'bot' => [
                'isBot' => $isBot,
                'botNames' => $botNames
            ],
            'architecture' => $architecture
        ];
    }

   /**
    * This function extracts raw UA data using `$_SERVER['HTTP_USER_AGENT']` and then calls a private static method in this class to parse it. 
    * The method initially checks whether the raw data doesn't exceed a set limit or if it isn't empty. Both cases are suspicious and are handled accordingly. After getting the parse results, it checks for flags, bot, and script suspicion and handles those cases accordingly.
    *
    * *Note: UA stands for User Agent.*
    *
    * @return array<string,string>
    * @throws LogicException If the UA is empty.
    */
    private static function getUserAgentData(): array {
        $rawUserAgentData = $_SERVER['HTTP_USER_AGENT'] ?? '';
        Logger::log(
            "UserAgent::getUserAgentData called. Raw UA: $rawUserAgentData",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        $dataArray = self::parseUserAgentData($rawUserAgentData);

        if (!$dataArray['success']){
            Logger::log(
                "UserAgent::getUserAgentData failed: UA is empty or invalid.",
                LogLevel::FAILURE,
                LoggerType::NORMAL,
                Loggers::CMD
            );
            throw new LogicException(
                "UA is empty.",
                422, // unprocessable entity
                "UA being empty is suspicious."
            );
        }

        if ($dataArray['suspicious']){
            Logger::log(
                "UserAgent::getUserAgentData: UA flagged as suspicious.",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        if ($dataArray['bot']['isBot']){
            Logger::log(
                "UserAgent::getUserAgentData: Bot detected.",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        if ($dataArray['script']['isScript']){
            Logger::log(
                "UserAgent::getUserAgentData: Script detected.",
                LogLevel::WARNING,
                LoggerType::NORMAL,
                Loggers::CMD
            );
        }

        Logger::log(
            "UserAgent::getUserAgentData returning parsed UA data.",
            LogLevel::DEBUG,
            LoggerType::NORMAL,
            Loggers::CMD
        );

        return [
            'raw' => $rawUserAgentData,
            'browser' => $dataArray['browser'],
            'engine' => $dataArray['engine'],
            'os' => $dataArray['os'],
            'device' => $dataArray['device'],
            'architecture' => $dataArray['architecture']
        ];
    }

    /**
     * Inserts UA data into the database.
     *
     * @return bool True on success, false on failure.
     * @throws DatabaseException If anything in the `execute()` method from the class `Database` goes wrong.
     */
    public function insertUAIntoDb(): bool {
        // get db instance
        /**
         * @var Database
         */
        $db = Database::getInstance();

        // get current timedate
        /**
         * @var string
         */
        $dateTime = date("Y-m-d H:i:s");

        // prepare query
        /**
         * @var string
         */
        $query = "INSERT INTO userAgents (userId, raw, browser, engine, os, device, architecture)
                  VALUES                (:userId,:raw,:browser,:engine,:os,:device,:architecture)";

        // execute query and capture the success flag into a var
        /**
         * Success flag from the execute method.
         * @var bool
         */
        $success = $db->execute(
            $query,
            [
                'userId' => $this->uid,
                'raw' => $this->raw,
                'browser' => $this->browser,
                'engine' => $this->engine,
                'os' => $this->os,
                'device' => $this->device,
                'architecture' => $this->architecture
            ]
        );

        if ($success){
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Compares the provided UA data with the UA data from the database.
     *
     * @return bool True if the UA's match, false if they don't
     */
    public function compareUADb(): bool {
        // get the database instance
        /**
         * @var Database
         */
        $db = Database::getInstance();

        /**
         * @var string
         */
        $query = "SELECT browser, engine, os, device, architecture
                  FROM userAgents
                  WHERE userId = :userId";
        
        // execute the query and capture the result
        /**
         * @var array<int,array<string,string>>
         */
        $dbRows = $db->query(
            $query,
            [
                'userId' => $this->uid
            ]
        ); // no `[0]` so we access all the rows

        /**
         * @var array<string,string>
         */
        $fields = [
            'browser' => $this->browser,
            'engine' => $this->engine,
            'os' => $this->os,
            'device' => $this->device,
            'architecture' => $this->architecture
        ];
    
        // Check each row for a full match
        foreach ($dbRows as $dbUA){
            $allMatch = true;
            foreach ($fields as $key => $value){
                if (!isset($dbUA[$key]) || $dbUA[$key] !== $value){ // if the value doesn't match
                    $allMatch = false; // this row doesnt match
                    break; // stop executing row
                }
            }
            if ($allMatch){ 
                return true; // Found a matching row
            }
        }
    
        return false; // No matching row found
    }
}