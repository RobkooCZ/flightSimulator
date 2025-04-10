/**
 * @file physics.c
 * @brief This file contains functions and logic to handle the physics calculations for an aircraft simulation.
 *
 * The functions in this file are responsible for various physics-related computations such as:
 * - Converting speed from meters per second to Mach number based on altitude.
 * - Performing linear interpolation between two points.
 * - Updating the physics state of the aircraft, including calculating forces like gravity and lift.
 *
 */

// Include header files
#include "controls.h"
#include "weather.h"
#include "aircraftData.h"
#include "logger.h"

// Include libraries
#include <math.h>
#include <stdbool.h>
#include <stdio.h>

// Declare a global struct for the physics data
PhysicsData globalPhysicsData;

// global max fuel var
float maxFuelKgs = 0; // 0 base value

/* Atmospheric Constants */
const float airDensityAtSeaLevel = 1.225f;        // Sea-level air density in kg/m³
const float T0 = 288.15f;                         // Sea-level temperature in Kelvin
const float lapseRate = 6.5f;                     // Temperature lapse rate in K per km
const float rhoTop = 0.3639f;                     // Air density at the tropopause in kg/m³
const float T_trop = 216.65f;                     // Temperature at the tropopause in Kelvin

/* Thermodynamic Constants */
const float gammaHeats = 1.4f;                    // Ratio of specific heats for air
const float R = 287.05f;                          // Specific gas constant for dry air (J/(kg·K))
const float Kt = -0.0065f;                        // Temperature lapse rate in the troposphere

/* Speed of Sound Calculation */
const float baseSpeedOfSoundFactor = 340.29f;     // Factor to compute speed of sound below tropopause

/* Drag Coefficient Constants */
float alpha, kw, Md;

void fillConstants(AircraftData *data){
    alpha = data->alpha;
    kw = data->kw;
    Md = data->Md;
}

/* Pressure Calculation */
const float P0 = 101325.0f;                       // Sea-level atmospheric pressure in Pascals

// if values at any time go above these defined limits, raise a warning
#define SPEED_LIMIT 4096
#define ALT_LIMIT 32767
#define THROTTLE_LIMIT 1.01f

// if values go below these defined limits, raise a warning
#define BOTTOM_SPEED_LIMIT 0 // will be changed later when planes can go in reverse (such as viggen having reverse thrust)
#define BOTTOM_ALT_LIMIT 0
#define BOTTOM_THROTTLE_LIMIT 0

// define some made up coefficients to tweak physics to be more arcade-ish (M stands for made up)
#define M_DRAG_COEFFICIENT 0.8f;

// Define macros to check if values are within limits

#define CHECK_ALT_LIMIT(alt, fn) \
    if (alt < BOTTOM_ALT_LIMIT) { \
        logMessage(LOG_WARNING, "Altitude at function %s is below the defined limit. (%dm)", fn, BOTTOM_ALT_LIMIT); \
    }  \
    else if (alt > ALT_LIMIT) { \
        logMessage(LOG_WARNING, "Altitude at function %s is above the defined limit. (%dm)", fn, ALT_LIMIT); \
    }

#define CHECK_SPEED_LIMIT(speed, fn) \
    if (speed < BOTTOM_SPEED_LIMIT) { \
        logMessage(LOG_WARNING, "Speed at function %s is below the defined limit. (%dkm/h)", fn, BOTTOM_SPEED_LIMIT); \
    } \
    else if (speed > SPEED_LIMIT) { \
        logMessage(LOG_WARNING, "Speed at function %s is above the defined limit. (%dkm/h)", fn, SPEED_LIMIT); \
    }

#define CHECK_THROTTLE_LIMIT(throttle, fn) \
    if (throttle < BOTTOM_THROTTLE_LIMIT) { \
        logMessage(LOG_WARNING, "Throttle at function %s is below the defined limit. (%.2f)", fn, BOTTOM_THROTTLE_LIMIT); \
    } \
    else if (throttle > THROTTLE_LIMIT) { \
        logMessage(LOG_WARNING, "Throttle at function %s is above the defined limit. (%.2f)", fn, THROTTLE_LIMIT); \
    }

// Define a macro to check if a pointer is null.
#define CHECK_PTR(ptr, ptrName, fn, retVal)\
    do { \
        if (ptr == NULL) { \
            logMessage(LOG_ERROR, "Pointer %s in function %s is NULL.", ptrName, fn); \
            return retVal; \
        } \
    }while(0);

// Define a macro to check if a var is NaN.
#define CHECK_VAR(var, varName, fn, retVal)\
    do { \
        if (isnan(var)) { \
        logMessage(LOG_ERROR, "Variable %s in function %s is NaN.", varName, fn); \
        return retVal; \
        } \
    }while(0);

/*
    #########################################################
    #                                                       #
    #                      AIR DENSITY                      #
    #                                                       #
    #########################################################
*/

float getTropopause(void){
    float deltaT_isa = 0.0f; // for earth's atmosphere
    float h_top = 11000.0f + 1000.0f * (deltaT_isa / 6.5f); // calculate tropopause altitude

    return h_top; // return the altitude of the tropopause
}

float getAirDensity(float altitude, PhysicsData *physicsData){
    // Check for errors or warnings
    CHECK_ALT_LIMIT(altitude, "getAirDensity");
    CHECK_PTR(physicsData, "physicsData", "getAirDensity", 0.0f);

    float tropopause = physicsData->tropopauseAltitude; // the altitude of the tropopause

    // If the plane is below or at the tropopause
    if (altitude <= tropopause){
        const float exponent = -((GRAVITY)/(Kt * R)) - 1; // exponent for the density equation
        const float T = physicsData->temperatureKelvin; // temperature at the given altitude

        return airDensityAtSeaLevel * powf((T / T0), exponent); // calculate and return air density
    }
    // If the plane is above the tropopause
    else {
        return rhoTop * expf(-((GRAVITY)/(R * T_trop)) * (altitude - tropopause)); // calculate and return air density
    }
}

