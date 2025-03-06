/**
 * @file aircraft.c
 * 
 * @brief This file contains functions to initialize and update the state of the aircraft.
 */

// Include header files
#include "aircraft.h"

// Include necessary libraries
#include <math.h>
#include <stdlib.h> 

void getEmptyMassAndMaxFuel(AircraftData *aircraftData, float *emptyMass, float *maxFuel){
    *emptyMass = (float)aircraftData->mass; // kg
    *maxFuel = (float)aircraftData->fuelCapacity; // kg
}

void initAircraft(AircraftState *aircraft, AircraftData *data) {
    // Initialize the aircraft's state with zero values or default values
    aircraft->x = 0.0f; // Set initial x position to 0
    aircraft->y = 500.0f;  // Start at an altitude of 500 units
    aircraft->z = 0.0f; // Set initial z position to 0
    aircraft->vx = 100.0f;  // Set an initial forward velocity of 50 units
    aircraft->vy = 0.0f; // Set initial y velocity to 0
    aircraft->vz = 0.0f; // Set initial z velocity to 0
    aircraft->yaw = 0.0f;   // Set initial yaw to 0 (facing straight)
    aircraft->pitch = 0.0f; // Set initial pitch to 0 (level flight)
    aircraft->roll = 0.0f;  // Set initial roll to 0 (level flight)
    aircraft->AoA = 0.0f;  // Set initial Angle of Attack to 0
    aircraft->hasAfterburner = false; // Set afterburner to false by default

    // fuel, mass
    getEmptyMassAndMaxFuel(data, &aircraft->currentMass, &aircraft->fuel);

    aircraft->currentMass += aircraft->fuel; // Add fuel to the current mass

    // Initialize controls
    controlsInit(); // Call the function to initialize controls
}

void updateAircraftState(AircraftState *aircraft, float deltaTime) {
    // Example: Update position based on velocity
    aircraft->x += aircraft->vx * deltaTime; // Update x position
    aircraft->y += aircraft->vy * deltaTime; // Update y position
    aircraft->z += aircraft->vz * deltaTime; // Update z position
}