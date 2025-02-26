#include "physics.h"
#include "controls.h"
#include "weather.h"

#include <math.h>
#include <stdbool.h>
#include <stdio.h>

const int PHYSICS_DEBUG = 0; // change this to 1 for printing out physics debug info

/*
    #########################################################
    #                                                       #
    #                      AIR DENSITY                      #
    #                                                       #
    #########################################################
*/

float getTropopause(void){
    float deltaT_isa = 0.0f; // for earth's atmosphere
    float h_top = 11000.0f + 1000.0f * (deltaT_isa / 6.5f);

    return h_top; // earth atmosphere
}

// Function to get the air density at a given altitudecl
float getAirDensity(float altitude) {
    float tropopause = getTropopause();
    const float R = 287.04f;

    // If the plane is below or at the tropopause
    if (altitude <= tropopause){
        const float Kt = -0.0065f;
        const float exponent = -((GRAVITY)/(Kt * R)) - 1;
        const float T = getTemperatureKelvin(altitude);
        const float T0 = 288.15f; // sea level temperature in Kelvin
        const float rho_0 = 1.225f; // sea level air density in kg/m^3

        return rho_0 * powf((T / T0), exponent);
    }
    // If the plane is above the tropopause
    else {
        const float rho_top = 0.3639f;
        const float T_trop = 216.65f;
        return rho_top * expf(-((GRAVITY)/(R * T_trop)) * (altitude - tropopause));
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
    const float treshold = 1e-6f; // avoid division by zero

    if (fabsf(aircraft->vx) < treshold) {
        return 0.0f; // avoid division by zero
    }

    float gamma = atanf(aircraft->vy / aircraft->vx);

    float aoa = aircraft->pitch - gamma;

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
    float TAS = calculateTAS(aircraft);
    return asinf(aircraft->vz/TAS);
}

float calculateLiftCoefficient(float mass, AircraftState *aircraft, float wingArea){
    const float airDensity = getAirDensity(aircraft->y);
    float TAS = calculateTAS(aircraft);
    float numerator = mass * GRAVITY;
    float denumerator = 0.5f * airDensity * powf(TAS, 2) * wingArea;

    if (convertRadiansToDeg(fabsf(aircraft->yaw)) > 5.0f && convertRadiansToDeg(fabsf(aircraft->pitch)) > 0.1f){ // plane is turning
        denumerator *= cosf(convertRadiansToDeg(aircraft->roll));
        return numerator / denumerator; // Cl
    }
    else{ // plane isnt turning
        const float tolerance = 1e-6f;
        if (fabs(aircraft->vy) < tolerance){ // aircraft isnt climbing or descending
            return numerator / denumerator; // Cl
        }
        else { // aircraft is climbing or descending
            numerator *= cosf(getFlightPathAngle(aircraft));
            return numerator / denumerator; // Cl
        }
    }

    return 0.0f;
}

float calculateLift(AircraftState *aircraft, float wingArea, float mass) {
    // L = 0.5 * rho * V^2 * S * C_L
    float V = calculateTAS(aircraft);
    float rho = getAirDensity(aircraft->y);  // altitude in meters
    float S = wingArea; // wing area for the plane (in m^2)
    float C_L = calculateLiftCoefficient(mass, aircraft, wingArea);

    return 0.5f * rho * V * V * S * C_L;
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
    };
}   

/*
    #########################################################
    #                                                       #
    #               DRAG CALCULATION FUNCTIONS              #
    #                                                       #
    #########################################################
*/

// Function to calculate the aspect ratio of the wing
float calculateAspectRatio(float wingspan, float wingArea) {
    return wingspan * wingspan / wingArea; // Aspect ratio formula: wingspan^2 / wingArea
}

float calculateDragCoefficient(float speed, float maxSpeed, float altitude, float C_d0){
    // Constants for the aircraft (can be adjusted based on the aircraft's characteristics)
    const float alpha = 0.1f; // Constant for drag rise in transonic region
    const float kw = 30.0f;   // Drag rise constant for supersonic region
    const float Md = 0.89f;   // Drag divergence Mach number

    // Calculate Mach number
    float mach = speed / calculateSpeedOfSound(altitude); // Speed of sound based on altitude

    // Subsonic flight (Mach < 0.8)
    if (mach < 0.8f) {
        return C_d0 + 0.05f * powf(speed / maxSpeed, 2); // Subsonic drag coefficient formula
    }
    // Transonic flight (Mach ~ 0.8 to 1.2)
    else if (mach < 1.2f) {
        return C_d0 + 0.05f * powf(speed / maxSpeed, 2) + alpha * powf((mach - 1), 2); // Transonic drag coefficient
    }
    // Supersonic flight (Mach > 1.2)
    else {
        return C_d0 + kw * powf((mach - Md), 2); // Supersonic drag coefficient
    }
}

float calculateParasiticDrag(float C_d, float airDensity, float speed, float wingArea) {
    return 0.5f * C_d * airDensity * powf(speed, 2) * wingArea; // Parasitic drag formula
}

// Function to calculate induced drag
float calculateInducedDrag(float liftCoefficient, float aspectRatio, float airDensity, float wingArea, float speed) {
    if (speed < 0.1f) return 0.0f; // Prevent divide-by-zero issues for very low speeds

    return 0.5f * airDensity * powf(speed, 2) * wingArea * ((liftCoefficient * liftCoefficient) / (PI * aspectRatio * OEF)); // Induced drag formula
}


