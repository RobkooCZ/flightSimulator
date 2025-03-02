#ifndef PHYSICS_H
#define PHYSICS_H

#define GRAVITY 9.81f
#define PI 3.14159265358979323846f
#define C_D0 0.02f // estimation of the zero lift drag for a jet fighter
#define OEF 0.8f // Oswald Efficiency Factor (~0.8 for a jet)

extern const int PHYSICS_DEBUG;

#include "aircraftData.h"
#include "controls.h"
#include "aircraft.h"

/**
 * @file physics.h
 * @brief This file contains the physics calculations and functions for the flight simulator.
 */

/**
 * @brief Structure to hold altitude and air density pair.
 */
typedef struct {
    float altitude;    ///< Altitude in meters
    float airDensity;  ///< Air density at that altitude (kg/m^3)
} AltitudeAirDensity;

/**
 * @brief Structure for the Longitudinal Axis Vector (L).
 */
typedef struct {
    float lx; ///< X component of the longitudinal axis vector
    float ly; ///< Y component of the longitudinal axis vector
    float lz; ///< Z component of the longitudinal axis vector
} LAV;

/**
 * @brief 3D Vector structure.
 */
typedef struct {
    float x; ///< X component of the vector
    float y; ///< Y component of the vector
    float z; ///< Z component of the vector
} Vector3;

/**
 * @brief Orientation structure.
 */
typedef struct {
    float yaw;   ///< Yaw angle in degrees
    float pitch; ///< Pitch angle in degrees
    float roll;  ///< Roll angle in degrees
} Orientation;

/**
 * @brief Physics data structure.
 * 
 * This structure holds all the physics-related data for the aircraft for caching to avoid redundant computations.
 */
typedef struct {
    // Atmosphere-related values (computed per frame)
    float tropopauseAltitude;
    float airDensity;
    float temperatureKelvin;
    float speedOfSound;
    float pressure;
    float flightPathAngle;

    // Lift and drag coefficients
    float liftCoefficient;
    float aspectRatio;
    float dragCoefficient;
    float parasiticDrag;
    float inducedDrag;
    float totalDrag;
    float dragDivergence;

    // Aircraft state data
    float thrust;
    float trueAirspeed; // TAS
    float machNumber;
    float angleOfAttack;

    // Vector-related computations
    Vector3 windVector;
    Vector3 upVector;
    Vector3 rightWingDirection;
    Vector3 liftAxisVector;
    Vector3 liftForce;
    Vector3 dragForce;

    // Orientation
    float pitchDegrees;
    float yawDegrees;
    float rollDegrees;

    // speed
    float velocityMagnitude;

    // Last simulation time 
    float lastSimulationTime;
} PhysicsData;

extern PhysicsData globalPhysicsData;

/*
    #########################################################
    #                                                       #
    #                      AIR DENSITY                      #
    #                                                       #
    #########################################################
*/

/**
 * @brief Get the altitude of the tropopause.
 * 
 * @return The altitude of the tropopause in meters.
 */
float getTropopause(void);

/**
 * @brief Get the air density at a given altitude.
 * 
 * @param altitude The altitude in meters.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The air density at the given altitude in kg/m^3.
 */
float getAirDensity(float altitude, PhysicsData *physicsData);

/**
 * @brief Convert degrees to radians.
 * 
 * @param degrees The angle in degrees.
 * @return The angle in radians.
 */
float convertDegToRadians(float degrees);

/*
    #########################################################
    #                                                       #
    #               AoA COMPUTING FUNCTIONS                 #
    #                                                       #
    #########################################################
*/

/**
 * @brief Calculate the Longitudinal Axis Vector (LAV) for the aircraft.
 * 
 * @param aircraft Pointer to the AircraftState structure.
 * @return The Longitudinal Axis Vector (LAV).
 */
LAV calculateLAV(AircraftState *aircraft);

/**
 * @brief Calculate the magnitude of a 3D vector.
 * 
 * @param x X component of the vector.
 * @param y Y component of the vector.
 * @param z Z component of the vector.
 * @return The magnitude of the vector.
 */
