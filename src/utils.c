#define _POSIX_C_SOURCE 199309L  // Enables clock_gettime() on Linux/macOS
#include <stdio.h>
#include <stdlib.h>
#include "utils.h"

#ifdef _WIN32
    #include <windows.h>
#else
    #include <time.h>
    #include <unistd.h>
#endif

void sleepMicroseconds(long microseconds) {
    #ifdef _WIN32
        Sleep(microseconds / 1000);  // Convert to milliseconds
    #else
        struct timespec req = {0};
        req.tv_sec = microseconds / 1000000;  // Convert to seconds
        req.tv_nsec = (microseconds % 1000000) * 1000;  // Convert remainder to nanoseconds
        
        nanosleep(&req, NULL); 
    #endif
}

void sleepMilliseconds(int milliseconds) {
    #ifdef _WIN32
        Sleep(milliseconds);
    #else
        struct timespec ts;
        ts.tv_sec = milliseconds / 1000;
        ts.tv_nsec = (milliseconds % 1000) * 1000000;
        nanosleep(&ts, NULL);
    #endif
}

// Cross-platform high-resolution timer
long getTimeMicroseconds(void) {
    #ifdef _WIN32
        static LARGE_INTEGER frequency;
        LARGE_INTEGER now;
        
        if (frequency.QuadPart == 0) {
            QueryPerformanceFrequency(&frequency);
        }
        
        QueryPerformanceCounter(&now);
        return (now.QuadPart * 1000000) / frequency.QuadPart;
    #else
        struct timespec now;
        clock_gettime(CLOCK_MONOTONIC, &now);  // Works now with _POSIX_C_SOURCE
        return now.tv_sec * 1000000 + now.tv_nsec / 1000;
    #endif
}