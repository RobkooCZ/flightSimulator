<?php

namespace WebDev\Functions;

use WebDev\Bootstrap;

Bootstrap::init();

if (!defined('STDOUT')){
    define('STDOUT', fopen('php://stdout', 'w'));
}

/**
 * Log level enumeration for categorizing log messages by severity.
 * 
 * This enum defines a hierarchy of log levels from least severe (TRACE)
 * to most severe (EMERGENCY). The integer values represent the relative
 * severity, with higher values indicating more severe issues.
 */
enum LogLevel: int {
    case NONE      = -1;
    case TRACE     = 0;
    case DEBUG     = 1;
    case INFO      = 2;
    case NOTICE    = 3;
    case SUCCESS   = 4;
    case WARNING   = 5;
    case FAILURE   = 6;
    case ERROR     = 7;
    case EXCEPTION = 8;
    case CRITICAL  = 9;
    case ALERT     = 10;
    case EMERGENCY = 11;

    /**
     * Converts a string name to the corresponding LogLevel enum case.
     *
     * @param string $name The name of the log level (e.g., "DEBUG", "INFO").
     * @return self|self::NONE The corresponding LogLevel enum case if a valid name was provided, otherwise returns `self::NONE`.
     */
    public static function fromName(string $name): self {
        foreach (self::cases() as $case){
            if ($case->name === $name){
                return $case;
            }
        }

        // default return NONE
        return self::NONE;
    }
}

/**
 * Terminal color codes for different log levels.
 * 
 * This enum defines ANSI escape sequences for colorizing log output
 * in the terminal. Each case corresponds to a different log level or
 * text formatting need.
 */
enum LogColours: string {
    case EMERGENCY = "\033[38;2;255;255;255;48;2;255;0;0m";
    case ALERT     = "\033[38;2;255;0;255m";
    case CRITICAL  = "\033[38;2;255;0;0m";
    case ERROR     = "\033[38;2;255;85;85m";
    case WARNING   = "\033[38;2;255;255;0m";
    case NOTICE    = "\033[38;2;255;215;0m";
    case INFO      = "\033[38;2;30;144;255m";
    case DEBUG     = "\033[38;2;0;255;255m";
    case TRACE     = "\033[38;2;128;128;128m";

    case RESET     = "\033[0m";
    
    case EXCEPTION = "\033[38;2;255;20;147m";

    case SUCCESS   = "\033[38;2;0;255;0m";
    case FAILURE   = "\033[38;2;255;69;0m";
}

/**
 * Text styling codes for terminal output.
 * 
 * This enum defines ANSI escape sequences for applying text styling
 * effects like bold, italic, and underline to terminal output.
 */
enum TextStyle: string {
    case BOLD      = "\033[1m";
    case ITALIC    = "\033[3m";
    case UNDERLINE = "\033[4m";
    case BLINK     = "\033[5m";
    case REVERSE   = "\033[7m";
    case RESET     = "\033[0m";
}

/**
 * Logging target destinations.
 * 
 * This enum defines where log messages can be sent, using bitwise flags
 * to allow combination of multiple targets.
 */
enum Loggers: int {
    case FILE = 1;
    case CMD  = 2;
    case ALL = self::FILE->value | self::CMD->value;
}

/**
 * Types of log messages.
 * 
 * This enum distinguishes between regular log messages and
 * exception-specific log messages, which require different formatting.
 */
enum LoggerType: string {
    case NORMAL = "NORMAL";
    case EXCEPTION = "EXCEPTION";
}

/**
 * Main logger class implementing the singleton pattern.
 * 
 * This class serves as the entry point for all logging operations,
 * managing both file and console logging through their respective
 * singleton instances.
 */
class Logger {
    /**
     * The singleton instance of the Logger class.
     *
     * @var Logger|null
     */
    private static ?Logger $l = null;
    
    /**
     * The FileLogger instance for writing logs to files.
     *
     * @var FileLogger|null
     */
    public ?FileLogger $fl = null;
    
    /**
     * The ConsoleLogger instance for writing logs to console.
     *
     * @var ConsoleLogger|null
     */
    public ?ConsoleLogger $cl = null;

    /**
     * @var string The minimum log level for logging messages to the console.
     * 
     * - `NONE`   => All messages are logged.
     * - `DEBUG`  => Messages below DEBUG level are not logged.
     * 
     * Refer to the `LogLevel` enum for the full list of log levels and their hierarchy.
     * 
     * This constant's value will be used if a `.env` entry for `MIN_LOG_LEVEL` isn't provided.
     */
    private const MIN_LOG_LEVEL = "NONE";

