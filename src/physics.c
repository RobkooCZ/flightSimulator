#include "physics.h"
#include "controls.h"
#include "weather.h"

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

// Function to get the air density at a given altitude
float getAirDensity(float altitude) {
    const float P = getPressureAtAltitude(altitude); // Pressure at the given altitude
    const float R = 287.05f; // Specific gas constant for dry air in J/(kg·K)
    const float T = getTemperatureKelvin(altitude); // Temperature at the given altitude

    const float p = P / (R * T); // Calculate the air density at the given altitude

    return p; // Return the calculated air density
}

/*
    #########################################################
    #                                                       #
    #               AoA COMPUTING FUNCTIONS                 #
    #                                                       #
    #########################################################

*/

float convertDegToRadians(float degrees){
    return degrees * (PI / 180.0f);
}

// Function to calculate the longitudinal axis vector based on pitch and yaw
LAV calculateLAV(AircraftState *aircraft) {
    float pitchRad = convertDegToRadians(aircraft->pitch);  // Convert pitch to radians
    float yawRad = convertDegToRadians(aircraft->yaw);      // Convert yaw to radians

    LAV lav;
    lav.lx = cosf(pitchRad) * cosf(yawRad);  // X component of LAV
    lav.ly = cosf(pitchRad) * sinf(yawRad);  // Y component of LAV
    lav.lz = sinf(pitchRad);                 // Z component of LAV
    return lav;
}

// Function to calculate the magnitude of a vector
float calculateMagnitude(float x, float y, float z) {
    return sqrtf(x * x + y * y + z * z);
}

// Function to calculate the dot product between velocity and longitudinal axis vectors
float calculateDotProduct(LAV lav, float vx, float vy, float vz) {
    return vx * lav.lx + vy * lav.ly + vz * lav.lz;
}

// Function to calculate the Angle of Attack (AoA)
float calculateAoA(AircraftState *aircraft) {
    float horizontalSpeed = sqrtf(aircraft->vx * aircraft->vx + aircraft->vz * aircraft->vz);
    if (horizontalSpeed < 1e-6f) { // avoid division by zero for very slow speeds
        return 0.0f;
    }

    return aircraft->pitch - atan2f(aircraft->vy, horizontalSpeed);
}

/*
    #########################################################
    #                                                       #
    #                    LIFT CALCULATION                   #
    #                                                       #
    #########################################################
*/


float calculateLiftCoefficient(float AoA) {
    const float cl0 = 0.36f;      // Baseline lift coefficient for zero (relative) AoA (accounting for wing incidence)
    const float cl_alpha = 2.0f * PI; // Lift curve slope (per radian)
    return cl0 + cl_alpha * AoA;
}

float calculateLift(AircraftState *aircraft, float wingArea) {
    // L = 0.5 * rho * V^2 * S * C_L
    float V = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz);
    float rho = getAirDensity(aircraft->y);  // altitude in meters
    float S = wingArea; // wing area for the plane (in m^2)
    // Use the new AoA directly from the aircraft state.
    float AoA = calculateAoA(aircraft);
    float C_L = calculateLiftCoefficient(AoA);
    return 0.5f * rho * V * V * S * C_L;
}

float calculateAy(float lift, float mass){
    // A_y = L / m
    return lift / mass;
}

/*
    #########################################################
    #                                                       #
    #               LIFT DIRECTION FUNCTIONS                #
    #                                                       #
    #########################################################
*/

Vector3 getUnitVector(AircraftState *aircraft) {
    float magnitude = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz);
    if (magnitude < 0.0001f) { // Prevent division by zero
        return (Vector3){0.0f, 0.0f, 0.0f}; 
    }
    return (Vector3){ aircraft->vx / magnitude, 
                      aircraft->vy / magnitude, 
                      aircraft->vz / magnitude };
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

    return rotated;
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

    return liftAxisVector;
}

Vector3 computeLiftForceComponents(AircraftState *aircraft, float wingArea, float coefficientLift) {
    // Get the velocity vector and its magnitude.
    Vector3 velocity = { aircraft->vx, aircraft->vy, aircraft->vz };
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
    } else {
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
    };
}

/*
    #########################################################
    #                                                       #
    #               DRAG CALCULATION FUNCTIONS              #
    #                                                       #
    #########################################################
*/

float calculateAspectRatio(float wingspan, float wingArea){
    return wingspan * wingspan / wingArea;
}

