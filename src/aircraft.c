#include "controls.h"
#include "aircraft.h"
#include <math.h>
#include <stdlib.h>

void initAircraft(AircraftState *aircraft) {
    // Initialize the aircraft's state with zero values or default values
    aircraft->x = 0.0f;
    aircraft->y = 1000.0f;  // Start at an altitude
    aircraft->z = 0.0f;
    aircraft->vx = 100.0f;  // Set an initial forward velocity (e.g., 100m/s)
    aircraft->vy = 0.0f;
    aircraft->vz = 0.0f;
    aircraft->yaw = 0.0f;   // Facing straight
    aircraft->pitch = 0.0f; // Level flight
    aircraft->roll = 0.0f;  // Level flight
    aircraft->hasAfterburner = false; // default false

    // Initialize controls
    controlsInit();
}

void updateAircraftState(AircraftState *aircraft, float deltaTime) {
    // Example: Update position based on velocity
    aircraft->x += aircraft->vx * deltaTime;
    aircraft->y += aircraft->vy * deltaTime;
    aircraft->z += aircraft->vz * deltaTime;

    // Prevent minor residual vy from causing oscillations.
    if (fabs(aircraft->vy) < 0.25f) {
        aircraft->vy = 0.0f;
    }
}