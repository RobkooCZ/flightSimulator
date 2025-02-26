#include "controls.h"
#include "textRenderer.h"

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
    controls.throttle = 1.0;
    controls.afterburner = false;
    controls.yawRate = 0.5;
    controls.pitchRate = 0.5;
    controls.rollRate = 0.5;
    controls.yaw = 0;
    controls.pitch = 0;
    controls.roll = 0;
}

// Adjust values based on keypress
void adjustValues(SDL_Keycode key) {
    float sensitivity = 0.02f;   // Adjusts yaw, pitch, roll
    float throttleStep = 0.01f;  // Adjusts throttle increment/decrement

    switch (key) {
        case SDLK_w: controls.pitch -= sensitivity; break; // Pitch down
        case SDLK_s: controls.pitch += sensitivity; break; // Pitch up
        case SDLK_a: controls.yaw -= sensitivity; break; // Yaw left
        case SDLK_d: controls.yaw += sensitivity; break; // Yaw right
        case SDLK_q: controls.roll -= sensitivity; break; // Roll left
        case SDLK_e: controls.roll += sensitivity; break; // Roll right
        case SDLK_z: controls.throttle += throttleStep; break; // Increase throttle
        case SDLK_x: controls.throttle -= throttleStep; break; // Decrease throttle
        default: break;
    }
    // Clamp throttle and set afterburner flag
    if (controls.throttle < 0) controls.throttle = 0;
    if (controls.throttle > 1.01f) controls.throttle = 1.01f;
    float tolerance = 0.0001f;
    controls.afterburner = (fabs(controls.throttle - 1.01f) < tolerance);
}

// Start controls thread (called once when initializing)
void startControls(void) {
    controlsInit(); // Initialize the controls
}

// Function to handle SDL events and adjust controls accordingly
void handleKeyEvents(SDL_Event *event) {
    if (event->type == SDL_KEYDOWN) {
        adjustValues(event->key.keysym.sym); // Process key press

        if (event->key.keysym.sym == SDLK_p || event->key.keysym.sym == SDLK_c){
            toggleModes(*event); // Toggle modes
        }
    }
}

// Get the current controls
AircraftControls *getControls(void) {
    return &controls;
}