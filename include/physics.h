#ifndef PHYSICS_H
#define PHYSICS_H

#define GRAVITY 9.81f
#define PI 3.14159265358979323846f
#define C_D0 0.02f // estimation of the zero lift drag for a jet fighter
#define OEF 0.8f // Oswald Efficiency Factor (~0.8 for a jet)

#include "aircraftData.h"
#include "controls.h"
#include "aircraft.h"

// Define a structure to hold altitude and air density pair
typedef struct {
    float altitude;    // Altitude in meters
    float airDensity;  // Air density at that altitude (kg/m^3)
} AltitudeAirDensity;

// Structure for the Longitudinal Axis Vector (L)
typedef struct {
    float lx;
    float ly;
    float lz;
} LAV;

// 3D Vector structure
typedef struct {
    float x, y, z;
} Vector3;

// orientation structure
typedef struct {
    float yaw, pitch, roll;
} Orientation;

// air density
float getAirDensity(float altitude);

// AoA calculation functions
float convertDegToRadians(float degrees);
LAV calculateLAV(AircraftState *aircraft);
float calculateMagnitude(float x, float y, float z);
float calculateDotProduct(LAV lav, float x, float y, float z);
float calculateAoA(AircraftState *aircraft);

// Lift calculation functions 
float calculateLiftCoefficient(float AoA); // simplified coefficient calculation
float calculateLift(AircraftState *aircraft, float wingArea);
float calculateAy(float lift, float mass);

// LIft direction functions
Vector3 getUnitVector(AircraftState *aircraft);
Vector3 rotateAroundVector(Vector3 V, Vector3 K, float theta);
Vector3 getRightWingDirection(AircraftState *aircraft);
Vector3 getLiftAxisVector(Vector3 wingRight, Vector3 unitVector);
Vector3 computeLiftForceComponents(AircraftState *aircraft, float wingArea, float coefficientLift);

// Drag calculation functions
float calculateAspectRatio(float wingspan, float wingArea);
float calculateInducedDrag(float liftCoefficient, float aspectRatio);
float calculateTotalDragCoefficient(float inducedDrag);
float calculateDragForce(float dragCoefficient, float airDensity, AircraftState *aircraft, float wingArea);

// thrust calculation functions
float calculateThrust(int thrust, int afterburnerThrust, AircraftState *aircraft, float maxSpeed, int percentControl);

// Aircraft orientation functions
Orientation calculateNewOrientation(float deltaTime);
Vector3 getDirectionVector(Orientation newOrientation);
void updateVelocity(AircraftState *aircraft, float deltaTime);

// TAS calculation functions (temp, pressure)
float getTemperatureKelvin(float altitudeMeters);
float getPressureAtAltitude(float altitudeMeters);
float calculateTAS(AircraftState *aircraft);

// Helper functions
Vector3 vectorCross(Vector3 a, Vector3 b);
Vector3 getUpVector(AircraftState *aircraft);
float convertRadiansToDeg(float radians);
float convertKmhToMs(float kmh);
float convertMsToKmh(float ms);
float calculateSpeedOfSound(float altitude);
float convertMsToMach(float ms, float altitude);
float interpolate(float lowerAlt, float upperAlt, float lowerDensity, float upperDensity, float targetAltitude);

// physics update
void updatePhysics(AircraftState *aircraft, float deltaTime, AircraftData *aircraftData);

#endif // PHYSICS_H