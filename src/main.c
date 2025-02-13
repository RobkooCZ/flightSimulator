#include "utils.h"
#include "aircraft.h"
#include "physics.h"
#include "textRenderer.h"
#include "menu.h"
#include "aircraftData.h"
#include <stdlib.h>

#define FILE_PATH "data/aircraftData.txt"

#ifdef _WIN32 // Windows
    #define CLEAR "cls"
#else // Linux
    #define CLEAR "clear"
#endif

int main() {
    long startTime, elapsedTime, previousTime;
    float deltaTime; // for precision
    float fps;
    AircraftState aircraft;

    initAircraft(&aircraft);

    previousTime = getTimeMicroseconds();

    system(CLEAR); // clear screen

    // ----- SELECT AIRCRAFT -----

    Aircraft aircraftList[MAX_AIRCRAFT];
    int aircraftCount;

    if (!loadAircraftNames(FILE_PATH, aircraftList, &aircraftCount)) {
        return 1; // Exit if file could not be loaded
    }

    int selectedIndex = selectAircraft(aircraftList, aircraftCount);

    // Load the selected aircraft data

    AircraftData aircraftData;
    getAircraftDataByName(FILE_PATH, aircraftList[selectedIndex].name, &aircraftData);


    system(CLEAR); // clear screen

    // ----- MAIN GAME LOOP -----
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
        updatePhysics(&aircraft, deltaTime, &aircraftData);

        // Update aircraft state (position, orientation)
        updateAircraftState(&aircraft, deltaTime);

        // // 3. Render (if using graphics, text for now)
        printInfo(&aircraft, &aircraftData, fps);

        // 4. Frame rate control
        elapsedTime = getTimeMicroseconds() - startTime;
        if (elapsedTime < FRAME_TIME_MICROSECONDS) {
            sleepMicroseconds(FRAME_TIME_MICROSECONDS - elapsedTime);
        }
    }

    return 0;
}