#ifndef TEXT_RENDERER_H
#define TEXT_RENDERER_H

#define DEBUG_MODE 0 // uncomment this for debug mode (more printing)

#include "aircraft.h"
#include "aircraftData.h"

// Function to print the aircraft information
void printInfo(AircraftState *aircraft, AircraftData *aircraftData, float fps);

#endif // TEXT_RENDERER_H