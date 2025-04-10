/**
 * @file weather.c
 * 
 * @brief This file contains functions to simulate weather effects on the aircraft.
 */

// Include the header file
#include "weather.h" 

// Include necessary libraries
#include <stdio.h> 
#include <math.h> 

// constants for base wind
static const float BASE_WIND_SPEED = 2.0f; // m/s at sea level, base wind speed
static const float ALTITUDE_FACTOR = 0.001f; // m/s increase per meter of altitude, factor for altitude effect on wind speed

// constants for turbulence
static const float TURBULENCE_AMPLITUDE = 2.0f; // m/s, amplitude of turbulence
static const float TURBULENCE_FREQUENCY = 0.5f; // radians per second, frequency of turbulence

// Function to get the wind vector based on altitude and time
Vector3 getWindVector(float altitude, float time) {
    Vector3 wind; // Declare a Vector3 to hold the wind components

    // Base wind that increases slightly with altitude, blowing in +x direction
    wind.x = BASE_WIND_SPEED + ALTITUDE_FACTOR * altitude; // Calculate base wind speed in x direction
    wind.y = 0.0f;  // Assume negligible vertical component, set y to 0
    wind.z = 0.0f;  // No base wind in z direction, set z to 0

    // Add turbulence component using a simple sine function
    float turbulence = TURBULENCE_AMPLITUDE * sinf(TURBULENCE_FREQUENCY * time); // Calculate turbulence component
    wind.x += turbulence; // Add turbulence to x component
    // Add a small turbulence component for z or y:
    wind.z += TURBULENCE_AMPLITUDE * cosf(TURBULENCE_FREQUENCY * time); // Add turbulence to z component

    return wind; // Return the wind vector
}