float calculateMagnitude(float x, float y, float z);

/**
 * @brief Calculate the dot product of the Longitudinal Axis Vector (LAV) and another vector.
 * 
 * @param lav The Longitudinal Axis Vector (LAV).
 * @param x X component of the other vector.
 * @param y Y component of the other vector.
 * @param z Z component of the other vector.
 * @return The dot product of the two vectors.
 */
float calculateDotProduct(LAV lav, float x, float y, float z);

/**
 * @brief Calculate the Angle of Attack (AoA) for the aircraft.
 * 
 * @param aircraft Pointer to the AircraftState structure.
 * @return The Angle of Attack (AoA) in degrees.
 */
float calculateAoA(AircraftState *aircraft);

/*
    #########################################################
    #                                                       #
    #                    LIFT CALCULATION                   #
    #                                                       #
    #########################################################
*/

/**
 * @brief Get the flight path angle for the aircraft.
 * 
 * @param aircraft Pointer to the AircraftState structure.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The flight path angle in degrees.
 */
float getFlightPathAngle(AircraftState *aircraft, PhysicsData *physicsData);

/**
 * @brief Calculate the lift coefficient for the aircraft.
 * 
 * @param mass The mass of the aircraft in kg.
 * @param aircraft Pointer to the AircraftState structure.
 * @param wingArea The wing area of the aircraft in m^2.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The lift coefficient.
 */
float calculateLiftCoefficient(float mass, AircraftState *aircraft, float wingArea, PhysicsData *physicsData);

/**
 * @brief Calculate the lift force for the aircraft.
 * 
 * @param wingArea The wing area of the aircraft in m^2.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The lift force in Newtons.
 */
float calculateLift(float wingArea, PhysicsData *physicsData);

/*
    #########################################################
    #                                                       #
    #               LIFT DIRECTION FUNCTIONS                #
    #                                                       #
    #########################################################
*/

/**
 * @brief Get the unit vector for the aircraft.
 * 
 * @param aircraft Pointer to the AircraftState structure.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The unit vector.
 */
Vector3 getUnitVector(AircraftState *aircraft, PhysicsData *physicsData);

/**
 * @brief Rotate a vector around another vector by a given angle.
 * 
 * @param V The vector to be rotated.
 * @param K The vector to rotate around.
 * @param theta The angle in degrees.
 * @return The rotated vector.
 */
Vector3 rotateAroundVector(Vector3 V, Vector3 K, float theta);

/**
 * @brief Get the direction vector of the right wing of the aircraft.
 * 
 * @param aircraft Pointer to the AircraftState structure.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The direction vector of the right wing.
 */
Vector3 getRightWingDirection(AircraftState *aircraft, PhysicsData *physicsData);

/**
 * @brief Get the lift axis vector for the aircraft.
 * 
 * @param wingRight The direction vector of the right wing.
 * @param unitVector The unit vector of the aircraft.
 * @return The lift axis vector.
 */
Vector3 getLiftAxisVector(Vector3 wingRight, Vector3 unitVector);

/**
 * @brief Compute the components of the lift force for the aircraft.
 * 
 * @param aircraft Pointer to the AircraftState structure.
 * @param wingArea The wing area of the aircraft in m^2.
 * @param coefficientLift The lift coefficient.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The components of the lift force as a Vector3.
 */
Vector3 computeLiftForceComponents(AircraftState *aircraft, float wingArea, float coefficientLift, PhysicsData *physicsData);

/*
    #########################################################
    #                                                       #
    #               DRAG CALCULATION FUNCTIONS              #
    #                                                       #
    #########################################################
*/

/**
 * @brief Calculate the aspect ratio of the aircraft's wings.
 * 
 * @param wingspan The wingspan of the aircraft in meters.
 * @param wingArea The wing area of the aircraft in m^2.
 * @return The aspect ratio.
 */
float calculateAspectRatio(float wingspan, float wingArea);

