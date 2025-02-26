#include "controls.h"
#include "aircraft.h"
#include <math.h>
#include <stdlib.h>

void initAircraft(AircraftState *aircraft) {
    // Initialize the aircraft's state with zero values or default values
    aircraft->x = 0.0f;
    aircraft->y = 500.0f;  // Start at an altitude
    aircraft->z = 0.0f;
    aircraft->vx = 50.0f;  // Set an initial forward velocity
    aircraft->vy = 0.0f;
    aircraft->vz = 0.0f;
    aircraft->yaw = 0.0f;   // Facing straight
    aircraft->pitch = 0.0f; // Level flight
    aircraft->roll = 0.0f;  // Level flight
    aircraft->AoA = 0.0f;  // Angle of Attack
    aircraft->hasAfterburner = false; // default false

    // Initialize controls
    controlsInit();
}

void updateAircraftState(AircraftState *aircraft, float deltaTime) {
    // Example: Update position based on velocity
    aircraft->x += aircraft->vx * deltaTime;
    aircraft->y += aircraft->vy * deltaTime;
    aircraft->z += aircraft->vz * deltaTime;
}