/*
    #########################################################
    #                                                       #
    #               AoA COMPUTING FUNCTIONS                 #
    #                                                       #
    #########################################################
*/

float convertDegToRadians(float degrees){
    // Check for errors or warnings
    CHECK_VAR(degrees, "degrees", "convertDegToRadians", 0.0f);

    return degrees * (PI / 180.0f); // convert degrees to radians
}

LAV calculateLAV(AircraftState *aircraft){
    LAV lav = {0.0f, 0.0f, 0.0f};
    
    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "calculateLAV", lav);

    float pitchRad = aircraft->pitch;
    float yawRad = aircraft->yaw;

    lav.lx = cosf(pitchRad) * cosf(yawRad);  // X component of LAV
    lav.ly = cosf(pitchRad) * sinf(yawRad);  // Y component of LAV
    lav.lz = sinf(pitchRad);                 // Z component of LAV
    return lav;
}

float calculateMagnitude(float x, float y, float z) {
    // check all vars to make sure none are NaN
    CHECK_VAR(x, "x", "calculateMagnitude", 0.0f);
    CHECK_VAR(y, "y", "calculateMagnitude", 0.0f);
    CHECK_VAR(z, "z", "calculateMagnitude", 0.0f);

    return sqrtf(x * x + y * y + z * z); // calculate the magnitude of a vector
}

float calculateDotProduct(LAV lav, float vx, float vy, float vz) {
    // check all vars to make sure none are NaN
    CHECK_VAR(vx, "vx", "calculateDotProduct", 0.0f);
    CHECK_VAR(vy, "vy", "calculateDotProduct", 0.0f);
    CHECK_VAR(vz, "vz", "calculateDotProduct", 0.0f);

    return vx * lav.lx + vy * lav.ly + vz * lav.lz; // calculate the dot product
}

float calculateAoA(AircraftState *aircraft) {
    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "calculateAoA", 0.0f);

    if (aircraft->vx < 1e-6f) { // Prevent division by zero
        if (aircraft->vz > 0.0f){
            return 90.0f;
        }
        else{
            return -90.0f;
        }
    }

    return atanf(aircraft->vz / aircraft->vx); // calculate the angle of attack
}

/*
    #########################################################
    #                                                       #
    #                    LIFT CALCULATION                   #
    #                                                       #
    #########################################################
*/

float getFlightPathAngle(AircraftState *aircraft, PhysicsData *physicsData){
    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "getFlightPathAngle", 0.0f);
    CHECK_PTR(physicsData, "physicsData", "getFlightPathAngle", 0.0f);

    float TAS = physicsData->trueAirspeed; // calculate true airspeed
    float ratio = aircraft->vy / TAS;

    if (ratio > 1.0f) {
        ratio = 1.0f;
    }
    else if (ratio < -1.0f) {
        ratio = -1.0f;
    }

    return asinf(ratio);  
}

float calculateLiftCoefficient(float mass, AircraftState *aircraft, float wingArea, PhysicsData *physicsData){
    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "calculateLiftCoefficient", 0.0f);
    CHECK_PTR(physicsData, "physicsData", "calculateLiftCoefficient", 0.0f);
    CHECK_VAR(mass, "mass", "calculateLiftCoefficient", 0.0f);
    CHECK_VAR(wingArea, "wingArea", "calculateLiftCoefficient", 0.0f);

    const float airDensity = physicsData->airDensity; // air density at current altitude
    float TAS = physicsData->trueAirspeed; // true airspeed
    float numerator = mass * GRAVITY; // calculate lift force numerator
    float denumerator = 0.5f * airDensity * powf(TAS, 2) * wingArea; // calculate lift force denominator

    if (fabsf(physicsData->yawDegrees) > 5.0f && fabsf(physicsData->pitchDegrees) > 0.1f){ // plane is turning
        denumerator *= cosf(physicsData->rollDegrees); // adjust for roll angle
        return numerator / denumerator; // calculate and return lift coefficient
    }
    else{ // plane isn't turning
        const float tolerance = 1e-6f;
        if (fabs(aircraft->vy) < tolerance){ // aircraft isn't climbing or descending
            return numerator / denumerator; // calculate and return lift coefficient
        }
        else { // aircraft is climbing or descending
            numerator *= cosf(physicsData->flightPathAngle); // adjust for flight path angle
            return numerator / denumerator; // calculate and return lift coefficient
        }
    }

    return 0.0f;
}

float calculateLift(float wingArea, PhysicsData *physicsData) {
    // check for errors or warnings
    CHECK_VAR(wingArea, "wingArea", "calculateLift", 0.0f);
    CHECK_PTR(physicsData, "physicsData", "calculateLift", 0.0f);

    float V = physicsData->trueAirspeed; // calculate true airspeed
    float rho = physicsData->airDensity;  // get air density at current altitude
    float S = wingArea; // wing area for the plane (in m^2)
    float C_L = physicsData->liftCoefficient; // lift coefficient for the plane

    return 0.5f * rho * V * V * S * C_L; // calculate and return lift force
}

/*
    #########################################################
    #                                                       #
    #               LIFT DIRECTION FUNCTIONS                #
    #                                                       #
    #########################################################
*/

Vector3 getUnitVector(AircraftState *aircraft, PhysicsData *physicsData){
    Vector3 vector = {0.0f, 0.0f, 0.0f};

    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "getUnitVector", vector);
    CHECK_PTR(physicsData, "physicsData", "getUnitVector", vector);

    float magnitude = physicsData->velocityMagnitude;
    if (magnitude < 0.0001f) { // Prevent division by zero
        return (Vector3){0.0f, 0.0f, 0.0f}; 
    }
    return (Vector3){ aircraft->vx / magnitude, 
                      aircraft->vy / magnitude, 
                      aircraft->vz / magnitude }; // return unit vector
}

