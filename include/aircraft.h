/**
 * @file aircraft.h
 * @brief Defines the AircraftState structure and functions to initialize and update the aircraft state.
 */

#ifndef AIRCRAFT_H
#define AIRCRAFT_H

// Include controls.h for the aircraft controls structure
#include "controls.h"

// Include stdbool for AircraftState boolean value
#include <stdbool.h>

// Forward declaration of AircraftControls
typedef struct AircraftControls AircraftControls;

/**
 * @struct AircraftState
 * @brief Represents the state of an aircraft.
 * 
 * This structure holds various parameters that define the current state of an aircraft,
 * including its position, velocity, orientation, thrust, and control inputs.
 * 
 * @var AircraftState::x
 * Position in the x-axis (meters).
 * 
 * @var AircraftState::y
 * Position in the y-axis (meters).
 * 
 * @var AircraftState::z
 * Position in the z-axis (meters).
 * 
 * @var AircraftState::vx
 * Velocity component in the x-axis (meters/second).
 * 
 * @var AircraftState::vy
 * Velocity component in the y-axis (meters/second).
 * 
 * @var AircraftState::vz
 * Velocity component in the z-axis (meters/second).
 * 
 * @var AircraftState::yaw
 * Yaw angle (left/right rotation) in radians.
 * 
 * @var AircraftState::pitch
 * Pitch angle (nose up/down) in radians.
 * 
 * @var AircraftState::roll
 * Roll angle (tilt left/right) in radians.
 * 
 * @var AircraftState::AoA
 * Angle of Attack.
 * 
 * @var AircraftState::thrust
 * Current thrust (Newtons).
 * 
 * @var AircraftState::hasAfterburner
 * Indicates if the aircraft has an afterburner.
 * 
 * @var AircraftState::controls
 * Control inputs for the aircraft.
 */
typedef struct AircraftState {
    // Position in 3D space
    float x, y, z; // m

    // Velocity components
    float vx, vy, vz; // m/s

    // Orientation (Euler angles)
    float yaw;   // Left/Right rotation (0-360 degrees) (in vars its in radians)
    float pitch; // Nose up/down (-90 to 90 degrees) (in vars its in radians)
    float roll;  // Tilt left/right (-180 to 180 degrees) (in vars its in radians)

    float AoA; // Angle of Attack

    // Angular velocity (for smooth control response)
    // float yawRate;
    // float pitchRate;
    // float rollRate;

    // Current thrust (computed from throttle input)
    float thrust; // N
    bool hasAfterburner;

    // Fuel level (updated as fuel burns) [TODO]
    // float fuel;

    // controls
    AircraftControls controls;
} AircraftState;

/**
 * @brief Initializes the aircraft state.
 * 
 * This function sets the initial values for the aircraft state.
 * 
 * @param aircraft Pointer to the AircraftState structure to initialize.
 */
void initAircraft(AircraftState *aircraft);

/**
 * @brief Updates the aircraft state based on the elapsed time.
 * 
 * This function updates the aircraft state parameters such as position, velocity, and orientation
 * based on the control inputs and the elapsed time.
 * 
 * @param aircraft Pointer to the AircraftState structure to update.
 * @param deltaTime Time elapsed since the last update (seconds).
 */
void updateAircraftState(AircraftState *aircraft, float deltaTime);

#endif // AIRCRAFT_H