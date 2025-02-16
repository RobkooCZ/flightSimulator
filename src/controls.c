#include "controls.h"
#include <stdio.h>
#include <stdlib.h>
#include <math.h>

static AircraftControls controls;  // Global controls struct

/*
    ########################################################
    #                                                      # 
    #                     INPUT SYSTEM                     #
    #                                                      #
    ########################################################
*/

#ifndef _WIN32  // Linux Input Handling
    void enableRawMode(void) {
        struct termios term;
        tcgetattr(STDIN_FILENO, &term);
        term.c_lflag &= (tcflag_t)~(ICANON | ECHO);
        tcsetattr(STDIN_FILENO, TCSANOW, &term);
    }

    void disableRawMode(void) {
        struct termios term;
        tcgetattr(STDIN_FILENO, &term);
        term.c_lflag |= (ICANON | ECHO);
        tcsetattr(STDIN_FILENO, TCSANOW, &term);
    }

    char getKeyPress(void) {
        char key;
        read(STDIN_FILENO, &key, 1);
        return key;
    }

    int kbhit(void) {
        struct termios oldt, newt;
        int ch;
        int oldf;

        tcgetattr(STDIN_FILENO, &oldt);
        newt = oldt;
        newt.c_lflag &= (tcflag_t)~(ICANON | ECHO);
        tcsetattr(STDIN_FILENO, TCSANOW, &newt);
        oldf = fcntl(STDIN_FILENO, F_GETFL, 0);
        fcntl(STDIN_FILENO, F_SETFL, oldf | O_NONBLOCK);

        ch = getchar();

        tcsetattr(STDIN_FILENO, TCSANOW, &oldt);
        fcntl(STDIN_FILENO, F_SETFL, oldf);

        if (ch != EOF) {
            ungetc(ch, stdin);
            return 1;
        }

        return 0;
    }
#endif

/*
    ########################################################
    #                                                      # 
    #               INPUT HANDLING THREAD                  #
    #                                                      #
    ########################################################
*/

#ifdef _WIN32
    DWORD WINAPI inputThread(LPVOID lpParam) {
        (void)lpParam; // unused parameter
#else
    void *inputThread(void *arg) {
        (void)arg; // unused parameter
#endif
    while (1) {
        #ifdef _WIN32
            if (_kbhit()) {
                char key = (char)_getch();
                adjustValues(key);
            }
            Sleep(10);
        #else
            if (kbhit()) {
                char key = (char)getchar();
                adjustValues(key);
            }
            
        #endif
    }
    
    return 0;
}

/*
    ########################################################
    #                                                      # 
    #                CONTROLS & ADJUSTMENT                 #
    #                                                      #
    ########################################################
*/

void controlsInit(void) {
    controls.throttle = 1.0;
    controls.afterburner = false;
    controls.yawRate = 0.5;
    controls.pitchRate = 0.5;
    controls.rollRate = 0.5;
    controls.yaw = 0;
    controls.pitch = 0;
    controls.roll = 0;
}

void adjustValues(char key) {
    float sensitivity = 0.02f;   // Adjusts yaw, pitch, roll
    float throttleStep = 0.01f;  // Adjusts throttle increment/decrement

    switch (key) {
        case 'w': controls.pitch -= sensitivity; break; // Pitch down
        case 's': controls.pitch += sensitivity; break; // Pitch up
        case 'a': controls.yaw -= sensitivity; break;   // Yaw left
        case 'd': controls.yaw += sensitivity; break;   // Yaw right
        case 'q': controls.roll -= sensitivity; break;  // Roll left
        case 'e': controls.roll += sensitivity; break;  // Roll right
        case 'z': controls.throttle += throttleStep; break; // Increase throttle
        case 'x': controls.throttle -= throttleStep; break; // Decrease throttle
        default: break;
    }

    // Clamp throttle and set afterburner flag
    if (controls.throttle < 0) controls.throttle = 0;
    if (controls.throttle > 1.01f) controls.throttle = 1.01f;
    float tolerance = 0.0001f;
    controls.afterburner = (fabs(controls.throttle - 1.01f) < tolerance);
}

void startControls(void) {
    controlsInit();
    
    #ifdef _WIN32
        CreateThread(NULL, 0, inputThread, NULL, 0, NULL);
    #else
        pthread_t thread;
        pthread_create(&thread, NULL, inputThread, NULL);
    #endif
}

AircraftControls *getControls(void) {
    return &controls;
}
