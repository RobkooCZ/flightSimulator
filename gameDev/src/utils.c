/**
 * @file utils.c
 * 
 * @brief Utility functions for cross-platform compatibility
 * 
 * This file contains functions for sleeping for a specified number of microseconds or milliseconds, as well as getting the current time in microseconds.
 */

#define _POSIX_C_SOURCE 199309L  // Enables clock_gettime() on Linux/macOS

// Include header file
#include "utils.h"

// Standard libraries
#include <stdio.h>  
#include <stdlib.h>  

#ifdef _WIN32
    #include <windows.h>  // Windows-specific header for Sleep and performance counters
#else
    #include <time.h>  // POSIX time library for nanosleep and clock_gettime
    #include <unistd.h>  // POSIX API for miscellaneous functions
#endif

// Function to sleep for a specified number of microseconds
void sleepMicroseconds(long microseconds) {
    #ifdef _WIN32
        Sleep((DWORD)(microseconds / 1000));  // Convert microseconds to milliseconds and sleep
    #else
        struct timespec req = {0};  // Initialize timespec structure
        req.tv_sec = microseconds / 1000000;  // Convert microseconds to seconds
        req.tv_nsec = (microseconds % 1000000) * 1000;  // Convert remainder to nanoseconds
        
        nanosleep(&req, NULL);  // Sleep for the specified time
    #endif
}

// Function to sleep for a specified number of milliseconds
void sleepMilliseconds(int milliseconds) {
    #ifdef _WIN32
        Sleep((DWORD)milliseconds);  // Sleep for the specified milliseconds
    #else
        struct timespec ts;  // Initialize timespec structure
        ts.tv_sec = milliseconds / 1000;  // Convert milliseconds to seconds
        ts.tv_nsec = (milliseconds % 1000) * 1000000;  // Convert remainder to nanoseconds
        nanosleep(&ts, NULL);  // Sleep for the specified time
    #endif
}

// Function to get the current time in microseconds
long getTimeMicroseconds(void) {
    #ifdef _WIN32
        static LARGE_INTEGER frequency;  // Frequency of the performance counter
        LARGE_INTEGER now;  // Current value of the performance counter
        
        if (frequency.QuadPart == 0) {
            QueryPerformanceFrequency(&frequency);  // Get the frequency of the performance counter
        }
        
        QueryPerformanceCounter(&now);  // Get the current value of the performance counter
        return (long)((now.QuadPart * 1000000) / frequency.QuadPart);  // Convert to microseconds
    #else
        struct timespec now;  // Initialize timespec structure
        clock_gettime(CLOCK_MONOTONIC, &now);  // Get the current time with monotonic clock
        return now.tv_sec * 1000000 + now.tv_nsec / 1000;  // Convert to microseconds
    #endif
}