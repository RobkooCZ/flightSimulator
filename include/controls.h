/**
 * @file controls.h
 * @brief Header file for aircraft control functions and structures.
 *
 * This file contains the definitions and function declarations for handling
 * aircraft controls in the flight simulator. It includes the structure for
 * representing aircraft control states and functions for initializing,
 * adjusting, and handling control inputs.
 *
 * The controls are mapped as follows:
 * - W, S: Elevator (Pitch)
 * - A, D: Yaw (Left, Right)
 * - Q, E: Roll (Tilting Left, Right)
 * - Z, X: Thrust Control
 */

#ifndef CONTROLS_H
#define CONTROLS_H

// Include necessary libraries
#include <stdbool.h>
#include <SDL2/SDL.h>

/**
 * @brief Forward declaration of AircraftState structure.
 */
typedef struct AircraftState AircraftState;


/**
 * @struct AircraftControls
 * @brief Structure to hold the state of aircraft controls.
 *
 * This structure contains fields representing the various control inputs
 * for an aircraft, including throttle, afterburner status, and control
 * surface positions and rates.
 */
typedef struct AircraftControls {
    float throttle;   /**< Throttle position (0.0 - 1.0, percentage of max thrust) */
    bool afterburner; /**< Afterburner status (true if active) */

    float yaw;        /**< Yaw control input */
    float pitch;      /**< Pitch control input */
    float roll;       /**< Roll control input */

    float yawRate;    /**< Rate of yaw change (degrees per second) */
    float pitchRate;  /**< Rate of pitch change (degrees per second) */
    float rollRate;   /**< Rate of roll change (degrees per second) */
} AircraftControls;

/**
 * @brief Initialize the control system.
 *
 * This function sets up the necessary state and resources for handling
 * aircraft controls.
 */
void controlsInit(void);

/**
 * @brief Adjust control values based on key input.
 *
 * @param key The SDL_Keycode representing the key that was pressed.
 *
 * This function adjusts the control values based on the provided key input.
 */
void adjustValues(SDL_Keycode key);

/**
 * @brief Start the control system.
 *
 * This function begins the control system, enabling it to process inputs
 * and update control states.
 */
void startControls(void);

/**
 * @brief Handle key events for control input.
 *
 * @param event Pointer to the SDL_Event containing the key event information.
 *
 * This function processes key events and updates the control states accordingly.
 */
void handleKeyEvents(SDL_Event *event);

/**
 * @brief Get the current aircraft controls.
 *
 * @return Pointer to the AircraftControls structure containing the current control states.
 *
 * This function returns a pointer to the structure holding the current state
 * of the aircraft controls.
 */
AircraftControls *getControls(void);

#endif // CONTROLS_H