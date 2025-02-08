#include "utils.h"
#include "aircraft.h"
#include "physics.h"
#include "textRenderer.h"
#include <stdlib.h>

int main() {
    long startTime, elapsedTime, previousTime;
    float deltaTime; // for precision
    float fps;
    AircraftState aircraft;

    initAircraft(&aircraft);

    previousTime = getTimeMicroseconds();

    #ifdef _WIN32 // Windows
        system("cls");
    #else // Linux
        system("clear");
    #endif

    while (1) {
        startTime = getTimeMicroseconds();

        // Calculate delta time
        deltaTime = (startTime - previousTime) / 1000000.0;
        previousTime = startTime;

        // Calculate frames per second
        fps = 1.0 / deltaTime;

        // PSEUDOCODE
        // // 1. Process input (e.g., throttle, controls)
        // handleInput(deltaTime);

        // // 2. Update physics (velocity, acceleration, forces)
        updatePhysics(&aircraft, deltaTime);

        // Update aircraft state (position, orientation)
        updateAircraftState(&aircraft, deltaTime);

        // // 3. Render (if using graphics, text for now)
        printInfo(&aircraft, fps);

        // 4. Frame rate control
        elapsedTime = getTimeMicroseconds() - startTime;
        if (elapsedTime < FRAME_TIME_MICROSECONDS) {
            sleepMicroseconds(FRAME_TIME_MICROSECONDS - elapsedTime);
        }
    }

    return 0;
}