#ifndef AIRCRAFT_H
#define AIRCRAFT_H

#include <stdbool.h>
#include "controls.h"

// Forward declaration of AircraftControls
typedef struct AircraftControls AircraftControls;

typedef struct AircraftState {
    // Position in 3D space
    float x, y, z;

    // Velocity components
    float vx, vy, vz;

    // Orientation (Euler angles)
    float yaw;   // Left/Right rotation (0-360 degrees)
    float pitch; // Nose up/down (-90 to 90 degrees)
    float roll;  // Tilt left/right (-180 to 180 degrees)

    // Angular velocity (for smooth control response)
    // float yawRate;
    // float pitchRate;
    // float rollRate;

    // Current thrust (computed from throttle input)
    float thrust;
    bool hasAfterburner;

    // Fuel level (updated as fuel burns)// todo
    // float fuel;

    // controls
    AircraftControls controls;
} AircraftState;

void initAircraft(AircraftState *aircraft);
void updateAircraftState(AircraftState *aircraft, float deltaTime);

#endif // AIRCRAFT_H