Vector3 rotateAroundVector(Vector3 V, Vector3 K, float theta) {
    Vector3 rotated = {0.0f, 0.0f, 0.0f};

    // check if theta isnt NaN
    CHECK_VAR(theta, "theta", "rotateAroundVector", rotated);

    // Compute cross product (K × V)
    Vector3 cross = vectorCross(K, V);

    // Compute dot product (K ⋅ V)
    float dot = V.x * K.x + V.y * K.y + V.z * K.z;

    // Apply Rodrigues' rotation formula
    rotated.x = V.x * cosf(theta) + cross.x * sinf(theta) + K.x * dot * (1 - cosf(theta));
    rotated.y = V.y * cosf(theta) + cross.y * sinf(theta) + K.y * dot * (1 - cosf(theta));
    rotated.z = V.z * cosf(theta) + cross.z * sinf(theta) + K.z * dot * (1 - cosf(theta));

    return rotated; // return rotated vector
}

Vector3 getRightWingDirection(AircraftState *aircraft, PhysicsData *physicsData){
    Vector3 wingRight = {0.0f, 0.0f, 0.0f};

    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "getRightWingDirection", wingRight);
    CHECK_PTR(physicsData, "physicsData", "getRightWingDirection", wingRight);

    // Base right-wing direction (ignoring roll)
    wingRight.x = cosf(aircraft->yaw);
    wingRight.y = 0.0f;
    wingRight.z = -sinf(aircraft->yaw);

    // Get unit velocity vector (Vunit)
    Vector3 Vunit = getUnitVector(aircraft, physicsData);

    // Rotate wingRight around Vunit by roll angle
    return rotateAroundVector(wingRight, Vunit, aircraft->roll);
}

Vector3 getLiftAxisVector(Vector3 wingRight, Vector3 unitVector){
    // cross product
    Vector3 liftAxisVector = vectorCross(wingRight, unitVector);

    // normalize LAX
    float magnitude = calculateMagnitude(liftAxisVector.x, liftAxisVector.y, liftAxisVector.z);

    if (magnitude < 0.0001f) { // Prevent division by zero
        return (Vector3){0.0f, 0.0f, 0.0f}; 
    }

    liftAxisVector.x /= magnitude;
    liftAxisVector.y /= magnitude;
    liftAxisVector.z /= magnitude;

    return liftAxisVector; // return normalized lift axis vector
}

Vector3 computeLiftForceComponents(AircraftState *aircraft, float wingArea, float coefficientLift, PhysicsData *physicsData) {
    Vector3 returnVector = {0.0f, 0.0f, 0.0f};

    // check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "computeLiftForceComponents", returnVector);
    CHECK_PTR(physicsData, "physicsData", "computeLiftForceComponents", returnVector);
    CHECK_VAR(wingArea, "wingArea", "computeLiftForceComponents", returnVector);
    CHECK_VAR(coefficientLift, "coefficientLift", "computeLiftForceComponents", returnVector);
    
    // TAS
    float TAS = physicsData->trueAirspeed;

    // convert it into a vector
    Vector3 velocity = {
        TAS * cosf(aircraft->yaw) * cosf(aircraft->pitch),
        TAS * sinf(aircraft->pitch),
        TAS * cosf(aircraft->pitch) * sinf(aircraft->yaw)
    };

    // Get the velocity vector and its magnitude.
    float airSpeed = calculateMagnitude(velocity.x, velocity.y, velocity.z);
    
    // Get the aircraft's up vector.
    Vector3 up = physicsData->upVector;
    
    // Compute an intermediate vector: cross(velocity, up)
    Vector3 cross1 = vectorCross(velocity, up);
    if (calculateMagnitude(cross1.x, cross1.y, cross1.z) < 1e-6f) {
        // Fallback if velocity and up are parallel.
        cross1 = (Vector3){0, 0, 1};
    }
    
    // Compute the lift direction as: cross(cross1, velocity) and normalize it.
    Vector3 liftDir = vectorCross(cross1, velocity);
    float liftDirMag = calculateMagnitude(liftDir.x, liftDir.y, liftDir.z);

    if (liftDirMag < 1e-6f) {
        liftDir = (Vector3){0, 1, 0};
    }
    else {
        liftDir.x /= liftDirMag;
        liftDir.y /= liftDirMag;
        liftDir.z /= liftDirMag;
    }
    
    // Compute lift magnitude: L = 0.5 * ρ * V² * S * C_L.
    float airDensity = physicsData->airDensity;
    float liftForceMagnitude = 0.5f * airDensity * airSpeed * airSpeed * wingArea * coefficientLift;
    
    return (Vector3){
        liftDir.x * liftForceMagnitude,
        liftDir.y * liftForceMagnitude,
        liftDir.z * liftForceMagnitude
    }; // return lift force components
}

/*
    #########################################################
    #                                                       #
    #               DRAG CALCULATION FUNCTIONS              #
    #                                                       #
    #########################################################
*/

float calculateAspectRatio(float wingspan, float wingArea) {
    if (wingArea < 1e-6f) { // Prevent division by zero
        return 0.0f;
    }

    // check for errors or warnings
    CHECK_VAR(wingspan, "wingspan", "calculateAspectRatio", 0.0f);
    CHECK_VAR(wingArea, "wingArea", "calculateAspectRatio", 0.0f);

    return wingspan * wingspan / wingArea; // calculate and return aspect ratio
}

