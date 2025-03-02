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
#include "logger.h"

// Include libraries
#include <math.h>
#include <stdbool.h>
#include <stdio.h>

// Declare a global struct for the physics data
PhysicsData globalPhysicsData;

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
const float alpha = 0.1f;                         // Drag rise constant for transonic speeds
const float kw = 30.0f;                           // Drag rise constant for supersonic speeds
const float Md = 0.89f;                           // Drag divergence Mach number

/* Pressure Calculation */
const float P0 = 101325.0f;                       // Sea-level atmospheric pressure in Pascals
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
    return degrees * (PI / 180.0f); // convert degrees to radians
}

LAV calculateLAV(AircraftState *aircraft){
    float pitchRad = aircraft->pitch;
    float yawRad = aircraft->yaw;

    LAV lav;
    lav.lx = cosf(pitchRad) * cosf(yawRad);  // X component of LAV
    lav.ly = cosf(pitchRad) * sinf(yawRad);  // Y component of LAV
    lav.lz = sinf(pitchRad);                 // Z component of LAV
    return lav;
}

float calculateMagnitude(float x, float y, float z) {
    return sqrtf(x * x + y * y + z * z); // calculate the magnitude of a vector
}

float calculateDotProduct(LAV lav, float vx, float vy, float vz) {
    return vx * lav.lx + vy * lav.ly + vz * lav.lz; // calculate the dot product
}

float calculateAoA(AircraftState *aircraft) {
    const float treshold = 1e-6f; // avoid division by zero

    if (fabsf(aircraft->vx) < treshold) {
        return 0.0f; // avoid division by zero
    }

    float gamma = atanf(aircraft->vy / aircraft->vx); // calculate flight path angle

    float aoa = aircraft->pitch - gamma; // calculate angle of attack

    return aoa;
}

/*
    #########################################################
    #                                                       #
    #                    LIFT CALCULATION                   #
    #                                                       #
    #########################################################
*/

float getFlightPathAngle(AircraftState *aircraft, PhysicsData *physicsData){
    float TAS = physicsData->trueAirspeed; // calculate true airspeed
    return asinf(aircraft->vy / TAS);      // use vy (vertical component) for flight path angle
}

float calculateLiftCoefficient(float mass, AircraftState *aircraft, float wingArea, PhysicsData *physicsData){
    const float airDensity = physicsData->airDensity; // get air density at current altitude
    float TAS = physicsData->trueAirspeed; // calculate true airspeed
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
    float magnitude = physicsData->velocityMagnitude;
    if (magnitude < 0.0001f) { // Prevent division by zero
        return (Vector3){0.0f, 0.0f, 0.0f}; 
    }
    return (Vector3){ aircraft->vx / magnitude, 
                      aircraft->vy / magnitude, 
                      aircraft->vz / magnitude }; // return unit vector
}

Vector3 rotateAroundVector(Vector3 V, Vector3 K, float theta) {
    Vector3 rotated;

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
    Vector3 wingRight;

    // Base right-wing direction (ignoring roll)
    wingRight.x = cosf(aircraft->yaw);
    wingRight.y = 0;
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
    return wingspan * wingspan / wingArea; // calculate and return aspect ratio
}

float calculateDragCoefficient(float speed, float maxSpeed, float C_d0, PhysicsData *physicsData){
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
    return 0.5f * C_d * airDensity * powf(speed, 2) * wingArea; // calculate and return parasitic drag
}

float calculateInducedDrag(float liftCoefficient, float aspectRatio, float airDensity, float wingArea, float speed) {
    if (speed < 0.1f) return 0.0f; // Prevent divide-by-zero issues for very low speeds

    return 0.5f * airDensity * powf(speed, 2) * wingArea * ((liftCoefficient * liftCoefficient) / (PI * aspectRatio * OEF)); // calculate and return induced drag
}

float calculateDragDivergenceAroundMach(float speed, PhysicsData *physicsData){
    float mach = speed / physicsData->speedOfSound; // get current Mach speed

    float Cdw = 0;
    if (mach > Md) Cdw = C_D0 * kw * powf((mach - Md), 2); // if Mach > Md, calculate additional drag

    return Cdw; // return drag divergence
}

float calculateTotalDrag(float *parasiticDrag, float *inducedDrag, float *waveDrag, float *relativeSpeed, Vector3 *relativeVelocity, AircraftState *aircraft, PhysicsData *physicsData){
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

    // COMPUTE FLIGHT PATH ANGLE (γ)
    const float gamma = atan2f(aircraft->vy, aircraft->vx); // Calculate flight path angle

    // COMPUTE ANGLE OF ATTACK (AoA)
    aircraft->AoA = aircraft->pitch - gamma; // Calculate angle of attack
}

/*
    #########################################################
    #                                                       #
    #                   TAS CALCULATION                     #
    #                                                       #
    #########################################################
*/

float getTemperatureKelvin(float altitudeMeters, PhysicsData *physicsData){
    float tropopause = physicsData->tropopauseAltitude; // get the altitude of the tropopause

    if (altitudeMeters > tropopause) { // if the altitude is above the tropopause
        return 216.65f; // return the constant temperature above tropopause
    }

    return T0 - 6.5f * altitudeMeters / 1000.0f;
}

float getPressureAtAltitude(PhysicsData *physicsData){
    const float T = physicsData->temperatureKelvin; // temperature in kelvin

    const float exponent = -(GRAVITY/(Kt * R));

    const float P = P0 * (powf((T / T0), exponent));

    return P;
}

float calculateTAS(PhysicsData *physicsData){
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
    return radians * (180.0f / PI); // Convert radians to degrees
}

float convertKmhToMs(float kmh){
    return kmh / 3.6f; // Convert km/h to m/s
}

float convertMsToKmh(float ms){
    return ms * 3.6f; // Convert m/s to km/h
}

float calculateSpeedOfSound(float altitude, PhysicsData *physicsData){
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
    return ms / physicsData->speedOfSound;
}

float interpolate(float lowerAlt, float upperAlt, float lowerDensity, float upperDensity, float targetAltitude) {
    float fraction = (targetAltitude - lowerAlt) / (upperAlt - lowerAlt);
    return lowerDensity + fraction * (upperDensity - lowerDensity);
}

/*
    #########################################################
    #                                                       #
    #                   PHYSICS UPDATE                      #
    #                                                       #
    #########################################################
*/

void updatePhysicsData(PhysicsData *physics, float altitude, AircraftState *aircraft, AircraftData *data, float simulationTime) {
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
    physics->totalDrag       = calculateTotalDrag(NULL, NULL, NULL, NULL, NULL, aircraft, physics);
    
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

    Vector3 thrustForce = {
        thrustMagnitude * cosf(aircraft->pitch) * cosf(aircraft->yaw),
        thrustMagnitude * sinf(aircraft->pitch),
        thrustMagnitude * cosf(aircraft->pitch) * sinf(aircraft->yaw)
    };

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
}