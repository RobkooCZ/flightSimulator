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

// Function to log messages with different log levels
void logMessage(LogLevel level, const char* fmt, ...) {
    const char* levelStr = ""; // Initialize an empty string for the log level
    switch (level) { // Determine the log level string based on the log level
        case LOG_DEBUG:   levelStr = "[DEBUG]"; break; // Debug level
        case LOG_INFO:    levelStr = "[INFO]"; break;  // Info level
        case LOG_WARNING: levelStr = "[WARNING]"; break; // Warning level
        case LOG_ERROR:   levelStr = "[ERROR]"; break; // Error level
        default: break; // Default case (do nothing)
    }
    
    printf("%s ", levelStr); // Print the log level string
    
    va_list args; // Declare a variable to hold the variable arguments
    va_start(args, fmt); // Initialize the variable argument list
    vprintf(fmt, args); // Print the formatted string with the variable arguments
    va_end(args); // Clean up the variable argument list
    
    printf("\n"); // Print a newline character
}