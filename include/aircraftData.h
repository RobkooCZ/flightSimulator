/**
 * @file aircraftData.h
 * @brief Header file for aircraft data structures and functions.
 *
 * This file contains the definition of the AircraftData structure and the 
 * declaration of the function to retrieve aircraft data by name.
 */

#ifndef AIRCRAFT_DATA_H
#define AIRCRAFT_DATA_H

#include <stdio.h>
#include "menu.h"

/**
 * @struct AircraftData
 * @brief Structure to hold data related to an aircraft.
 *
 * This structure contains various parameters that describe the physical 
 * and performance characteristics of an aircraft.
 *
 * @var AircraftData::name
 * Name of the aircraft.
 * @var AircraftData::mass
 * Mass of the aircraft in kilograms.
 * @var AircraftData::wingArea
 * Wing area of the aircraft in square meters.
 * @var AircraftData::wingSpan
 * Wing span of the aircraft in meters.
 * @var AircraftData::aspectRatio
 * Aspect ratio of the aircraft's wings.
 * @var AircraftData::sweepAngle
 * Sweep angle of the aircraft's wings in degrees.
 * @var AircraftData::thrust
 * Thrust produced by the aircraft's engines in Newtons.
 * @var AircraftData::afterburnerThrust
 * Thrust produced by the aircraft's engines with afterburner in Newtons.
 * @var AircraftData::maxSpeed
 * Maximum speed of the aircraft in kilometers per hour.
 * @var AircraftData::stallSpeed
 * Stall speed of the aircraft in kilometers per hour.
 * @var AircraftData::serviceCeiling
 * Maximum altitude the aircraft can reach in meters.
 * @var AircraftData::fuelCapacity
 * Fuel capacity of the aircraft in kilograms.
 * @var AircraftData::cd0
 * Zero-lift drag coefficient of the aircraft.
 * @var AircraftData::maxAoA
 * Maximum angle of attack of the aircraft in degrees.
 * @var AircraftData::fuelBurn
 * Fuel burn rate of the aircraft in kilograms per second.
 * @var AircraftData::afterburnerFuelBurn
 * Fuel burn rate of the aircraft with afterburner in kilograms per second.
 */
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

/**
 * @brief Retrieves aircraft data by name from a file.
 *
 * This function reads the aircraft data from the specified file and 
 * populates the provided AircraftData structure with the data for the 
 * specified aircraft name.
 *
 * @param filename The name of the file containing the aircraft data.
 * @param aircraftName The name of the aircraft to retrieve data for.
 * @param aircraftData Pointer to an AircraftData structure to be populated 
 *                     with the retrieved data.
 */
void getAircraftDataByName(const char *filename, const char *aircraftName, AircraftData *aircraftData);

#endif // AIRCRAFT_DATA_H