float calculateDragCoefficient(float speed, float maxSpeed, float C_d0, PhysicsData *physicsData){
    // check for errors or warnings
    CHECK_VAR(convertMsToKmh(speed), "speed", "calculateDragCoefficient", 0.0f);
    CHECK_VAR(maxSpeed, "maxSpeed", "calculateDragCoefficient", 0.0f);
    CHECK_VAR(C_d0, "C_d0", "calculateDragCoefficient", 0.0f);
    CHECK_PTR(physicsData, "physicsData", "calculateDragCoefficient", 0.0f);

    float mach = speed / physicsData->speedOfSound; // calculate Mach number

    if (mach < 0.8f) { // Subsonic flight (Mach < 0.8)
        return C_d0 + 0.05f * powf(speed / maxSpeed, 2); // calculate and return drag coefficient
    }
    else if (mach < 1.2f) { // Transonic flight (Mach ~ 0.8 to 1.2)
        return C_d0 + 0.05f * powf(speed / maxSpeed, 2) + alpha * powf((mach - 1), 2); // calculate and return drag coefficient
    }
    else { // Supersonic flight (Mach > 1.2)
        return C_d0 + kw * powf((mach - Md), 2); // calculate and return drag coefficient
    }
}

float calculateParasiticDrag(float C_d, float airDensity, float speed, float wingArea) {
    // check for errors or warnings
    CHECK_VAR(C_d, "C_d", "calculateParasiticDrag", 0.0f);
    CHECK_VAR(airDensity, "airDensity", "calculateParasiticDrag", 0.0f);
    CHECK_SPEED_LIMIT(convertMsToKmh(speed), "calculateParasiticDrag");
    CHECK_VAR(convertMsToKmh(speed), "speed", "calculateParasiticDrag", 0.0f);
    CHECK_VAR(wingArea, "wingArea", "calculateParasiticDrag", 0.0f);

    return (0.5f * C_d * airDensity * powf(speed, 2) * wingArea) * M_DRAG_COEFFICIENT; // calculate and return parasitic drag
}

float calculateInducedDrag(float liftCoefficient, float aspectRatio, float airDensity, float wingArea, float speed) {
    // check for errors or warnings
    CHECK_VAR(liftCoefficient, "liftCoefficient", "calculateInducedDrag", 0.0f);
    CHECK_VAR(aspectRatio, "aspectRatio", "calculateInducedDrag", 0.0f);
    CHECK_VAR(airDensity, "airDensity", "calculateInducedDrag", 0.0f);
    CHECK_VAR(wingArea, "wingArea", "calculateInducedDrag", 0.0f);
    CHECK_SPEED_LIMIT(convertMsToKmh(speed), "calculateInducedDrag");
    CHECK_VAR(convertMsToKmh(speed), "speed", "calculateInducedDrag", 0.0f);

    if (speed < 0.1f) return 0.0f; // Prevent divide-by-zero issues for very low speeds

    return (0.5f * airDensity * powf(speed, 2) * wingArea * ((liftCoefficient * liftCoefficient) / (PI * aspectRatio * OEF))) * M_DRAG_COEFFICIENT; // calculate and return induced drag
}

float calculateDragDivergenceAroundMach(float speed, PhysicsData *physicsData){
    // check for errors or warnings
    CHECK_SPEED_LIMIT(convertMsToKmh(speed), "calculateDragDivergenceAroundMach");
    CHECK_VAR(convertMsToKmh(speed), "speed", "calculateDragDivergenceAroundMach", 0.0f);
    CHECK_PTR(physicsData, "physicsData", "calculateDragDivergenceAroundMach", 0.0f);

    float mach = speed / physicsData->speedOfSound; // get current Mach speed

    float Cdw = 0;
    if (mach > Md) Cdw = C_D0 * kw * powf((mach - Md), 2); // if Mach > Md, calculate additional drag

    return Cdw * M_DRAG_COEFFICIENT; // return drag divergence
}

float calculateTotalDrag(float *parasiticDrag, float *inducedDrag, float *waveDrag, float *relativeSpeed, Vector3 *relativeVelocity, AircraftState *aircraft, PhysicsData *physicsData){
    // check for errors or warnings
    // in some cases, i pass null intentionally as to ignore the pointers.
    // CHECK_PTR(parasiticDrag, "parasiticDrag", "calculateTotalDrag", 0.0f);
    // CHECK_PTR(inducedDrag, "inducedDrag", "calculateTotalDrag", 0.0f);
    // CHECK_PTR(waveDrag, "waveDrag", "calculateTotalDrag", 0.0f);
    // CHECK_PTR(relativeSpeed, "relativeSpeed", "calculateTotalDrag", 0.0f);
    // CHECK_PTR(relativeVelocity, "relativeVelocity", "calculateTotalDrag", 0.0f);
    CHECK_PTR(aircraft, "aircraft", "calculateTotalDrag", 0.0f);
    CHECK_PTR(physicsData, "physicsData", "calculateTotalDrag", 0.0f);

    Vector3 wind = physicsData->windVector; // get wind vector
    float tas = physicsData->trueAirspeed; // get true airspeed

    Vector3 tasVector = {
        tas * cosf(aircraft->yaw) * cosf(aircraft->pitch),
        tas * sinf(aircraft->pitch),
        tas * cosf(aircraft->pitch) * sinf(aircraft->yaw)
    }; // convert TAS into a velocity vector

    if (relativeVelocity != NULL) {
        relativeVelocity->x = tasVector.x - wind.x;
        relativeVelocity->y = tasVector.y - wind.y;
        relativeVelocity->z = tasVector.z - wind.z;
    }

    Vector3 relVelocity = {
        tasVector.x - wind.x,
        tasVector.y - wind.y,
        tasVector.z - wind.z
    };

    float relSpeed = calculateMagnitude(relVelocity.x, relVelocity.y, relVelocity.z); // calculate relative speed

    if (relativeSpeed != NULL) {
        *relativeSpeed = relSpeed;
    }


    float parasiticDragValue = physicsData->parasiticDrag; // get parasitic drag
    float inducedDragValue = physicsData->inducedDrag;
    float waveDragValue = physicsData->dragDivergence;

    if (parasiticDrag != NULL) {
        *parasiticDrag = parasiticDragValue;
    }
    if (inducedDrag != NULL) {
        *inducedDrag = inducedDragValue;
    }
    if (waveDrag != NULL) {
        *waveDrag = waveDragValue;
    }

    return parasiticDragValue + inducedDragValue + waveDragValue; // return total drag
}

