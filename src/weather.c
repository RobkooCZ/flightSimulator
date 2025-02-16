#include "weather.h"

#include <stdio.h>
#include <math.h>

// constants for base wind
static const float BASE_WIND_SPEED = 2.0f; // m/s at sea level
static const float ALTITUDE_FACTOR = 0.001f; // m/s increase per meter of altitude

// constants for turbluence
static const float TURBULENCE_AMPLITUDE = 2.0f; // m/s
static const float TURBULENCE_FREQUENCY = 0.5f; // radians per second

Vector3 getWindVector(float altitude, float time) {
    Vector3 wind;

    // Base wind that increases slightly with altitude, blowing in +x direction
    wind.x = BASE_WIND_SPEED + ALTITUDE_FACTOR * altitude;
    wind.y = 0.0f;  // Assume negligible vertical component
    wind.z = 0.0f;  // No base wind in z direction

    // Add turbulence component using a simple sine function
    float turbulence = TURBULENCE_AMPLITUDE * sinf(TURBULENCE_FREQUENCY * time);
    wind.x += turbulence;
    // Add a small turbulence component for z or y:
    wind.z += TURBULENCE_AMPLITUDE * cosf(TURBULENCE_FREQUENCY * time);

    return wind;
}