/**
 * @brief Calculate the drag coefficient for the aircraft.
 * 
 * @param speed The speed of the aircraft in m/s.
 * @param maxSpeed The maximum speed of the aircraft in m/s.
 * @param C_d0 The zero-lift drag coefficient.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The drag coefficient.
 */
float calculateDragCoefficient(float speed, float maxSpeed, float C_d0, PhysicsData *physicsData);

/**
 * @brief Calculate the parasitic drag for the aircraft.
 * 
 * @param C_d The drag coefficient.
 * @param airDensity The air density in kg/m^3.
 * @param speed The speed of the aircraft in m/s.
 * @param wingArea The wing area of the aircraft in m^2.
 * @return The parasitic drag in Newtons.
 */
float calculateParasiticDrag(float C_d, float airDensity, float speed, float wingArea);

/**
 * @brief Calculate the induced drag for the aircraft.
 * 
 * @param liftCoefficient The lift coefficient.
 * @param aspectRatio The aspect ratio of the wings.
 * @param airDensity The air density in kg/m^3.
 * @param wingArea The wing area of the aircraft in m^2.
 * @param speed The speed of the aircraft in m/s.
 * @return The induced drag in Newtons.
 */
float calculateInducedDrag(float liftCoefficient, float aspectRatio, float airDensity, float wingArea, float speed);

/**
 * @brief Calculate the drag divergence around Mach for the aircraft.
 * 
 * @param speed The relative speed of the aircraft in m/s.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The drag divergence around Mach.
 */
float calculateDragDivergenceAroundMach(float speed, PhysicsData *physicsData);

/**
 * @brief Calculate the total drag for the aircraft.
 * 
 * @param parasiticDrag Pointer to store the parasitic drag.
 * @param inducedDrag Pointer to store the induced drag.
 * @param waveDrag Pointer to store the wave drag.
 * @param relativeSpeed Pointer to store the relative speed.
 * @param relativeVelocity Pointer to store the relative velocity.
 * @param aircraft Pointer to the AircraftState structure.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The total drag in Newtons.
 */
float calculateTotalDrag(float *parasiticDrag, float *inducedDrag, float *waveDrag, float *relativeSpeed, Vector3 *relativeVelocity, AircraftState *aircraft, PhysicsData *physicsData);

/*
    #########################################################
    #                                                       #
    #                   THRUST CALCULATION                  #
    #                                                       #
    #########################################################
*/

/**
 * @brief Calculate the thrust for the aircraft.
 * 
 * @param thrust The thrust value.
 * @param afterburnerThrust The afterburner thrust value.
 * @param percentControl The throttle control percentage.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The thrust in Newtons.
 */
float calculateThrust(int thrust, int afterburnerThrust, int percentControl, PhysicsData *physicsData);

/*
    #########################################################
    #                                                       #
    #                      ORIENTATION                      #
    #                                                       #
    #########################################################
*/

/**
 * @brief Calculate the new orientation of the aircraft.
 * 
 * @param deltaTime The time step in seconds.
 * @return The new orientation of the aircraft.
 */
Orientation calculateNewOrientation(float deltaTime);

/**
 * @brief Get the direction vector based on the orientation.
 * 
 * @param newOrientation The new orientation of the aircraft.
 * @return The direction vector.
 */
Vector3 getDirectionVector(Orientation newOrientation);

/**
 * @brief Update the velocity of the aircraft.
 * 
 * @param aircraft Pointer to the AircraftState structure.
 * @param deltaTime The time step in seconds.
 * @param data Pointer to the AircraftData structure.
 * @param physicsData Pointer to the PhysicsData structure.
 */
void updateVelocity(AircraftState *aircraft, float deltaTime, AircraftData *data, PhysicsData *physicsData);

/*
    #########################################################
    #                                                       #
    #                   TAS CALCULATION                     #
    #                                                       #
    #########################################################
*/

/**
 * @brief Get the temperature in Kelvin at a given altitude.
 * 
 * @param altitudeMeters The altitude in meters.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The temperature in Kelvin.
 */