/*
    #########################################################
    #                                                       #
    #                   THRUST CALCULATION                  #
    #                                                       #
    #########################################################
*/

float calculateThrust(int thrust, int afterburnerThrust, int percentControl, PhysicsData *physicsData){
    // check for errors or warnings
    CHECK_PTR(physicsData, "physicsData", "calculateThrust", 0.0f);

    int usedThrust;
    bool afterBurnerOn;

    if (percentControl > 100) {
        afterBurnerOn = true;
        percentControl = 100; // set to 100 so in later calculations its not > 100
    } else {
        afterBurnerOn = false;
    }

    if (afterBurnerOn) { // if the afterburner is on, use the afterburner thrust, if not, use normal
        usedThrust = afterburnerThrust;
    } else {
        usedThrust = thrust;
    }

    // calculate thrust at altitude
    float airDensityAtCurrentAltitude = physicsData->airDensity;

    // get derate factor
    float derateFactor = (airDensityAtCurrentAltitude / airDensityAtSeaLevel);

    if (airDensityAtSeaLevel < 1e-6f) { // Prevent division by zero
        return 0.0f;
    }
    
    // calculate thrust
    float calculatedThrust = (float)usedThrust * derateFactor;

    // apply user control
    calculatedThrust = ((float)percentControl / 100.0f) * calculatedThrust;

    // modify thrust based on speed of the aircraft
    float mach = physicsData->machNumber;
    const float ramRecoveryFactor = 0.3f; // estimate for turbojet 

    float speedModifiedThrust = calculatedThrust * (1 + ramRecoveryFactor * mach);

    // if the modified thrust exceeds the top thrust of the engine, cap it
    speedModifiedThrust = (speedModifiedThrust > usedThrust) ? (float)usedThrust : speedModifiedThrust;

    // return the calculated thrust
    return speedModifiedThrust;
}

/*
    #########################################################
    #                                                       #
    #                      ORIENTATION                      #
    #                                                       #
    #########################################################
*/

// ===== NOT USED ATM =====

Orientation calculateNewOrientation(float deltaTime){
    AircraftControls *controls = getControls(); // Get the current aircraft controls

    // Calculate new orientation based on rates of change
    Orientation newOrientation;
    newOrientation.yaw = controls->yaw + (controls->yawRate * deltaTime); // Update yaw
    newOrientation.pitch = controls->pitch + (controls->pitchRate * deltaTime); // Update pitch
    newOrientation.roll = controls->roll + (controls->rollRate * deltaTime); // Update roll
    return newOrientation; // Return the new orientation
}

Vector3 getDirectionVector(Orientation newOrientation){
    Vector3 directionVector;

    directionVector.x = cosf(newOrientation.pitch) * cosf(newOrientation.yaw); // Calculate x component
    directionVector.y = sinf(newOrientation.pitch); // Calculate y component
    directionVector.z = cosf(newOrientation.pitch) * sinf(newOrientation.yaw); // Calculate z component

    return directionVector; // Return the direction vector
}

void updateVelocity(AircraftState *aircraft, float deltaTime, AircraftData *data, PhysicsData *physicsData){   
    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "updateVelocity", );
    CHECK_PTR(data, "data", "updateVelocity", );
    CHECK_PTR(physicsData, "physicsData", "updateVelocity", );

    // UPDATE VX
    const float T = physicsData->thrust;
    const float D = physicsData->totalDrag;
    
    const float ax = (T - D) / data->mass; // Calculate acceleration in x direction
    aircraft->vx += ax * deltaTime; // Update velocity in x direction

    // UPDATE VY
    const float L = calculateLift(data->wingArea, physicsData); // Calculate lift
    const float W = GRAVITY * data->mass; // Calculate weight

    const float ay = (L - W) / data->mass; // Calculate acceleration in y direction
    aircraft->vy += ay * deltaTime; // Update velocity in y direction

    // // COMPUTE FLIGHT PATH ANGLE (γ)
    // const float gamma = atan2f(aircraft->vy, aircraft->vx); // Calculate flight path angle

    // // GET AOA
    // const float aoa = physicsData->angleOfAttack; // Get angle of attack
}

/*
    #########################################################
    #                                                       #
    #                   TAS CALCULATION                     #
    #                                                       #
    #########################################################
*/

float getTemperatureKelvin(float altitudeMeters, PhysicsData *physicsData){
    // Check for errors or warnings
    CHECK_ALT_LIMIT(altitudeMeters, "getTemperatureKelvin");
    CHECK_VAR(altitudeMeters, "altitudeMeters", "getTemperatureKelvin", 0.0f);
    CHECK_PTR(physicsData, "physicsData", "getTemperatureKelvin", 0.0f);

    float tropopause = physicsData->tropopauseAltitude; // get the altitude of the tropopause

    if (altitudeMeters > tropopause) { // if the altitude is above the tropopause
        return 216.65f; // return the constant temperature above tropopause
    }

    return T0 - 6.5f * altitudeMeters / 1000.0f;
}

float getPressureAtAltitude(PhysicsData *physicsData){
    // Check for errors or warnings
    CHECK_PTR(physicsData, "physicsData", "getPressureAtAltitude", 0.0f);

    const float T = physicsData->temperatureKelvin; // temperature in kelvin

    const float exponent = -(GRAVITY/(Kt * R));

    const float P = P0 * (powf((T / T0), exponent));

    return P;
}

