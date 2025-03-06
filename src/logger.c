/**
 * @file logger.c
 * @brief Logger utility for the flight simulator application.
 * 
 */

// Include logger header file
#include "logger.h"

// Include standard libraries
#include <stdio.h>  
#include <stdarg.h>

// colors
#define ANSI_COLOR_RED     "\x1b[31m"
#define ANSI_COLOR_GREEN   "\x1b[32m"
#define ANSI_COLOR_YELLOW  "\x1b[33m"
#define ANSI_COLOR_CYAN    "\x1b[36m"

// reset escape code to stop color
#define ANSI_COLOR_RESET   "\x1b[0m"

// Function to log messages with different log levels
void logMessage(LogLevel level, const char* fmt, ...) {
    const char* levelStr = ""; // Initialize an empty string for the log level

    switch (level) { // Determine the log level string based on the log level
        case LOG_DEBUG:   levelStr = ANSI_COLOR_GREEN "[DEBUG]" ANSI_COLOR_RESET; break; // Debug level
        case LOG_INFO:    levelStr = ANSI_COLOR_CYAN "[INFO]" ANSI_COLOR_RESET; break;  // Info level
        case LOG_WARNING: levelStr = ANSI_COLOR_YELLOW "[WARNING]" ANSI_COLOR_RESET; break; // Warning level
        case LOG_ERROR:   levelStr = ANSI_COLOR_RED "[ERROR]" ANSI_COLOR_RESET; break; // Error level
        default: break; // Default case (do nothing)
    }
    
    printf("%s ", levelStr); // Print the log level string
    
    va_list args; // Declare a variable to hold the variable arguments
    va_start(args, fmt); // Initialize the variable argument list
    vprintf(fmt, args); // Print the formatted string with the variable arguments
    va_end(args); // Clean up the variable argument list
    
    printf("\n"); // Print a newline character
}