float calculateDragDivergenceAroundMach(float speed, AircraftState *aircraft){
    // get current mach speed
    float mach = speed / calculateSpeedOfSound(aircraft->y);

    // set k_w and M_d 
    const float kw = 30.0f; // rough estimate for nearly supersonic aircraft (j29f, j32b)
    const float Md = 0.89f; // rough estimate for nearly supersonic aircraft (j29f, j32b)

    // calculate the drag divergence 
    float Cdw = 0;
    if (mach > Md) Cdw = C_D0 * kw * powf((mach - Md), 2); // if mach > Md, calculate adidtional drag

    return Cdw;
}

float calculateTotalDrag(float *parasiticDrag, float *inducedDrag, float *waveDrag, float *relativeSpeed, Vector3 *relativeVelocity, float simulationTime, AircraftState *aircraft, AircraftData *data){
    // --- WIND & RELATIVE VELOCITY ---
    Vector3 wind = getWindVector(aircraft->y, simulationTime);
    float tas = calculateTAS(aircraft);

    // convert TAS into a velocity vector
    Vector3 tasVector = {
        tas * cosf(aircraft->yaw) * cosf(aircraft->pitch),
        tas * sinf(aircraft->pitch),
        tas * cosf(aircraft->pitch) * sinf(aircraft->yaw)
    };

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

    // --- DRAG ---
    float aspectRatio = calculateAspectRatio(data->wingSpan, data->wingArea);
    float airDensity = getAirDensity(aircraft->y);
    float relSpeed = calculateMagnitude(relVelocity.x, relVelocity.y, relVelocity.z);

    if (relativeSpeed != NULL) {
        *relativeSpeed = relSpeed;
    }

    float liftCoefficient = calculateLiftCoefficient(data->mass, aircraft, data->wingArea);

    // Calculate parasitic and induced drag
    float C_d = calculateDragCoefficient(relSpeed, convertKmhToMs(data->maxSpeed), aircraft->y, C_D0);
    float parasiticDragValue = calculateParasiticDrag(C_d, airDensity, relSpeed, data->wingArea);
    float inducedDragValue = calculateInducedDrag(liftCoefficient, aspectRatio, airDensity, data->wingArea, relSpeed);
    float waveDragValue = calculateDragDivergenceAroundMach(tas, aircraft);

    if (parasiticDrag != NULL) {
        *parasiticDrag = parasiticDragValue;
    }
    if (inducedDrag != NULL) {
        *inducedDrag = inducedDragValue;
    }
    if (waveDrag != NULL) {
        *waveDrag = waveDragValue;
    }

    return parasiticDragValue + inducedDragValue + waveDragValue;
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

void updateVelocity(AircraftState *aircraft, float deltaTime, AircraftData *data, float simulationTime){    
    // UPDATE VX
    const float T = calculateThrust(data->thrust, data->afterburnerThrust, aircraft, (int)(aircraft->controls.throttle * 100));
    const float D = calculateTotalDrag(NULL, NULL, NULL, NULL, NULL, simulationTime, aircraft, data);
    
    const float ax = (T - D) / data->mass;
    aircraft->vx += ax * deltaTime;

    // UPDATE VY
    const float L = calculateLift(aircraft, data->wingArea, data->mass);
    const float W = GRAVITY * data->mass;

    const float ay = (L - W) / data->mass;
    aircraft->vy += ay * deltaTime;

    // COMPUTE FLIGHT PATH ANGLE (γ)
    const float gamma = atan2f(aircraft->vy, aircraft->vx);

    // COMPUTE ANGLE OF ATTACK (AoA)
    aircraft->AoA = aircraft->pitch - gamma;
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

    if (PHYSICS_DEBUG) {
        printf("PHYSICS DEBUG: Mass: %f, Wing Area: %f\n", mass, wingArea);
        printf("PHYSICS DEBUG: Gravity Force: x=%f, y=%f, z=%f\n", gravityForce.x, gravityForce.y, gravityForce.z);
        printf("PHYSICS DEBUG: Lift Coefficient: %f\n", liftCoefficient);
        printf("PHYSICS DEBUG: Lift Force: x=%f, y=%f, z=%f\n", liftForce.x, liftForce.y, liftForce.z);
        printf("PHYSICS DEBUG: Total Drag: %f, Relative Speed: %f\n", totalDrag, relativeSpeed);
        printf("PHYSICS DEBUG: Relative Velocity: x=%f, y=%f, z=%f\n", relativeVelocity.x, relativeVelocity.y, relativeVelocity.z);
        printf("PHYSICS DEBUG: Drag Force: x=%f, y=%f, z=%f\n", dragForce.x, dragForce.y, dragForce.z);
        printf("PHYSICS DEBUG: Thrust Arguments: thrust=%d, afterburnerThrust=%d, aircraft->y=%f, percentControl=%d\n",
            aircraftData->thrust, aircraftData->afterburnerThrust, aircraft->y, (int)(aircraft->controls.throttle * 100));
        printf("PHYSICS DEBUG: Thrust Magnitude: %f\n", thrustMagnitude);
        printf("PHYSICS DEBUG: Thrust Force: x=%f, y=%f, z=%f\n", thrustForce.x, thrustForce.y, thrustForce.z);
        printf("PHYSICS DEBUG: Net Force: x=%f, y=%f, z=%f\n", netForce.x, netForce.y, netForce.z);
        printf("PHYSICS DEBUG: Acceleration: x=%f, y=%f, z=%f\n", acceleration.x, acceleration.y, acceleration.z);
        printf("PHYSICS DEBUG: Updated Velocity: vx=%f, vy=%f, vz=%f\n", aircraft->vx, aircraft->vy, aircraft->vz);
    }
}