float calculateTAS(PhysicsData *physicsData){
    // Check for errors or warnings
    CHECK_PTR(physicsData, "physicsData", "calculateTAS", 0.0f);

    // Get the IAS 
    float IAS = physicsData->velocityMagnitude;
    
    // Get air density at current altitude and at sea level
    float airDensityCurrent = physicsData->airDensity;
    
    // Correct IAS to get TAS
    float TAS = IAS / sqrtf(airDensityCurrent / airDensityAtSeaLevel);
    return TAS;
}

/*
    #########################################################
    #                                                       #
    #                   HELPER FUNCTIONS                    #
    #                                                       #
    #########################################################
*/

Vector3 vectorCross(Vector3 a, Vector3 b) {
    return (Vector3){
        a.y * b.z - a.z * b.y, // x component
        a.z * b.x - a.x * b.z, // y component
        a.x * b.y - a.y * b.x  // z component
    };
}

Vector3 getUpVector(AircraftState *aircraft) {  
    Vector3 retV = {0.0f, 0.0f, 0.0f};  
    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "getUpVector", retV);

    float cosYaw   = cosf(aircraft->yaw),   sinYaw   = sinf(aircraft->yaw);   // Calculate cosine and sine of yaw
    float cosPitch = cosf(aircraft->pitch), sinPitch = sinf(aircraft->pitch); // Calculate cosine and sine of pitch
    float cosRoll  = cosf(aircraft->roll),  sinRoll  = sinf(aircraft->roll);  // Calculate cosine and sine of roll
    
    Vector3 up;
    up.x = -cosYaw * sinRoll - sinYaw * sinPitch * cosRoll; // Calculate x component of up vector
    up.y = cosPitch * cosRoll;                              // Calculate y component of up vector
    up.z = -sinYaw * sinRoll + cosYaw * sinPitch * cosRoll; // Calculate z component of up vector
    return up; // Return the up vector
}

Vector3 getUnitVectorFromVector(Vector3 vector) {
    float magnitude = calculateMagnitude(vector.x, vector.y, vector.z); // Calculate the magnitude of the vector
    if (magnitude < 0.0001f) { // Prevent division by zero
        return (Vector3){0.0f, 0.0f, 0.0f}; // Return zero vector if magnitude is too small
    }
    return (Vector3){ vector.x / magnitude,  // Normalize x component
                      vector.y / magnitude,  // Normalize y component
                      vector.z / magnitude }; // Normalize z component
}

float convertRadiansToDeg(float radians){
    // Check for errors or warnings
    CHECK_VAR(radians, "radians", "convertRadiansToDeg", 0.0f);

    return radians * (180.0f / PI); // Convert radians to degrees
}

float convertKmhToMs(float kmh){
    // Check for errors or warnings
    CHECK_VAR(kmh, "kmh", "convertKmhToMs", 0.0f);
    CHECK_SPEED_LIMIT(kmh, "convertKmhToMs");
    
    return kmh / 3.6f; // Convert km/h to m/s
}

float convertMsToKmh(float ms){
    // Check for errors or warnings
    CHECK_VAR(ms*3.6f, "ms", "convertMsToKmh", 0.0f);
    CHECK_SPEED_LIMIT(ms*3.6f, "convertMsToKmh");

    return ms * 3.6f; // Convert m/s to km/h
}

float calculateSpeedOfSound(float altitude, PhysicsData *physicsData){
    // Check for errors or warnings
    CHECK_PTR(physicsData, "physicsData", "calculateSpeedOfSound", 0.0f);
    CHECK_ALT_LIMIT(altitude, "calculateSpeedOfSound");
    CHECK_VAR(altitude, "altitude", "calculateSpeedOfSound", 0.0f);

    float tropopause = physicsData->tropopauseAltitude; // get the altitude of the tropopause
    float T = physicsData->temperatureKelvin; // temperature in kelvin

    if (altitude > tropopause){ // if the plane is above than the tropopause
        return sqrtf(gammaHeats * R * T); // 295.07 m/s (constant)
    }
    else{ // plane is below the tropopause
        return 340.29f * sqrtf(T/T0); // speed of sound formula
    }

    return 19.0f; // shouldnt be reached but just in case
}

float convertMsToMach(float ms, PhysicsData *physicsData){
    // Check for errors or warnings
    CHECK_PTR(physicsData, "physicsData", "convertMsToMach", 0.0f);
    CHECK_SPEED_LIMIT(convertMsToKmh(ms), "convertMsToMach");
    CHECK_VAR(convertMsToKmh(ms), "ms", "convertMsToMach", 0.0f);

    return ms / physicsData->speedOfSound;
}

float interpolate(float lowerAlt, float upperAlt, float lowerDensity, float upperDensity, float targetAltitude) {
    // Check for errors or warnings
    CHECK_VAR(lowerAlt, "lowerAlt", "interpolate", 0.0f);
    CHECK_VAR(upperAlt, "upperAlt", "interpolate", 0.0f);
    CHECK_VAR(lowerDensity, "lowerDensity", "interpolate", 0.0f);
    CHECK_VAR(upperDensity, "upperDensity", "interpolate", 0.0f);
    CHECK_VAR(targetAltitude, "targetAltitude", "interpolate", 0.0f);

    float fraction = (targetAltitude - lowerAlt) / (upperAlt - lowerAlt);
    return lowerDensity + fraction * (upperDensity - lowerDensity);
}

/*
    #########################################################
    #                                                       #
    #                        FUEL                           #
    #                                                       #
    #########################################################
*/

float getFuelBurnRate(AircraftData *data, float throttle){
    // Check for errors or warnings
    CHECK_PTR(data, "data", "getFuelBurnRate", 0.0f);
    CHECK_VAR(throttle, "throttle", "getFuelBurnRate", 0.0f);

    int afterburner = (throttle > 1.0f) ? 1 : 0;

    if (afterburner){
        if (data->afterburnerFuelBurn < 0.0f) {
            logMessage(LOG_ERROR, "Invalid afterburner fuel burn rate");
            return 0.0f;
        }
        return data->afterburnerFuelBurn;
    }
    else{
        if (data->fuelBurn < 0.0f) {
            logMessage(LOG_ERROR, "Invalid fuel burn rate");
            return 0.0f;
        }
        return data->fuelBurn * throttle; // simple linear scaling
    }
}

