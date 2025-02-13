#include "physics.h"

AltitudeAirDensity airDensityTable[] = {
    {0.0, 1.225}, // altitude, meters
    {1000.0, 1.112},
    {2000.0, 1.007},
    {3000.0, 0.9093},
    {4000.0, 0.8194},
    {5000.0, 0.7364},
    {6000.0, 0.6614},
    {7000.0, 0.5930},
    {8000.0, 0.5321},
    {9000.0, 0.4788},
    {10000.0, 0.4335},
    {11000.0, 0.3955},
    {12000.0, 0.3643},
    {13000.0, 0.3394},
    {14000.0, 0.3190},
    {15000.0, 0.3024}
};

#define AIR_DENSITY_TABLE_SIZE (sizeof(airDensityTable) / sizeof(airDensityTable[0])) 

// Function to perform linear interpolation between two points
float interpolate(float lowerAlt, float upperAlt, float lowerDensity, float upperDensity, float targetAltitude) {
    float fraction = (targetAltitude - lowerAlt) / (upperAlt - lowerAlt);
    return lowerDensity + fraction * (upperDensity - lowerDensity);
}

// Function to get the air density at a given altitude
float getAirDensity(float altitude) {
    // If the altitude is below the first entry, return the first value
    if (altitude <= airDensityTable[0].altitude) {
        return airDensityTable[0].airDensity;
    }
    
    // If the altitude is above the last entry, return the last value
    if (altitude >= airDensityTable[AIR_DENSITY_TABLE_SIZE - 1].altitude) {
        return airDensityTable[AIR_DENSITY_TABLE_SIZE - 1].airDensity;
    }

    // Iterate through the table to find the two points for interpolation
    for (size_t i = 1; i < AIR_DENSITY_TABLE_SIZE; i++) {
        if (altitude < airDensityTable[i].altitude) {
            // Perform linear interpolation
            return interpolate(airDensityTable[i - 1].altitude, airDensityTable[i].altitude,
                               airDensityTable[i - 1].airDensity, airDensityTable[i].airDensity, altitude);
        }
    }

    // Default return (should not be reached)
    return 0.0;
}

/*
    #########################################################
    #                                                       #
    #               AoA COMPUTING FUNCTIONS                 #
    #                                                       #
    #########################################################

*/

float convertDegToRadians(float degrees){
    return degrees * (PI / 180.0);
}

// Function to calculate the longitudinal axis vector based on pitch and yaw
LAV calculateLAV(AircraftState *aircraft) {
    float pitchRad = convertDegToRadians(aircraft->pitch);  // Convert pitch to radians
    float yawRad = convertDegToRadians(aircraft->yaw);      // Convert yaw to radians

    LAV lav;
    lav.lx = cos(pitchRad) * cos(yawRad);  // X component of LAV
    lav.ly = cos(pitchRad) * sin(yawRad);  // Y component of LAV
    lav.lz = sin(pitchRad);                // Z component of LAV
    return lav;
}

// Function to calculate the magnitude of a vector
float calculateMagnitude(float x, float y, float z) {
    return sqrt(x * x + y * y + z * z);
}

// Function to calculate the dot product between velocity and longitudinal axis vectors
float calculateDotProduct(LAV lav, float vx, float vy, float vz) {
    return vx * lav.lx + vy * lav.ly + vz * lav.lz;
}

// Function to calculate the Angle of Attack (AoA)
float calculateAoA(AircraftState *aircraft) {
    float horizontalSpeed = sqrtf(aircraft->vx * aircraft->vx + aircraft->vz * aircraft->vz);
    float totalSpeed = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz);
    if (totalSpeed < 1e-6f) { // avoid division by zero for very slow speeds
        return 0.0f;
    }
    // Use atan2 so that a descending flight (negative vy) gives a positive AoA.
    return atan2f(-aircraft->vy, horizontalSpeed);
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
    const float cl_alpha = 2 * PI; // Lift curve slope (per radian)
    return cl0 + cl_alpha * AoA;
}

float calculateLift(AircraftState *aircraft) {
    // L = 0.5 * rho * V^2 * S * C_L
    float V = calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz);
    float rho = getAirDensity(aircraft->y);  // altitude in meters
    float S = 24.15; // wing area for J29F (static value)
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
    rotated.x = V.x * cos(theta) + cross.x * sin(theta) + K.x * dot * (1 - cos(theta));
    rotated.y = V.y * cos(theta) + cross.y * sin(theta) + K.y * dot * (1 - cos(theta));
    rotated.z = V.z * cos(theta) + cross.z * sin(theta) + K.z * dot * (1 - cos(theta));

    return rotated;
}

