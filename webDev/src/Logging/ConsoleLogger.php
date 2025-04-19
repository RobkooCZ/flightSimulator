<?php

declare(strict_types=1);

namespace WebDev\Logging;

use WebDev\Exception\LogicException;
use WebDev\Logging\Enum\LogColours;
use WebDev\Logging\Enum\LogLevel;
use WebDev\Logging\Enum\TextStyle;

if (!defined('STDOUT')){
    define('STDOUT', fopen('php://stdout', 'w'));
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