void updateFuelLevel(float *fuelKg, float deltaTime, float fuelBurnRate){
    // Check for errors or warnings
    CHECK_PTR(fuelKg, "fuelKg", "updateFuelLevel", );
    CHECK_VAR(deltaTime, "deltaTime", "updateFuelLevel", );
    CHECK_VAR(fuelBurnRate, "fuelBurnRate", "updateFuelLevel", );

    *fuelKg -= fuelBurnRate * deltaTime; // Update fuel level

    if (*fuelKg < 0.0f){
        *fuelKg = 0.0f; // Prevent negative fuel levels
        logMessage(LOG_WARNING, "Out of fuel!");
    }
}

void updateAircraftMass(AircraftState *aircraft, AircraftData *data, float fuelBurnRate, float deltaTime){
    // Check for errors or warnings
    CHECK_PTR(aircraft, "aircraft", "updateAircraftMass", );
    CHECK_PTR(data, "data", "updateAircraftMass", );
    CHECK_VAR(fuelBurnRate, "fuelBurnRate", "updateAircraftMass", );
    CHECK_VAR(deltaTime, "deltaTime", "updateAircraftMass", );

    float fuelBurned = fuelBurnRate * deltaTime; // Calculate fuel burned
    (aircraft->currentMass) -= fuelBurned; // Update aircraft mass

    if (aircraft->currentMass < data->mass){
        aircraft->currentMass = data->mass; // Prevent lower mass than min mass of aircraft
    }
}

/*
    #########################################################
    #                                                       #
    #                   PHYSICS UPDATE                      #
    #                                                       #
    #########################################################
*/

void updatePhysicsData(PhysicsData *physics, float altitude, AircraftState *aircraft, AircraftData *data, float simulationTime) {
    // Check for errors or warnings
    CHECK_PTR(physics, "physics", "updatePhysicsData", );
    CHECK_ALT_LIMIT(altitude, "updatePhysicsData");
    CHECK_VAR(altitude, "altitude", "updatePhysicsData", );
    CHECK_PTR(aircraft, "aircraft", "updatePhysicsData", );
    CHECK_PTR(data, "data", "updatePhysicsData", );
    CHECK_VAR(simulationTime, "simulationTime", "updatePhysicsData", );

    // 1. Atmosphere: update tropopause, temperature, air density, and speed of sound
    physics->tropopauseAltitude = getTropopause();
    physics->temperatureKelvin   = getTemperatureKelvin(altitude, physics);
    physics->airDensity          = getAirDensity(altitude, physics);
    physics->speedOfSound        = calculateSpeedOfSound(altitude, physics);
    
    // 2. Flight parameters: compute velocity magnitude, true airspeed, Mach, flight path angle, and AoA
    physics->velocityMagnitude = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz);
    physics->trueAirspeed      = calculateTAS(physics);
    physics->machNumber        = convertMsToMach(physics->trueAirspeed, physics);
    physics->flightPathAngle   = getFlightPathAngle(aircraft, physics);
    physics->angleOfAttack     = calculateAoA(aircraft);
    
    // 3. Orientation: update aircraft orientation in degrees and cache the radian values
    physics->pitchDegrees = convertRadiansToDeg(aircraft->pitch);
    physics->yawDegrees   = convertRadiansToDeg(aircraft->yaw);
    physics->rollDegrees  = convertRadiansToDeg(aircraft->roll);

    // 4. Orientation vectors: update wind, up, rightWingDirection and lift axis
    physics->windVector = getWindVector(altitude, simulationTime);
    physics->upVector = getUpVector(aircraft);
    physics->rightWingDirection = getRightWingDirection(aircraft, physics);
    {
        Vector3 wingRight = physics->rightWingDirection;
        Vector3 unitVelocity = getUnitVector(aircraft, physics);
        physics->liftAxisVector = getLiftAxisVector(wingRight, unitVelocity);
    }
    
    // 5. Aerodynamics: compute lift coefficient, aspect ratio, then lift force
    physics->liftCoefficient = calculateLiftCoefficient(data->mass, aircraft, data->wingArea, physics);
    physics->aspectRatio     = calculateAspectRatio(data->wingSpan, data->wingArea);
    physics->liftForce       = computeLiftForceComponents(aircraft, data->wingArea, physics->liftCoefficient, physics);

    // 6. Aerodynamics: compute drag coefficients and forces
    float maxSpeedMs = convertKmhToMs(data->maxSpeed);
    physics->dragCoefficient = calculateDragCoefficient(physics->trueAirspeed, maxSpeedMs, C_D0, physics);
    physics->parasiticDrag   = calculateParasiticDrag(physics->dragCoefficient, physics->airDensity, physics->trueAirspeed, data->wingArea);
    physics->inducedDrag     = calculateInducedDrag(physics->liftCoefficient, physics->aspectRatio, physics->airDensity, data->wingArea, physics->trueAirspeed);
    physics->dragDivergence  = calculateDragDivergenceAroundMach(physics->trueAirspeed, physics);
    physics->totalDrag       = physics->parasiticDrag + physics->inducedDrag + physics->dragDivergence;
    
    // 7. Engine: update thrust
    physics->thrust = calculateThrust(data->thrust, data->afterburnerThrust, (int)(aircraft->controls.throttle * 100), physics);
    
    // 8. Placeholder for drag force; computed later in computeAcceleration()
    physics->dragForce = (Vector3){0.0f, 0.0f, 0.0f};
    
    physics->lastSimulationTime = simulationTime;
}

