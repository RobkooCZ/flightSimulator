/**
 * @file logger.h
 * @brief Logger utility for the flight simulator application.
 *
 * This header file defines the logging functionality for the flight simulator.
 * It provides an enumeration for log levels and a function to log messages.
 */
#ifndef LOGGER_H
#define LOGGER_H

/**
 * @enum LogLevel
 * @brief Defines the severity levels for logging.
 *
 * This enumeration represents the different levels of log messages.
 * - LOG_DEBUG: Debug-level messages, typically used for development and debugging.
 * - LOG_INFO: Informational messages, usually for general application events.
 * - LOG_WARNING: Warning messages, indicating potential issues or important notices.
 * - LOG_ERROR: Error messages, indicating serious problems that need attention.
 */
typedef enum {
    LOG_DEBUG,
    LOG_INFO,
    LOG_WARNING,
    LOG_ERROR
} LogLevel;

/**
 * @brief Logs a message with a specified log level.
 *
 * This function logs a message with the given log level and formatted string.
 * It supports variable arguments similar to printf.
 *
 * @param level The log level of the message.
 * @param fmt The format string for the message.
 * @param ... Additional arguments for the format string.
 */
void logMessage(LogLevel level, const char* fmt, ...);

#endif