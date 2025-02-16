#ifndef CONTROLS_H
#define CONTROLS_H

#include <stdbool.h>

typedef struct AircraftState AircraftState;

/*
    ####################################################################
    #                             CONTROLS                             #
    #                      W,S - ELEVATOR (PITCH)                      #
    #                      A,D - YAW (LEFT,RIGHT)                      #
    #                 Q,E - ROLL (TILTING LEFT, RIGHT)                 #
    #                       Z,X - THRUST CONTROL                       #
    ####################################################################
*/

typedef struct AircraftControls {
    float throttle;   // 0.0 - 1.0 (percentage of max thrust)
    bool afterburner; // true if afterburner is active

    float yaw;
    float pitch;
    float roll;

    float yawRate;   // Rate of yaw change (deg/s)
    float pitchRate; // Rate of pitch change (deg/s)
    float rollRate;  // Rate of roll change (deg/s)
} AircraftControls;

#ifdef _WIN32
    #include <conio.h>
    #include <windows.h>
    #define CLEAR "cls"

    // ----- INPUT HANDLING THREAD -----
    DWORD WINAPI inputThread(LPVOID lpParam);
#else
    #include <termios.h>
    #include <fcntl.h>
    #include <unistd.h>
    #include <pthread.h>
    #define CLEAR "clear"

    // ----- INPUT SYSTEM -----
    void enableRawMode(void);
    void disableRawMode(void);
    char getKeyPress(void);

    // ----- INPUT HANDLING THREAD -----
    void *inputThread(void *arg);
    int kbhit(void);
#endif

// ----- CONTROLS & ADJUSTEMENT -----
void controlsInit(void);
void adjustValues(char key);
void startControls(void);
AircraftControls *getControls(void);

#endif // CONTROLS_H