float calculateInducedDrag(float liftCoefficient, float aspectRatio){
    return liftCoefficient * liftCoefficient / (PI * OEF * aspectRatio);
}

float calculateTotalDragCoefficient(float inducedDrag){
    return C_D0 + inducedDrag;
}

float calculateDragForce(float dragCoefficient, float airDensity, float relativeSpeed, float wingArea) {
    float speedSquared = relativeSpeed * relativeSpeed;
    if (speedSquared < 0.0001f) return 0.0f; // Avoid division by zero

    return 0.5f * dragCoefficient * airDensity * wingArea * speedSquared;
}

/*
    #########################################################
    #                                                       #
    #                   THRUST CALCULATION                  #
    #                                                       #
    #########################################################
*/

float calculateThrust(int thrust, int afterburnerThrust, AircraftState *aircraft, float maxSpeed, int percentControl){
    int usedThrust;
    bool afterBurnerOn;

    if (percentControl > 100){
        afterBurnerOn = true;
        percentControl = 100; // set to 100 so in later calculations its not > 100
    }
    else{
        afterBurnerOn = false;
    }

    if (afterBurnerOn){ // if the afterburner is on, use the afterburner thrust, if not, use normal
        usedThrust = afterburnerThrust;
    }
    else{
        usedThrust = thrust;
    }

    float airDensityAtCurrentAltitude = getAirDensity(aircraft->y);
    float airDensityAtSeaLevel = getAirDensity(0);
    float currentSpeed = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz);

    float calculatedThrust = (float)usedThrust * (airDensityAtCurrentAltitude/airDensityAtSeaLevel) * (1.0f + 0.2f * (currentSpeed/maxSpeed));

    calculatedThrust = ((float)percentControl/100.0f) * calculatedThrust; // apply the user control to the thrust

    return calculatedThrust;
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
    AircraftControls *controls = getControls();

    // Calculate new orientation based on rates of change
    Orientation newOrientation;
    newOrientation.yaw = controls->yaw + (controls->yawRate * deltaTime);
    newOrientation.pitch = controls->pitch + (controls->pitchRate * deltaTime);
    newOrientation.roll = controls->roll + (controls->rollRate * deltaTime);
    return newOrientation;
}

Vector3 getDirectionVector(Orientation newOrientation){
    Vector3 directionVector;

    directionVector.x = cosf(newOrientation.pitch) * cosf(newOrientation.yaw);
    directionVector.y = sinf(newOrientation.pitch);
    directionVector.z = cosf(newOrientation.pitch) * sinf(newOrientation.yaw);

    return directionVector;
}

void updateVelocity(AircraftState *aircraft, float deltaTime){
    // Get the current speed
    float speed = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz);

    // update movement direction
    Vector3 direction;
    direction.x = cosf(aircraft->pitch) * cosf(aircraft->yaw);
    direction.y = sinf(aircraft->pitch);
    direction.z = cosf(aircraft->pitch) * sinf(aircraft->yaw);

    // apply speed to new direction
    aircraft->vx = speed * direction.x;
    aircraft->vy = speed * direction.y;
    aircraft->vz = speed * direction.z;

    // apply roll effect
    float rollTurningFactor = 0.5f;
    aircraft->yaw += sinf(aircraft->roll) * rollTurningFactor * deltaTime;
}


/*
    #########################################################
    #                                                       #
    #                   TAS CALCULATION                     #
    #                                                       #
    #########################################################
*/

float getTemperatureKelvin(float altitudeMeters){
    float T0 = 288.15f; // Standart temperature at sea level (in K)
    float lapseRate = 0.0065f; // Standart lapse rate (in K/m)

    // calculate temperature in Celsius
    float TCelsius = T0 - (lapseRate * altitudeMeters);

    // Convert to kelvin and return
    float TKelvin = TCelsius + 273.15f;
    return TKelvin;
}