    /**
     * Prevents unserializing of this singleton class.
     *
     * @throws LogicException When attempting to unserialize the singleton.
     * @return never
     */
    public function __wakeup(): never {
        throw new LogicException(message: "Cannot unserialize singleton.", reason: "Would violate the singleton pattern.");
    }

    /**
     * Prevents cloning of this singleton class.
     *
     * @throws LogicException When attempting to clone the singleton.
     * @return void
     */
    public function __clone(): void {
        throw new LogicException(message: "Cannot clone singleton", reason: "Would violate the singleton pattern.");
    }

    /**
     * Private constructor to prevent direct instantiation.
     * Initializes the FileLogger and ConsoleLogger instances.
     *
     * @return void
     */
    private function __construct(){
        $this->fl = FileLogger::getInstance();
        $this->cl = ConsoleLogger::getInstance();
    }

    /**
     * Gets the singleton instance of the Logger class.
     *
     * @return Logger The singleton instance.
     */
    public static function getInstance(): Logger {
        if (self::$l === null){
            self::$l = new Logger();
        }
        return self::$l;
    }

    /**
     * Gets the minimum log level from environment settings or uses the default.
     *
     * @return string The minimum log level name.
     */
    public static function getMinLogLevel(): string {
        return $_ENV['MIN_LOG_LEVEL'] ?? self::MIN_LOG_LEVEL; // either return the .env entry, or the default consatnt in this class.
    }

    /**
     * Gets the current date and time formatted for log entries.
     *
     * @return string The formatted date and time string.
     */
    public static function dateAndTime(): string {
        $now = new \DateTime();
        $formatted = $now->format('jS F Y, H:i:s.u');
        return substr($formatted, 0, -3); // cuts off last 3 digits
    }

    /**
     * Internal method to log a message to the specified targets.
     *
     * @param string $msg The message to log.
     * @param LogLevel $ill The log level of the message.
     * @param LoggerType $type The type of log (normal or exception).
     * @param Loggers $target The target(s) to log to (file, console, or both).
     * @param int $line The line number where the log was called.
     * @param string $file The file where the log was called.
     * @return void
     */
    private function logMessage(string $msg, LogLevel $ill, LoggerType $type, Loggers $target = Loggers::ALL, int $line = __LINE__, string $file = __FILE__): void {
        if (($target->value & Loggers::CMD->value)){
            try {
                if ($this->cl === null){
                    $this->cl = ConsoleLogger::getInstance();
                }
                // call either the method to log a normal message or log an exception
                ($type->value === "NORMAL") ? $this->cl->cliLog($msg, $ill) : $this->cl->cliLogException($msg, $ill, $line, $file);
            }
            catch (\Throwable $e){
                // Fallback to direct output if ConsoleLogger fails
                fwrite(STDOUT, "[LOGGER ERROR] Failed to log message: $msg" . PHP_EOL);
            }
        }
        
        if (($target->value & Loggers::FILE->value)){
            try {
                if ($this->fl === null){
                    $this->fl = FileLogger::getInstance();
                }
                
                // Logging to file will be added in the future
            }
            catch (\Throwable $e){
                // Fallback to console if file logging fails
                fwrite(STDOUT, "[FILE LOGGER ERROR] Failed to log message: $msg" . PHP_EOL);
            }
        }
    }

    /**
     * Static method to log a message through the singleton instance.
     * 
     * This method checks the minimum log level before proceeding, to avoid
     * logging messages that are below the configured threshold.
     *
     * @param string $msg The message to log.
     * @param LogLevel $ill The log level of the message.
     * @param LoggerType $type The type of log (normal or exception).
     * @param Loggers $target The target(s) to log to (file, console, or both).
     * @param int $line The line number where the log was called.
     * @param string $file The file where the log was called.
     * @return void
     */
    public static function log(string $msg, LogLevel $ill, LoggerType $type, Loggers $target = Loggers::ALL, int $line = __LINE__, string $file = __FILE__): void {
        if ($ill->value < LogLevel::fromName(self::getMinLogLevel())->value) return; // don't log anything below the desired level
        
        self::getInstance()->logMessage($msg, $ill, $type, $target, $line, $file);
    }
}

/**
 * Utility class for retrieving color codes based on log levels.
 */