float getTemperatureKelvin(float altitudeMeters, PhysicsData *physicsData);

/**
 * @brief Get the pressure at a given altitude.
 * 
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The pressure in Pascals.
 */
float getPressureAtAltitude(PhysicsData *physicsData);

/**
 * @brief Calculate the True Airspeed (TAS) for the aircraft.
 * 
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The True Airspeed (TAS) in m/s.
 */
float calculateTAS(PhysicsData *physicsData);

/*
    #########################################################
    #                                                       #
    #                   HELPER FUNCTIONS                    #
    #                                                       #
    #########################################################
*/

/**
 * @brief Calculate the cross product of two vectors.
 * 
 * @param a The first vector.
 * @param b The second vector.
 * @return The cross product as a Vector3.
 */
Vector3 vectorCross(Vector3 a, Vector3 b);

/**
 * @brief Get the up vector for the aircraft.
 * 
 * @param aircraft Pointer to the AircraftState structure.
 * @return The up vector.
 */
Vector3 getUpVector(AircraftState *aircraft);

/**
 * @brief Get the unit vector from a given vector.
 * 
 * @param vector The input vector.
 * @return The unit vector.
 */
Vector3 getUnitVectorFromVector(Vector3 vector);

/**
 * @brief Convert radians to degrees.
 * 
 * @param radians The angle in radians.
 * @return The angle in degrees.
 */
float convertRadiansToDeg(float radians);

/**
 * @brief Convert speed from km/h to m/s.
 * 
 * @param kmh The speed in km/h.
 * @return The speed in m/s.
 */
float convertKmhToMs(float kmh);

/**
 * @brief Convert speed from m/s to km/h.
 * 
 * @param ms The speed in m/s.
 * @return The speed in km/h.
 */
float convertMsToKmh(float ms);

/**
 * @brief Calculate the speed of sound at a given altitude.
 * 
 * @param altitude The altitude in meters.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The speed of sound in m/s.
 */
float calculateSpeedOfSound(float altitude, PhysicsData *physicsData);

/**
 * @brief Convert speed from m/s to Mach number.
 * 
 * @param ms The speed in m/s.
 * @param physicsData Pointer to the PhysicsData structure.
 * @return The Mach number.
 */
float convertMsToMach(float ms, PhysicsData *physicsData);

/**
 * @brief Interpolate air density between two altitudes.
 * 
 * @param lowerAlt The lower altitude in meters.
 * @param upperAlt The upper altitude in meters.
 * @param lowerDensity The air density at the lower altitude in kg/m^3.
 * @param upperDensity The air density at the upper altitude in kg/m^3.
 * @param targetAltitude The target altitude in meters.
 * @return The interpolated air density in kg/m^3.
 */
float interpolate(float lowerAlt, float upperAlt, float lowerDensity, float upperDensity, float targetAltitude);

/*
    #########################################################
    #                                                       #
    #                   PHYSICS UPDATE                      #
    #                                                       #
    #########################################################
*/

void updatePhysicsData(PhysicsData *physics, float altitude, AircraftState *aircraft, AircraftData *data, float simulationTime);

/**
 * @brief Computes the acceleration of the aircraft based on its current velocity and state.
 *
 * @param velocity The current velocity of the aircraft.
 * @param aircraft A pointer to the current state of the aircraft.
 * @param aircraftData A pointer to the data specific to the aircraft model.
 * @param physicsData A pointer to the physics data structure.
 * @return The computed acceleration as a Vector3.
 */
Vector3 computeAcceleration(Vector3 velocity, AircraftState *aircraft, AircraftData *aircraftData, PhysicsData *physicsData);

/**
 * @brief Updates the physics state of the aircraft.
 *
 * @param aircraft A pointer to the current state of the aircraft.
 * @param deltaTime The time step for the update.
 * @param simulationTime The current time in the simulation.
 * @param aircraftData A pointer to the data specific to the aircraft model.
 */
void updatePhysics(AircraftState *aircraft, float deltaTime, float simulationTime, AircraftData *aircraftData);
#endif // PHYSICS_H