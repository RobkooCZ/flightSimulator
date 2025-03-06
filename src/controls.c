/**
 * @file controls.c
 * 
 * @brief This file contains functions to handle user input and update aircraft controls.
 */

// Include necessary header files
#include "controls.h" 
#include "2Drenderer.h"

// Include math library for floating point operations
#include <math.h> 

static AircraftControls controls;  // Global controls struct

/*
    ########################################################
    #                                                      # 
    #                     INPUT SYSTEM                     #
    #                                                      #
    ########################################################
*/

// Initialize controls
void controlsInit(void) {
    controls.throttle = 1.0;  // Set initial throttle to maximum
    controls.afterburner = false;  // Afterburner is initially off
    controls.yawRate = 0.5;  // Set initial yaw rate
    controls.pitchRate = 0.5;  // Set initial pitch rate
    controls.rollRate = 0.5;  // Set initial roll rate
    controls.yaw = 0;  // Set initial yaw to zero
    controls.pitch = 0;  // Set initial pitch to zero
    controls.roll = 0;  // Set initial roll to zero
}

// Adjust values based on keypress
void adjustValues(SDL_Keycode key) {
    float sensitivity = 0.02f;   // Sensitivity for yaw, pitch, and roll adjustments
    float throttleStep = 0.01f;  // Throttle increment/decrement step

    switch (key) {
        case SDLK_w: controls.pitch -= sensitivity; break; // Pitch down
        case SDLK_s: controls.pitch += sensitivity; break; // Pitch up
        case SDLK_a: controls.yaw -= sensitivity; break; // Yaw left
        case SDLK_d: controls.yaw += sensitivity; break; // Yaw right
        case SDLK_q: controls.roll -= sensitivity; break; // Roll left
        case SDLK_e: controls.roll += sensitivity; break; // Roll right
        case SDLK_z: controls.throttle += throttleStep; break; // Increase throttle
        case SDLK_x: controls.throttle -= throttleStep; break; // Decrease throttle
        default: break;  // Do nothing for other keys
    }
    // Clamp throttle and set afterburner flag
    if (controls.throttle < 0) controls.throttle = 0;  // Ensure throttle is not less than 0
    if (controls.throttle > 1.01f) controls.throttle = 1.01f;  // Ensure throttle is not more than 1.01
    float tolerance = 0.0001f;  // Tolerance for floating point comparison
    controls.afterburner = (fabsf(controls.throttle - 1.01f) < tolerance);  // Set afterburner if throttle is at maximum
}

// Start controls thread (called once when initializing)
void startControls(void) {
    controlsInit(); // Initialize the controls
}

// Function to handle SDL events and adjust controls accordingly
void handleKeyEvents(SDL_Event *event) {
    if (event->type == SDL_KEYDOWN) {  // Check if the event is a key press
        adjustValues(event->key.keysym.sym); // Process key press

        // Check for mode toggle keys
        if (event->key.keysym.sym == SDLK_p || event->key.keysym.sym == SDLK_c || event->key.keysym.sym == SDLK_m){
            toggleModes(*event); // Toggle modes
        }
    }
}

// Get the current controls
AircraftControls *getControls(void) {
    return &controls;  // Return the address of the controls struct
}