class LoggerColour {
    /**
     * Retrieves a corresponding LogColours enum case based on the provided log level name.
     * 
     * This method loops through all the cases of the `LogColours` enum and compares each case's
     * name with the provided `$name`. If a matching case is found, it returns that case.
     * If no matching case is found, it returns `null`.
     * 
     * @param string $name The name of the log level to find the corresponding color for.
     * 
     * @return LogColours|null The corresponding `LogColours` enum case or `null` if no match is found.
     */
    private static function fromName(string $name): ?LogColours {
        foreach (LogColours::cases() as $case){
            if ($case->name === $name){
                return $case;
            }
        }

        return null;
    }

    /**
     * Gets the color code associated with the provided log level.
     * 
     * This method calls `fromName()` to find the `LogColours` enum case that matches the
     * provided log level's name. If a match is found, it returns the color value of the matching case.
     * If no match is found (or `fromName()` returns `null`), it defaults to the `RESET` color to avoid
     * a `null` value in the output.
     * 
     * @param LogLevel $ill The log level for which to get the color.
     * 
     * @return string The color code string associated with the log level.
     *               Returns the reset color code if no corresponding color is found.
     */
    public static function getColour(LogLevel $ill): string {
        return self::fromName($ill->name)?->value ?? LogColours::RESET->value;
    }
}

/**
 * Console logger class for formatting and outputting logs to the terminal.
 * 
 * This class implements the singleton pattern and provides methods for
 * writing both normal and exception log messages to the console with
 * appropriate formatting and colors.
 */
class ConsoleLogger {
    /**
     * The singleton instance of the ConsoleLogger class.
     *
     * @var ConsoleLogger|null
     */
    private static ?ConsoleLogger $cl = null;

    /**
     * Prevents unserializing of this singleton class.
     *
     * @throws LogicException When attempting to unserialize the singleton.
     * @return never
     */
    public function __wakeup(): never {
        throw new LogicException(message: "Cannot unserialize singleton.", reason: "Would violate the singleton pattern.");
    }

    /**
     * Prevents cloning of this singleton class.
     *
     * @throws LogicException When attempting to clone the singleton.
     * @return void
     */
    public function __clone(): void {
        throw new LogicException(message: "Cannot clone singleton", reason: "Would violate the singleton pattern.");
    }

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @return void
     */
    private function __construct(){}

    /**
     * Formats exception names in log messages with appropriate colors.
     *
     * @param string $msg The original message containing exception names.
     * @param LogLevel $ill The log level for color selection.
     * @return string The formatted message with colored exception names.
     */
    private static function formatExceptionName(string $msg, LogLevel $ill): string {
        /*
            Matches: "[NULL]", "[FILE]"
            Doesn't match: "]broken]", "not name"
        */
        $pattern = '/\[[^\]]+\]/';

        // find all the brackets using the pattern above
        preg_match_all($pattern, $msg, $matches);
    
        // go through all the found brackets and colour them accordingly
        foreach ($matches[0] as $bracket){
            $coloredBracket = TextStyle::BOLD->value . LoggerColour::getColour(LogLevel::EXCEPTION) . $bracket . LogColours::RESET->value . LoggerColour::getColour($ill);
            $msg = str_replace($bracket, $coloredBracket, $msg);
        }

        /*
            Matches: "[NULL", "[FILE"
            Doesn't match: "]broken]", "not name"
        */
        $nameRegex = '/\[[A-Z_]+/';

        // add the log level text after the exception name 
        $msg = preg_replace_callback($nameRegex, fn(array $m) => $m[0] . " " . $ill->name, $msg);
    
        // return the coloured brackets
        return $msg;
    }

    /**
     * Formats titles in log messages with appropriate styling.
     *
     * @param string $msg The original message containing titles.
     * @param LogLevel $ill The log level for color selection.
     * @return string The formatted message with styled titles.
     */
    private static function formatTitles(string $msg, LogLevel $ill): string {
        /*
            Matches: "| Hello:", "| Example 2:"
            Doesn't match: "| No_match:"
        */
        $pattern = '/\| [\w ]+\:/';
        preg_match_all($pattern, $msg, $matches);

        /*
            Matches: "Hello:", "Example 2:"
            Doesn't match: " No match:"
        */
        $underlinePattern = '/[\w][\w ]+\:/';
    
        // Loop through the first level of $matches, which contains the found titles
        foreach($matches[0] as $match){
            preg_match($underlinePattern, $match, $textMatch);
            if (!empty($textMatch[0])){
                $underlinedText = TextStyle::RESET->value . TextStyle::ITALIC->value . $textMatch[0] . TextStyle::RESET->value . LoggerColour::getColour($ill);
                $msg = str_replace($textMatch[0], $underlinedText, $msg);
            }
        }
    
        // return the message with titles underlined
        return $msg;
    }

