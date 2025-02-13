#ifndef AIRCRAFT_DATA_H
#define AIRCRAFT_DATA_H

#include <stdio.h>
#include "menu.h"

typedef struct {
    char name[MAX_NAME_LENGTH];
    float mass; // kg
    float wingArea; // m2
    float wingSpan; // m
    float aspectRatio; 
    float sweepAngle; // deg
    int thrust; // N
    int afterburnerThrust; // N
    float maxSpeed; // kph
    float stallSpeed; // kph
    int serviceCeiling; // m
    int fuelCapacity; // kg
    float cd0;
    float maxAoA; // deg
    float fuelBurn; // kg/s
    float afterburnerFuelBurn; // kg/s
} AircraftData;

void getAircraftDataByName(const char *filename, const char *aircraftName, AircraftData *aircraftData);

#endif // AIRCRAFT_DATA_H