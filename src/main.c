/**
 * @file main.c
 * @brief This file contains the main entry point for the flight simulator application.
 *
 * This file is responsible for initializing the flight simulator, setting up necessary
 * resources, and running the main simulation loop.
 *
 */

// Include all the header files
#include "controls.h"
#include "utils.h"
#include "aircraft.h"
#include "physics.h"
#include "2Drenderer.h"
#include "menu.h"
#include "aircraftData.h"

// Include standard libraries
#include <stdio.h>
#include <stdlib.h>

// Include the SDL2 header, tell SDL to not declare main as SDL_main
#define SDL_MAIN_HANDLED
#include <SDL2/SDL.h>

#define FILE_PATH "data/aircraftData.txt" // Define file path for aircraft data

#ifdef _WIN32
    #define CLEAR "cls" // Define clear command for Windows
#else
    #define CLEAR "clear" // Define clear command for Unix-based systems
#endif

// global var to check if the plane is crashed
static int crashed = 0;

// Prototype for message function
void message(void);

// Destructor attribute to call message function after main ends
__attribute__((destructor))
void message(void) {
    printf("\n\n");
    printf("***********************************************\n");
    printf("*                                             *\n");
    if (crashed) {
        printf("*           GAME OVER, PLANE CRASHED          *\n");
    }
    else {
        printf("*             Thanks for playing!             *\n");
        printf("*             See you next time!              *\n");
    }
    printf("*                                             *\n");
    printf("***********************************************\n");
    printf("\n\n");
}

int main(int argc, char* argv[]) {
    // Ignore arguments (safer than letting them be)
    (void)argc;
    (void)argv;

    long startTime, elapsedTime, previousTime; // Time tracking variables
    float deltaTime; // Delta time calculation
    float fps; // Frames per second calculation
    AircraftState aircraft;
    float simulationTime = 0.0f; // Simulation time

    // ----- SELECT AIRCRAFT -----
    Aircraft aircraftList[MAX_AIRCRAFT]; // Array for aircraft names
    int aircraftCount; // Count of aircraft

    if (!loadAircraftNames(FILE_PATH, aircraftList, &aircraftCount)) { // Load names from file
        return 1; // Return error if loading fails
    }

    int selectedIndex = selectAircraft(aircraftList, aircraftCount); // User selects an aircraft

    // Load selected aircraft data
    AircraftData aircraftData; // Structure for aircraft data
    getAircraftDataByName(FILE_PATH, aircraftList[selectedIndex].name, &aircraftData); // Populate aircraftData
    maxFuelKgs = (float)aircraftData.fuelCapacity; // kgs

    // Initialize aircraft state using data from file
    initAircraft(&aircraft, &aircraftData); 
    aircraft.fuel = 150.0f; // test
    aircraft.hasAfterburner = (aircraftData.afterburnerThrust != 0); // Update afterburner flag

    previousTime = getTimeMicroseconds(); // Get initial time

    // Initialize SDL2 Text Renderer and input system
    initTextRenderer(); // Initialize text renderer
    startControls(); // Start input thread

    SDL_Event event; // Variable for SDL events
    int running = 1; // Main loop control

    system(CLEAR); // Clear console
    printf("===== Robkoo's Flight simulator debug console =====\n"); // Debug message

    // ----- MAIN GAME LOOP -----
    while (running) {
        startTime = getTimeMicroseconds(); // Get start time

        // if the plane is crashed, exit the loop
        if (aircraft.y <= 0.0f){
            running = 0; // crashed
            crashed = 1;
        }

        // Event handling (for input)
        while (SDL_PollEvent(&event)) { // Poll for SDL events
            if (event.type == SDL_QUIT) { // Check for quit event
                running = 0; // Set running to 0 to exit loop
            }
            if (event.type == SDL_KEYDOWN) { // Check for key down event
                if (event.key.keysym.sym == SDLK_ESCAPE) { // Check for escape key
                    running = 0; // Set running to 0 to exit loop
                }
                handleKeyEvents(&event); // Handle other key events
            }
        }

        // Calculate delta time
        deltaTime = (float)((double)(startTime - previousTime) / 1000000.0); // Calculate time difference in seconds
        simulationTime += deltaTime; // Update simulation time
        previousTime = startTime; // Update previous time

        // Calculate FPS
        fps = 1.0f / deltaTime; // Calculate frames per second

        // Get controls
        AircraftControls *controls = getControls(); // Get current controls
        aircraft.yaw = controls->yaw; // Update aircraft yaw
        aircraft.pitch = controls->pitch; // Update aircraft pitch
        aircraft.roll = controls->roll; // Update aircraft roll
        aircraft.controls.throttle = controls->throttle; // Update aircraft throttle
        aircraft.controls.afterburner = (aircraft.controls.throttle > 1); // Update afterburner status

        // Update physics
        updatePhysics(&aircraft, deltaTime, simulationTime, &aircraftData); // Update aircraft physics
        updateAircraftState(&aircraft, deltaTime); // Update aircraft state

        // Render aircraft data using SDL2
        renderFlightInfo(&aircraft, &aircraftData, fps, simulationTime); // Render flight information

        // Frame rate control
        elapsedTime = getTimeMicroseconds() - startTime; // Calculate elapsed time
        if (elapsedTime < FRAME_TIME_MICROSECONDS) { // Check if frame time is less than desired frame time
            sleepMicroseconds(FRAME_TIME_MICROSECONDS - elapsedTime); // Sleep for remaining time
        }
    }

    // Cleanup
    destroyTextRenderer(); // Destroy text renderer

    return 0; // Return success
}

#ifdef _WIN32
    #include <windows.h> // Include Windows header
    
    int WINAPI WinMain(HINSTANCE hInstance, HINSTANCE hPrevInstance, LPSTR lpCmdLine, int nCmdShow) {
        // Cast unused parameters to void
        (void)hInstance;
        (void)hPrevInstance;
        (void)lpCmdLine;
        (void)nCmdShow;

        // Convert command-line string to argc/argv format
        int argc; // Variable for argument count
        LPWSTR* argvW = CommandLineToArgvW(GetCommandLineW(), &argc); // Convert command line to wide string arguments
        if (argvW == NULL) { // Check for conversion failure
            return 1; // Return error
        }

        char** argv = (char**)malloc((long long unsigned int)argc * sizeof(char*)); // Allocate memory for arguments
        if (argv == NULL) { // Check for allocation failure
            LocalFree(argvW); // Free wide string arguments
            return 1; // Return error
        }

        for (int i = 0; i < argc; i++) { // Loop through arguments
            size_t size = wcslen(argvW[i]) + 1; // Get size of wide string argument
            argv[i] = (char*)malloc(size); // Allocate memory for argument
            if (argv[i] == NULL) { // Check for allocation failure
                for (int j = 0; j < i; j++) { // Free previously allocated arguments
                    free(argv[j]);
                }
                free(argv); // Free argument array
                LocalFree(argvW); // Free wide string arguments
                return 1; // Return error
            }
            wcstombs(argv[i], argvW[i], size); // Convert wide string to multibyte string
        }

        // Call main()
        int result = main(argc, argv); // Call main function with converted arguments

        // Free memory allocated by CommandLineToArgvW and conversion
        for (int i = 0; i < argc; i++) { // Free each argument
            free(argv[i]);
        }
        free(argv); // Free argument array
        LocalFree(argvW); // Free wide string arguments

        return result; // Return result of main function
    }
#endif