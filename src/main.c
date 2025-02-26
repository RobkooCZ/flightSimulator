#include "controls.h"
#include "utils.h"
#include "aircraft.h"
#include "physics.h"
#include "textRenderer.h"
#include "menu.h"
#include "aircraftData.h"
#include <stdlib.h>

#define SDL_MAIN_HANDLED // for windows so SDL doesnt need a separate main (HAS to be before including SDL2/SDL.h)
#include <SDL2/SDL.h>

#define FILE_PATH "data/aircraftData.txt"

// prototype
void message(void);

// do this after main ends
__attribute__((destructor))
void message(void){
    printf("\n\n");
    printf("***********************************************\n");
    printf("*                                             *\n");
    printf("*             Thanks for playing!             *\n");
    printf("*             See you next time!              *\n");
    printf("*                                             *\n");
    printf("***********************************************\n");
    printf("\n\n");
}

int main(int argc, char* argv[]) {
    // Ignore arguments (safer than letting them be)
    (void)argc;
    (void)argv;

    long startTime, elapsedTime, previousTime;
    float deltaTime;
    float fps;
    AircraftState aircraft;
    float simulationTime = 0.0f;

    initAircraft(&aircraft);

    previousTime = getTimeMicroseconds();

    // ----- SELECT AIRCRAFT -----
    Aircraft aircraftList[MAX_AIRCRAFT];
    int aircraftCount;

    if (!loadAircraftNames(FILE_PATH, aircraftList, &aircraftCount)) {
        return 1;
    }

    int selectedIndex = selectAircraft(aircraftList, aircraftCount);

    // Load selected aircraft data
    AircraftData aircraftData;
    getAircraftDataByName(FILE_PATH, aircraftList[selectedIndex].name, &aircraftData);

    aircraft.hasAfterburner = (aircraftData.afterburnerThrust != 0);

    // Initialize SDL2 Text Renderer
    initTextRenderer();

    startControls(); // Start input thread

    SDL_Event event;
    int running = 1;

    system("clear");
    printf("Flight simulator debug console\n");

    // ----- MAIN GAME LOOP -----
    while (running) {
        startTime = getTimeMicroseconds();

        // Event handling (for input)
        while (SDL_PollEvent(&event)) {
            if (event.type == SDL_QUIT) {
                running = 0;
            }
            if (event.type == SDL_KEYDOWN) {
                if (event.key.keysym.sym == SDLK_ESCAPE) {
                    running = 0;
                }
                handleKeyEvents(&event); // Handles key events for controls
            }
        }

        // Calculate delta time
        deltaTime = (float)((double)(startTime - previousTime) / 1000000.0);
        simulationTime += deltaTime;
        previousTime = startTime;

        // Calculate FPS
        fps = 1.0f / deltaTime;

        // Get controls
        AircraftControls *controls = getControls();
        aircraft.yaw = controls->yaw;
        aircraft.pitch = controls->pitch;
        aircraft.roll = controls->roll;
        aircraft.controls.throttle = controls->throttle;
        aircraft.controls.afterburner = (aircraft.controls.throttle > 1);

        // Update physics
        updatePhysics(&aircraft, deltaTime, simulationTime, &aircraftData);
        updateAircraftState(&aircraft, deltaTime);

        // Render aircraft data using SDL2
        renderFlightInfo(&aircraft, &aircraftData, fps, simulationTime);

        // Frame rate control
        elapsedTime = getTimeMicroseconds() - startTime;
        if (elapsedTime < FRAME_TIME_MICROSECONDS) {
            sleepMicroseconds(FRAME_TIME_MICROSECONDS - elapsedTime);
        }
    }

    // Cleanup
    destroyTextRenderer();

    return 0;
}

#ifdef _WIN32
    #include <windows.h>
    
    int WINAPI WinMain(HINSTANCE hInstance, HINSTANCE hPrevInstance, LPSTR lpCmdLine, int nCmdShow) {
        // Cast unused parameters to void
        (void)hInstance;
        (void)hPrevInstance;
        (void)lpCmdLine;
        (void)nCmdShow;

        // Convert command-line string to argc/argv format
        int argc;
        LPWSTR* argvW = CommandLineToArgvW(GetCommandLineW(), &argc);
        if (argvW == NULL) {
            return 1;
        }

        char** argv = (char**)malloc((long long unsigned int)argc * sizeof(char*));
        if (argv == NULL) {
            LocalFree(argvW);
            return 1;
        }

        for (int i = 0; i < argc; i++) {
            size_t size = wcslen(argvW[i]) + 1;
            argv[i] = (char*)malloc(size);
            if (argv[i] == NULL) {
                for (int j = 0; j < i; j++) {
                    free(argv[j]);
                }
                free(argv);
                LocalFree(argvW);
                return 1;
            }
            wcstombs(argv[i], argvW[i], size);
        }

        // Call main()
        int result = main(argc, argv);

        // Free memory allocated by CommandLineToArgvW and conversion
        for (int i = 0; i < argc; i++) {
            free(argv[i]);
        }
        free(argv);
        LocalFree(argvW);

        return result;
    }
#endif