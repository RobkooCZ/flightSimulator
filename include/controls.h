#ifndef CONTROLS_H
#define CONTROLS_H

#include <stdbool.h>
#include <SDL2/SDL.h>

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

// ----- CONTROLS & ADJUSTEMENT -----
void controlsInit(void);
void adjustValues(SDL_Keycode key);
void startControls(void);
void handleKeyEvents(SDL_Event *event);
AircraftControls *getControls(void);

#endif // CONTROLS_H