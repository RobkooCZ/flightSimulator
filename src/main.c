#include "controls.h"
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
    #include <windows.h>
#else // Linux
    #define CLEAR "clear"
#endif

int main(void) {
    long startTime, elapsedTime, previousTime;
    float deltaTime; // for precision
    float fps;
    AircraftState aircraft;

    #ifdef _WIN32 // for printing out "°" on windows
        SetConsoleOutputCP(CP_UTF8);
    #endif

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

    if (aircraftData.afterburnerThrust == 0){ // e.g. no afterburner
        aircraft.hasAfterburner = false;
    }
    else{ // has afterburner
        aircraft.hasAfterburner = true;
    }

    system(CLEAR); // clear screen

    startControls(); // initialize controls, start a thread for real time input

    // ----- MAIN GAME LOOP -----
    while (1) {
        startTime = getTimeMicroseconds();

        // Calculate delta time
        deltaTime = (float)((double)(startTime - previousTime) / 1000000.0);
        previousTime = startTime;

        // Calculate frames per second
        fps = 1.0f / deltaTime;

        // PSEUDOCODE
        // // 1. Process input (e.g., throttle, controls)
        AircraftControls *controls = getControls(); // get controls
        aircraft.yaw = controls->yaw;
        aircraft.pitch = controls->pitch;
        aircraft.roll = controls->roll;
        aircraft.controls.throttle = controls->throttle;
        if (aircraft.controls.throttle > 1) aircraft.controls.afterburner = true;
        else aircraft.controls.afterburner = false;

        // adjustValues(key, aircraft.controls, &aircraft);

        // // 2. Update physics (velocity, acceleration, forces)
        updatePhysics(&aircraft, deltaTime, &aircraftData);
        
        // Update aircraft state (position, orientation)
        updateAircraftState(&aircraft, deltaTime);

        // // 3. Render (text for now)
        printInfo(&aircraft, &aircraftData, fps);

        // 4. Frame rate control
        elapsedTime = getTimeMicroseconds() - startTime;
        if (elapsedTime < FRAME_TIME_MICROSECONDS) {
            sleepMicroseconds(FRAME_TIME_MICROSECONDS - elapsedTime);
        }
    }

    return 0;
}