    /**
     * Adds newlines around pipes for better readability in log messages.
     *
     * @param string $msg The original message.
     * @return string The formatted message with added newlines.
     */
    private static function addNewlines(string $msg): string {
        // Add a newline before every pipe followed by a space, but only if it's not already preceded by a newline
        return preg_replace('/(?<!\n)\| /', "\n\t| ", $msg);
    }

    /**
     * Transforms an exception message with styling, colors, and formatting.
     *
     * @param string $msg The original exception message.
     * @param LogLevel $ill The log level for color selection.
     * @param int $line The line number where the exception was logged.
     * @param string $file The file where the exception was logged.
     * @return string The transformed and formatted exception message.
     */
    private static function transformExceptionMessage(string $msg, LogLevel $ill, int $line, string $file): string {
        // colour the exception name, such as [e_NULL]
        $newMsg = self::formatExceptionName($msg, $ill);
        
        // append line and file data from where the method was called
        $newMsg .= " | Line: " . $line . " | File: " . $file;

        // underline the titles for readability
        $newMsg = self::formatTitles($newMsg, $ill);

        // newline around pipes for readability
        $newMsg = self::addNewlines($newMsg);

        // return the transformed message
        return $newMsg;
    }

    /**
     * Gets the singleton instance of the ConsoleLogger class.
     *
     * @return ConsoleLogger The singleton instance.
     */
    public static function getInstance(): ConsoleLogger {
        if (self::$cl === null){
            self::$cl = new ConsoleLogger();
        }
        return self::$cl;
    }

    /**
     * Logs a message to the console with appropriate formatting.
     * Use for general logs; for exceptions, use `cliLogException` instead.
     *
     * @param string $msg The message to log.
     * @param LogLevel $ill The log level of the message.
     * @return void
     */
    public function cliLog(string $msg, LogLevel $ill): void {
        $colour = LoggerColour::getColour($ill);
        $reset = LogColours::RESET->value;
        fwrite(
            STDOUT,
            $colour . "[" . Logger::dateAndTime() . "] " . "[" . $ill->name . "] " . $msg . $reset . PHP_EOL
        );
    }

    /**
     * Logs an exception message to the console with special formatting.
     *
     * @param string $msg The exception message to log.
     * @param LogLevel $ill The log level of the message.
     * @param int $line The line number where the exception was logged.
     * @param string $file The file where the exception was logged.
     * @return void
     */
    public function cliLogException(
        string $msg,
        LogLevel $ill,
        int $line = __LINE__,
        string $file = __FILE__,
    ): void {
        $transformedMessage = self::transformExceptionMessage($msg, $ill, $line, $file);
        $this->cliLog($transformedMessage, $ill);
    }
}

/**
 * File logger class for writing logs to files.
 * 
 * This class implements the singleton pattern and provides a foundation
 * for file-based logging, though the actual implementation is pending.
 */
class FileLogger {
    /**
     * The singleton instance of the FileLogger class.
     *
     * @var FileLogger|null
     */
    private static ?FileLogger $fl = null;

    /**
     * Prevents unserializing of this singleton class.
     *
     * @throws LogicException When attempting to unserialize the singleton.
     * @return never
     */
    public function __wakeup(): never {
        throw new LogicException(message: "Cannot unserialize singleton.", reason: "Would violate the singleton pattern.");
    }

    /**
     * Prevents cloning of this singleton class.
     *
     * @throws LogicException When attempting to clone the singleton.
     * @return void
     */
    public function __clone(): void {
        throw new LogicException(message: "Cannot clone singleton", reason: "Would violate the singleton pattern.");
    }

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @return void
     */
    private function __construct(){}

    /**
     * Gets the singleton instance of the FileLogger class.
     *
     * @return FileLogger The singleton instance.
     */
    public static function getInstance(): FileLogger {
        if (self::$fl === null){
            self::$fl = new FileLogger();
        }
        return self::$fl;
    }

    /**
     * Logs a message to a file (to be implemented).
     *
     * @return void
     */
    public function fileLog(){ /* implemented in the future */ }

    /**
     * Logs an exception message to a file (to be implemented).
     *
     * @return void
     */
    public function fileExceptionlog(){ /* implemented in the future */ }
}