Vector3 getRightWingDirection(AircraftState *aircraft){
    Vector3 wingRight;

    // Base right-wing direction (ignoring roll)
    wingRight.x = cos(aircraft->yaw);
    wingRight.y = 0;
    wingRight.z = -sin(aircraft->yaw);

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

float calculateDragForce(float dragCoefficient, float airDensity, AircraftState *aircraft, float wingArea) {
    float speedSquared = aircraft->vx * aircraft->vx + 
                         aircraft->vy * aircraft->vy + 
                         aircraft->vz * aircraft->vz;
    if (speedSquared < 0.0001f) return 0.0f; // Avoid division by zero

    return 0.5f * dragCoefficient * airDensity * wingArea * speedSquared;
}


// ----- THRUST -----
float calculateThrust(float thrust, float afterburnerThrust, AircraftState *aircraft, float maxSpeed, int percentControl){
    float usedThrust;
    bool afterBurnerOn;

    if (percentControl > 100){
        afterBurnerOn = true;
        percentControl = 100; // set to 100 so in later calculations its not > 100
    }else{
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

    float calculatedThrust = usedThrust * (airDensityAtCurrentAltitude/airDensityAtSeaLevel) * (1 + 0.2 * (currentSpeed/maxSpeed));

    calculatedThrust = (percentControl/100) * calculatedThrust; // apply the user control to the thrust

    return calculatedThrust;
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

float convertRadiansToDeg(float radians){
    return radians * (180.0 / PI);
}

float convertKmhToMs(float kmh){
    return kmh / 3.6;
}

float convertMsToKmh(float ms){
    return ms * 3.6;
}

float convertMsToMach(float ms){
    return ms / 343;
}

/*
    #########################################################
    #                                                       #
    #                   PHYSICS UPDATE                      #
    #                                                       #
    #########################################################
*/

// function to update the physics of the aircraft
void updatePhysics(AircraftState *aircraft, float deltaTime, AircraftData *aircraftData) {
    // Use aircraftData values for mass and wing area
    float mass = aircraftData->mass;
    float wingArea = aircraftData->wingArea;

    // --- GRAVITY ---
    Vector3 gravityForce = { 0, -GRAVITY * mass, 0 };

    // --- LIFT ---
    float AoA = calculateAoA(aircraft);
    float liftCoefficient = calculateLiftCoefficient(AoA);
    Vector3 liftForce = computeLiftForceComponents(aircraft, wingArea, liftCoefficient);

    // --- DRAG ---
    // Calculate aspect ratio using wing span from aircraftData (assuming wingSpan is provided)
    float aspectRatio = (aircraftData->wingSpan * aircraftData->wingSpan) / wingArea;
    float inducedDrag = calculateInducedDrag(liftCoefficient, aspectRatio);
    float dragCoefficient = calculateTotalDragCoefficient(inducedDrag);
    float dragForceMag = calculateDragForce(dragCoefficient, getAirDensity(aircraft->y), aircraft, wingArea);
    Vector3 velocityUnit = getUnitVector(aircraft);
    Vector3 dragForce = {
        -velocityUnit.x * dragForceMag,
        -velocityUnit.y * dragForceMag,
        -velocityUnit.z * dragForceMag
    };

    // --- THRUST ---
    // Pass in aircraftData fields for thrust values and maxSpeed
    float thrustMagnitude = calculateThrust(
        aircraftData->thrust, 
        aircraftData->afterburnerThrust, 
        aircraft, 
        aircraftData->maxSpeed, 
        110  // Using a control percentage value (adjust as needed)
    );
    Vector3 thrustForce = {
        thrustMagnitude * cos(aircraft->pitch) * cos(aircraft->yaw),
        thrustMagnitude * sin(aircraft->pitch),
        thrustMagnitude * cos(aircraft->pitch) * sin(aircraft->yaw)
    };

    // --- VERTICAL DAMPING ---
    const float VERTICAL_DAMPING = 2000.0f;  // Adjust this constant if necessary
    Vector3 dampingForce = { 0, -VERTICAL_DAMPING * aircraft->vy, 0 };

    // --- SUM ALL FORCES ---
    Vector3 netForce = {
        gravityForce.x + liftForce.x + dragForce.x + thrustForce.x + dampingForce.x,
        gravityForce.y + liftForce.y + dragForce.y + thrustForce.y + dampingForce.y,
        gravityForce.z + liftForce.z + dragForce.z + thrustForce.z + dampingForce.z
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
}