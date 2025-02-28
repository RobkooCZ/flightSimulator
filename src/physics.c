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

// Include libraries
#include <math.h>
#include <stdbool.h>
#include <stdio.h>

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

float getAirDensity(float altitude) {
    float tropopause = getTropopause(); // get the altitude of the tropopause
    const float R = 287.04f; // specific gas constant for dry air

    // If the plane is below or at the tropopause
    if (altitude <= tropopause){
        const float Kt = -0.0065f; // temperature lapse rate in the troposphere
        const float exponent = -((GRAVITY)/(Kt * R)) - 1; // exponent for the density equation
        const float T = getTemperatureKelvin(altitude); // temperature at the given altitude
        const float T0 = 288.15f; // sea level temperature in Kelvin
        const float rho_0 = 1.225f; // sea level air density in kg/m^3

        return rho_0 * powf((T / T0), exponent); // calculate and return air density
    }
    // If the plane is above the tropopause
    else {
        const float rho_top = 0.3639f; // air density at the tropopause
        const float T_trop = 216.65f; // temperature at the tropopause
        return rho_top * expf(-((GRAVITY)/(R * T_trop)) * (altitude - tropopause)); // calculate and return air density
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

LAV calculateLAV(AircraftState *aircraft) {
    float pitchRad = convertDegToRadians(aircraft->pitch);  // Convert pitch to radians
    float yawRad = convertDegToRadians(aircraft->yaw);      // Convert yaw to radians

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

float getFlightPathAngle(AircraftState *aircraft){
    float TAS = calculateTAS(aircraft); // calculate true airspeed
    return asinf(aircraft->vz/TAS); // calculate and return flight path angle
}

float calculateLiftCoefficient(float mass, AircraftState *aircraft, float wingArea){
    const float airDensity = getAirDensity(aircraft->y); // get air density at current altitude
    float TAS = calculateTAS(aircraft); // calculate true airspeed
    float numerator = mass * GRAVITY; // calculate lift force numerator
    float denumerator = 0.5f * airDensity * powf(TAS, 2) * wingArea; // calculate lift force denominator

    if (convertRadiansToDeg(fabsf(aircraft->yaw)) > 5.0f && convertRadiansToDeg(fabsf(aircraft->pitch)) > 0.1f){ // plane is turning
        denumerator *= cosf(convertRadiansToDeg(aircraft->roll)); // adjust for roll angle
        return numerator / denumerator; // calculate and return lift coefficient
    }
    else{ // plane isn't turning
        const float tolerance = 1e-6f;
        if (fabs(aircraft->vy) < tolerance){ // aircraft isn't climbing or descending
            return numerator / denumerator; // calculate and return lift coefficient
        }
        else { // aircraft is climbing or descending
            numerator *= cosf(getFlightPathAngle(aircraft)); // adjust for flight path angle
            return numerator / denumerator; // calculate and return lift coefficient
        }
    }

    return 0.0f;
}

float calculateLift(AircraftState *aircraft, float wingArea, float mass) {
    float V = calculateTAS(aircraft); // calculate true airspeed
    float rho = getAirDensity(aircraft->y);  // get air density at current altitude
    float S = wingArea; // wing area for the plane (in m^2)
    float C_L = calculateLiftCoefficient(mass, aircraft, wingArea); // calculate lift coefficient

    return 0.5f * rho * V * V * S * C_L; // calculate and return lift force
}

/*
    #########################################################
    #                                                       #
    #               LIFT DIRECTION FUNCTIONS                #
    #                                                       #
    #########################################################
*/

Vector3 getUnitVector(AircraftState *aircraft) {
    float magnitude = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz); // calculate magnitude of velocity vector
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

Vector3 getRightWingDirection(AircraftState *aircraft){
    Vector3 wingRight;

    // Base right-wing direction (ignoring roll)
    wingRight.x = cosf(aircraft->yaw);
    wingRight.y = 0;
    wingRight.z = -sinf(aircraft->yaw);

    // Get unit velocity vector (Vunit)
    Vector3 Vunit = getUnitVector(aircraft);

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

Vector3 computeLiftForceComponents(AircraftState *aircraft, float wingArea, float coefficientLift) {
    // TAS
    float TAS = calculateTAS(aircraft);

    // convert it into a vector
    Vector3 velocity = {
        TAS * cosf(aircraft->yaw) * cosf(aircraft->pitch),
        TAS * sinf(aircraft->pitch),
        TAS * cosf(aircraft->pitch) * sinf(aircraft->yaw)
    };

    // Get the velocity vector and its magnitude.
    float airSpeed = calculateMagnitude(velocity.x, velocity.y, velocity.z);
    
    // Get the aircraft's up vector.
    Vector3 up = getUpVector(aircraft);
    
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
    float airDensity = getAirDensity(aircraft->y);
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

float calculateDragCoefficient(float speed, float maxSpeed, float altitude, float C_d0){
    const float alpha = 0.1f; // Constant for drag rise in transonic region
    const float kw = 30.0f;   // Drag rise constant for supersonic region
    const float Md = 0.89f;   // Drag divergence Mach number

    float mach = speed / calculateSpeedOfSound(altitude); // calculate Mach number

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

float calculateDragDivergenceAroundMach(float speed, AircraftState *aircraft){
    float mach = speed / calculateSpeedOfSound(aircraft->y); // get current Mach speed

    const float kw = 30.0f; // rough estimate for nearly supersonic aircraft (j29f, j32b)
    const float Md = 0.89f; // rough estimate for nearly supersonic aircraft (j29f, j32b)

    float Cdw = 0;
    if (mach > Md) Cdw = C_D0 * kw * powf((mach - Md), 2); // if Mach > Md, calculate additional drag

    return Cdw; // return drag divergence
}

float calculateTotalDrag(float *parasiticDrag, float *inducedDrag, float *waveDrag, float *relativeSpeed, Vector3 *relativeVelocity, float simulationTime, AircraftState *aircraft, AircraftData *data){
    Vector3 wind = getWindVector(aircraft->y, simulationTime); // get wind vector
    float tas = calculateTAS(aircraft); // calculate true airspeed

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

    float aspectRatio = calculateAspectRatio(data->wingSpan, data->wingArea); // calculate aspect ratio
    float airDensity = getAirDensity(aircraft->y); // get air density at current altitude
    float relSpeed = calculateMagnitude(relVelocity.x, relVelocity.y, relVelocity.z); // calculate relative speed

    if (relativeSpeed != NULL) {
        *relativeSpeed = relSpeed;
    }

    float liftCoefficient = calculateLiftCoefficient(data->mass, aircraft, data->wingArea); // calculate lift coefficient

    float C_d = calculateDragCoefficient(relSpeed, convertKmhToMs(data->maxSpeed), aircraft->y, C_D0); // calculate drag coefficient
    float parasiticDragValue = calculateParasiticDrag(C_d, airDensity, relSpeed, data->wingArea); // calculate parasitic drag
    float inducedDragValue = calculateInducedDrag(liftCoefficient, aspectRatio, airDensity, data->wingArea, relSpeed); // calculate induced drag
    float waveDragValue = calculateDragDivergenceAroundMach(tas, aircraft); // calculate wave drag

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

float calculateThrust(int thrust, int afterburnerThrust, AircraftState *aircraft, int percentControl) {
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
    float airDensityAtCurrentAltitude = getAirDensity(aircraft->y);
    float airDensityAtSeaLevel = getAirDensity(0);

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
    float tas = calculateTAS(aircraft);
    float mach = convertMsToMach(tas, aircraft->y);
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

void updateVelocity(AircraftState *aircraft, float deltaTime, AircraftData *data, float simulationTime){    
    // UPDATE VX
    const float T = calculateThrust(data->thrust, data->afterburnerThrust, aircraft, (int)(aircraft->controls.throttle * 100)); // Calculate thrust
    const float D = calculateTotalDrag(NULL, NULL, NULL, NULL, NULL, simulationTime, aircraft, data); // Calculate total drag
    
    const float ax = (T - D) / data->mass; // Calculate acceleration in x direction
    aircraft->vx += ax * deltaTime; // Update velocity in x direction

    // UPDATE VY
    const float L = calculateLift(aircraft, data->wingArea, data->mass); // Calculate lift
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

float getTemperatureKelvin(float altitudeMeters){
    float tropopause = getTropopause(); // get the altitude of the tropopause

    if (altitudeMeters > tropopause) { // if the altitude is above the tropopause
        return 216.65f; // return the constant temperature above tropopause
    }

    float T_0 = 288.15f;
    return T_0 - 6.5f * altitudeMeters / 1000.0f;
}

// Function to calculate the pressure at a given altitude
float getPressureAtAltitude(float altitudeMeters){
    const float R = 287.04f; // specific gas constant for dry air in J/(kg·K)
    const float Kt = -0.0065f; // temperature gradient in the troposphere
    const float T = getTemperatureKelvin(altitudeMeters);
    const float P0 = 101325.0f; // sea level pressure in pascals
    const float T0 = 288.15f; // sea level temperature in Kelvin

    const float exponent = -(GRAVITY/(Kt * R));

    const float P = P0 * (powf((T / T0), exponent));

    return P;
}

// Calculate True Air Speed (TAS) in m/s
float calculateTAS(AircraftState *aircraft) {
    // Compute the magnitude of the velocity vector (IAS)
    float IAS = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz);
    
    // Get air density at current altitude and at sea level
    float airDensityCurrent = getAirDensity(aircraft->y);
    float airDensitySeaLevel = getAirDensity(0);
    
    // Correct IAS to get TAS
    float TAS = IAS / sqrtf(airDensityCurrent / airDensitySeaLevel);
    return TAS;
}

/*
    #########################################################
    #                                                       #
    #                   HELPER FUNCTIONS                    #
    #                                                       #
    #########################################################
*/

// Function to calculate the cross product of two vectors
Vector3 vectorCross(Vector3 a, Vector3 b) {
    return (Vector3){
        a.y * b.z - a.z * b.y, // x component
        a.z * b.x - a.x * b.z, // y component
        a.x * b.y - a.y * b.x  // z component
    };
}

// Function to get the up vector of the aircraft based on its orientation
Vector3 getUpVector(AircraftState *aircraft) {
    float yaw   = convertDegToRadians(aircraft->yaw);   // Convert yaw to radians
    float pitch = convertDegToRadians(aircraft->pitch); // Convert pitch to radians
    float roll  = convertDegToRadians(aircraft->roll);  // Convert roll to radians
    
    float cosYaw   = cosf(yaw),   sinYaw   = sinf(yaw);   // Calculate cosine and sine of yaw
    float cosPitch = cosf(pitch), sinPitch = sinf(pitch); // Calculate cosine and sine of pitch
    float cosRoll  = cosf(roll),  sinRoll  = sinf(roll);  // Calculate cosine and sine of roll
    
    Vector3 up;
    up.x = -cosYaw * sinRoll - sinYaw * sinPitch * cosRoll; // Calculate x component of up vector
    up.y = cosPitch * cosRoll;                              // Calculate y component of up vector
    up.z = -sinYaw * sinRoll + cosYaw * sinPitch * cosRoll; // Calculate z component of up vector
    return up; // Return the up vector
}

// Function to get the unit vector from a given vector
Vector3 getUnitVectorFromVector(Vector3 vector) {
    float magnitude = calculateMagnitude(vector.x, vector.y, vector.z); // Calculate the magnitude of the vector
    if (magnitude < 0.0001f) { // Prevent division by zero
        return (Vector3){0.0f, 0.0f, 0.0f}; // Return zero vector if magnitude is too small
    }
    return (Vector3){ vector.x / magnitude,  // Normalize x component
                      vector.y / magnitude,  // Normalize y component
                      vector.z / magnitude }; // Normalize z component
}

// Function to convert radians to degrees
float convertRadiansToDeg(float radians){
    return radians * (180.0f / PI); // Convert radians to degrees
}

// Function to convert kilometers per hour to meters per second
float convertKmhToMs(float kmh){
    return kmh / 3.6f; // Convert km/h to m/s
}

// Function to convert meters per second to kilometers per hour
float convertMsToKmh(float ms){
    return ms * 3.6f; // Convert m/s to km/h
}

float calculateSpeedOfSound(float altitude){
    float tropopause = getTropopause();
    float gamma = 1.4f; // ratio of specific heats for air, aprox 1.4 for dry hair
    float R = 287.05f; // specific gas constant for dry air in J/(kg·K)
    float T0 = 288.15f; // sea level temperature in Kelvin
    float T = getTemperatureKelvin(altitude); // temperature in kelvin

    if (altitude > tropopause){ // if the plane is above than the tropopause
        return sqrtf(gamma * R * T); // 295.07 m/s (constant)
    }
    else{ // plane is below the tropopause
        return 340.29f * sqrtf(T/T0); // speed of sound formula
    }

    return 19.0f; // shouldnt be reached but just in case
}

float convertMsToMach(float ms, float altitude){
    return ms / calculateSpeedOfSound(altitude);
}

// Function to perform linear interpolation between two points
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

// function to update the physics of the aircraft
void updatePhysics(AircraftState *aircraft, float deltaTime, float simulationTime, AircraftData *aircraftData) {
    // Use aircraftData values for mass and wing area
    float mass = aircraftData->mass;
    float wingArea = aircraftData->wingArea;

    // --- GRAVITY ---
    Vector3 gravityForce = { 0, -GRAVITY * mass, 0 };

    // --- LIFT ---
    float liftCoefficient = calculateLiftCoefficient(mass, aircraft, wingArea);

    Vector3 liftForce = computeLiftForceComponents(aircraft, wingArea, liftCoefficient);

    Vector3 relativeVelocity = {0, 0, 0};
    float relativeSpeed = 0.0f;
    
    float totalDrag = calculateTotalDrag(NULL, NULL, NULL, &relativeSpeed, &relativeVelocity, simulationTime, aircraft, aircraftData);

    // Calculate drag force vector
    Vector3 dragForce = {
        -totalDrag * (relativeVelocity.x/relativeSpeed),
        -totalDrag * (relativeVelocity.y/relativeSpeed),
        -totalDrag * (relativeVelocity.z/relativeSpeed),    
    };

    // --- THRUST ---
    float thrustMagnitude = calculateThrust(
        aircraftData->thrust,
        aircraftData->afterburnerThrust,
        aircraft,
        (int)(aircraft->controls.throttle * 100)
    );

    Vector3 thrustForce = {
        thrustMagnitude * cosf(aircraft->pitch) * cosf(aircraft->yaw),
        thrustMagnitude * sinf(aircraft->pitch),
        thrustMagnitude * cosf(aircraft->pitch) * sinf(aircraft->yaw)
    };

    // --- SUM ALL FORCES ---
    Vector3 netForce = {
        gravityForce.x + liftForce.x + dragForce.x + thrustForce.x,
        gravityForce.y + liftForce.y + dragForce.y + thrustForce.y,
        gravityForce.z + liftForce.z + dragForce.z + thrustForce.z
    };

    // --- COMPUTE ACCELERATION ---
    Vector3 acceleration = {
        netForce.x / mass,
        netForce.y / mass,
        netForce.z / mass
    };

    // --- UPDATE VELOCITY ---
    aircraft->vx += acceleration.x * deltaTime;
    aircraft->vy += acceleration.y * deltaTime;
    aircraft->vz += acceleration.z * deltaTime;

    // --- UPDATE ORIENTATION BASED ON CONTROLS ---
    updateVelocity(aircraft, deltaTime, aircraftData, simulationTime);
}