#ifndef AIRCRAFT_H
#define AIRCRAFT_H

typedef struct {
    float x, y, z;    // Position in space
    float vx, vy, vz; // Velocity in each direction
    float yaw, pitch, roll; // Orientation (rotation)
} AircraftState;

void initAircraft(AircraftState *aircraft);
void updateAircraftState(AircraftState *aircraft, float deltaTime);

#endif // AIRCRAFT_H