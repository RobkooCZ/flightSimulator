#ifndef AIRCRAFT_H
#define AIRCRAFT_H

#include <stdbool.h>
#include "controls.h"

// Forward declaration of AircraftControls
typedef struct AircraftControls AircraftControls;

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

    // Fuel level (updated as fuel burns)// todo
    // float fuel;

    // controls
    AircraftControls controls;
} AircraftState;

void initAircraft(AircraftState *aircraft);
void updateAircraftState(AircraftState *aircraft, float deltaTime);

#endif // AIRCRAFT_H