/**
 * @file weather.h
 * @brief Header file for weather-related functions in the flight simulator.
 *
 * This file contains the declaration of functions related to weather conditions,
 * such as wind vector calculations, which are used in the flight simulator.
 */

#ifndef WEATHER_H
#define WEATHER_H

// Include physics.h for Vector3 definition
#include "physics.h" 

/**
* @brief Calculates the wind vector at a given altitude and time.
*
* This function returns a Vector3 representing the wind direction and speed
* at a specified altitude and time. The wind vector is used in the physics
* calculations of the flight simulator to simulate realistic flight conditions.
*
* @param altitude The altitude at which to calculate the wind vector (in meters).
* @param time The time at which to calculate the wind vector (in seconds).
* @return A Vector3 representing the wind direction and speed.
*/
Vector3 getWindVector(float altitude, float time);

#endif // WEATHER_H