Vector3 computeAcceleration(Vector3 velocity, AircraftState *aircraft, AircraftData *aircraftData, PhysicsData *physicsData){
    float mass = aircraftData->mass;

    // Gravity force remains constant
    Vector3 gravityForce = { 0, -GRAVITY * mass, 0 };

    // Compute lift with updated velocity
    Vector3 liftForce = physicsData->liftForce;

    // Compute drag with updated velocity
    float relativeSpeed = sqrtf(velocity.x * velocity.x +
                                velocity.y * velocity.y +
                                velocity.z * velocity.z);
    Vector3 relativeVelocity = velocity;  // Use current velocity

    float totalDrag = physicsData->totalDrag;

    Vector3 dragForce;
    if (relativeSpeed < 0.0001f) { // Prevent division by zero
        dragForce = (Vector3){0.0f, 0.0f, 0.0f};
    }
    else {
        dragForce = (Vector3){
            -totalDrag * (relativeVelocity.x / relativeSpeed),
            -totalDrag * (relativeVelocity.y / relativeSpeed),
            -totalDrag * (relativeVelocity.z / relativeSpeed)
        };
    }

    // Compute thrust with updated aircraft state
    float thrustMagnitude = physicsData->thrust;

    Vector3 thrustForce;
    if (aircraft->fuel <= 0.0f) { // for now simply: if youre out of fuel, engine instantly stops producing thrust. irl it'd slowly go down to zero (i think)
        thrustForce = (Vector3){0, 0, 0};
        globalPhysicsData.thrust = 0; // 0N
    } 
    else { // engine not out of fuel
        thrustForce = (Vector3){
            thrustMagnitude * cosf(aircraft->pitch) * cosf(aircraft->yaw),
            thrustMagnitude * sinf(aircraft->pitch),
            thrustMagnitude * cosf(aircraft->pitch) * sinf(aircraft->yaw)
        };
    }

    // Compute net force
    Vector3 netForce = {
        gravityForce.x + liftForce.x + dragForce.x + thrustForce.x,
        gravityForce.y + liftForce.y + dragForce.y + thrustForce.y,
        gravityForce.z + liftForce.z + dragForce.z + thrustForce.z
    };

    // Compute acceleration
    Vector3 acceleration = {
        netForce.x / mass,
        netForce.y / mass,
        netForce.z / mass
    };

    return acceleration;
}

void updatePhysics(AircraftState *aircraft, float deltaTime, float simulationTime, AircraftData *aircraftData) {
    // Compute physicsData only once per frame
    if (fabsf(globalPhysicsData.lastSimulationTime - simulationTime) > 1e-6f) {
        updatePhysicsData(&globalPhysicsData, aircraft->y, aircraft, aircraftData, simulationTime);
        globalPhysicsData.lastSimulationTime = simulationTime;
    }

    Vector3 v0 = { aircraft->vx, aircraft->vy, aircraft->vz };

    // Compute k1 using the current state
    Vector3 k1 = computeAcceleration(v0, aircraft, aircraftData, &globalPhysicsData);

    // Create temporary aircraft states for RK4 integration
    AircraftState tempAircraft = *aircraft;
    
    // Compute k2
    tempAircraft.vx = v0.x + 0.5f * k1.x * deltaTime;
    tempAircraft.vy = v0.y + 0.5f * k1.y * deltaTime;
    tempAircraft.vz = v0.z + 0.5f * k1.z * deltaTime;
    updateVelocity(&tempAircraft, deltaTime * 0.5f, aircraftData, &globalPhysicsData); // Update orientation for intermediate step
    Vector3 k2 = computeAcceleration((Vector3){ tempAircraft.vx, tempAircraft.vy, tempAircraft.vz }, &tempAircraft, aircraftData, &globalPhysicsData);

    // Compute k3
    tempAircraft = *aircraft; // Reset temp aircraft
    tempAircraft.vx = v0.x + 0.5f * k2.x * deltaTime;
    tempAircraft.vy = v0.y + 0.5f * k2.y * deltaTime;
    tempAircraft.vz = v0.z + 0.5f * k2.z * deltaTime;
    updateVelocity(&tempAircraft, deltaTime * 0.5f, aircraftData, &globalPhysicsData);
    Vector3 k3 = computeAcceleration((Vector3){ tempAircraft.vx, tempAircraft.vy, tempAircraft.vz }, &tempAircraft, aircraftData, &globalPhysicsData);

    // Compute k4
    tempAircraft = *aircraft; // Reset temp aircraft
    tempAircraft.vx = v0.x + k3.x * deltaTime;
    tempAircraft.vy = v0.y + k3.y * deltaTime;
    tempAircraft.vz = v0.z + k3.z * deltaTime;
    updateVelocity(&tempAircraft, deltaTime, aircraftData, &globalPhysicsData);
    Vector3 k4 = computeAcceleration((Vector3){ tempAircraft.vx, tempAircraft.vy, tempAircraft.vz }, &tempAircraft, aircraftData, &globalPhysicsData);

    // RK4 final velocity update
    aircraft->vx += (k1.x + 2.0f * k2.x + 2.0f * k3.x + k4.x) * (deltaTime / 6.0f);
    aircraft->vy += (k1.y + 2.0f * k2.y + 2.0f * k3.y + k4.y) * (deltaTime / 6.0f);
    aircraft->vz += (k1.z + 2.0f * k2.z + 2.0f * k3.z + k4.z) * (deltaTime / 6.0f);

    // Update aircraft orientation with the new velocity
    updateVelocity(aircraft, deltaTime, aircraftData, &globalPhysicsData);

    // Update aircraft fuel level and mass
    float fuelBurnRate = getFuelBurnRate(aircraftData, aircraft->controls.throttle);
    updateFuelLevel(&aircraft->fuel, deltaTime, fuelBurnRate);
    updateAircraftMass(aircraft, aircraftData, fuelBurnRate, deltaTime);
}