// Function to calculate the pressure at a given altitude
float getPressureAtAltitude(float altitudeMeters){
    const float P0 = 101325; // Pressure at sea level in Pascals
    const float L = 0.0065f; // Lapse rate in K/m
    const float h = altitudeMeters; // meters above sea level
    const float T0 = 288.15f; // Temperature at sea level in Kelvin
    const float g0 = 9.80665f; // Gravity
    const float M = 0.0289644f; // Molar mass of Earth's air in kg/mol
    const float R = 8.3144598f; // Ideal gas constant in J/(mol·K)

    const float exponent = (g0 * M) / (R * L); // Calculate the exponent for the pressure equation
    const float bracket = (1 - ((L * h)/T0)); // Calculate the bracketed term for the pressure equation

    const float P = P0 * powf(bracket, exponent); // Calculate the pressure at the given altitude

    return P; // Return the calculated pressure
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

Vector3 vectorCross(Vector3 a, Vector3 b) {
    return (Vector3){
        a.y * b.z - a.z * b.y,
        a.z * b.x - a.x * b.z,
        a.x * b.y - a.y * b.x
    };
}

Vector3 getUpVector(AircraftState *aircraft) {
    float yaw   = convertDegToRadians(aircraft->yaw);
    float pitch = convertDegToRadians(aircraft->pitch);
    float roll  = convertDegToRadians(aircraft->roll);
    
    float cosYaw   = cosf(yaw),   sinYaw   = sinf(yaw);
    float cosPitch = cosf(pitch), sinPitch = sinf(pitch);
    float cosRoll  = cosf(roll),  sinRoll  = sinf(roll);
    
    Vector3 up;
    up.x = -cosYaw * sinRoll - sinYaw * sinPitch * cosRoll;
    up.y = cosPitch * cosRoll;
    up.z = -sinYaw * sinRoll + cosYaw * sinPitch * cosRoll;
    return up;
}

Vector3 getUnitVectorFromVector(Vector3 vector) {
    float magnitude = calculateMagnitude(vector.x, vector.y, vector.z);
    if (magnitude < 0.0001f) { // Prevent division by zero
        return (Vector3){0.0f, 0.0f, 0.0f}; 
    }
    return (Vector3){ vector.x / magnitude, 
                      vector.y / magnitude, 
                      vector.z / magnitude };
}

float convertRadiansToDeg(float radians){
    return radians * (180.0f / PI);
}

float convertKmhToMs(float kmh){
    return kmh / 3.6f;
}

float convertMsToKmh(float ms){
    return ms * 3.6f;
}

float calculateSpeedOfSound(float altitude){
    float gamma = 1.4f; // ratio of specific heats for air, aprox 1.4 for dry hair
    float R = 287.05f; // specific gas constant for dry air in J/(kg·K)
    float T = getTemperatureKelvin(altitude); // temperature in kelvin

    return sqrtf(gamma * R * T);
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
    float AoA = calculateAoA(aircraft);
    float liftCoefficient = calculateLiftCoefficient(AoA);
    Vector3 liftForce = computeLiftForceComponents(aircraft, wingArea, liftCoefficient);

    // --- WIND & RELATIVE VELOCITY ---
    // Calculate wind vector based on current altitude and simulation time
    Vector3 wind = getWindVector(aircraft->y, simulationTime);
    // Compute relative velocity (aircraft velocity relative to the moving air)
    Vector3 relativeVelocity = {
        aircraft->vx - wind.x,
        aircraft->vy - wind.y,
        aircraft->vz - wind.z
    };

    // --- DRAG ---
    // Calculate aspect ratio using wing span from aircraftData 
    float aspectRatio = (aircraftData->wingSpan * aircraftData->wingSpan) / wingArea;
    float inducedDrag = calculateInducedDrag(liftCoefficient, aspectRatio);
    float dragCoefficient = calculateTotalDragCoefficient(inducedDrag);
    // Use the magnitude of the relative velocity for drag force calculation
    float relativeSpeed = calculateMagnitude(relativeVelocity.x, relativeVelocity.y, relativeVelocity.z);
    float dragForceMag = calculateDragForce(dragCoefficient, getAirDensity(aircraft->y), relativeSpeed, wingArea);
    // Compute unit vector from the relative velocity vector
    Vector3 relativeVelocityUnit = getUnitVectorFromVector(relativeVelocity);
    Vector3 dragForce = {
        -relativeVelocityUnit.x * dragForceMag,
        -relativeVelocityUnit.y * dragForceMag,
        -relativeVelocityUnit.z * dragForceMag
    };

    // --- THRUST ---
    float thrustMagnitude = calculateThrust(
        aircraftData->thrust, 
        aircraftData->afterburnerThrust, 
        aircraft, 
        aircraftData->maxSpeed, 
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
    updateVelocity(aircraft, deltaTime);
}