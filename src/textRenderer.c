#include "textRenderer.h"

#include <stdlib.h>
#include <math.h>
#include <stdio.h>

// SCREEN SIZE
#define SCREEN_WIDTH 1000
#define SCREEN_HEIGHT 600

// COLORS FOR RENDERER
#define WHITE 255, 255, 255, 255
#define RED 255, 0, 0, 255
#define GREEN 0, 255, 0, 255
#define BLUE 0, 0, 255, 255
#define BLACK 0, 0, 0, 255
#define YELLOW 255, 255, 0, 255
#define CYAN 0, 255, 255, 255
#define MAGENTA 255, 0, 255, 255
#define ORANGE 255, 165, 0, 255
#define PURPLE 128, 0, 128, 255
#define BROWN 165, 42, 42, 255
#define PINK 255, 192, 203, 255
#define GRAY 128, 128, 128, 255
#define LIGHT_GRAY 211, 211, 211, 255
#define DARK_GRAY 169, 169, 169, 255

// SDL2 variables
static SDL_Window *window = NULL;
static SDL_Renderer *renderer = NULL;
static TTF_Font *font = NULL;

// Positioning
#define LEFT_GAP 20
#define RIGHT_GAP 550 
#define TOP_GAP 20
#define GAP 25

static int debugMode = 0; // Toggle for debug mode
static int controlsMode = 1; // Toggle controls mode

void calculateFlightParameters(AircraftState *aircraft, AircraftData *aircraftData, 
                               float *engineOutput, float *totalDrag, float *netForce, 
                               float *relativeSpeed, Vector3 *relativeVelocity,
                               float *parasiticDrag, float *inducedDrag, float *shockwaveDrag,
                               float *dragCoefficient, float simulationTime){
    *engineOutput = calculateThrust(
        aircraftData->thrust, 
        aircraftData->afterburnerThrust, 
        aircraft, 
        (int)(aircraft->controls.throttle * 100)
    );

    // one function to do it all! quite proud of this one :3
    *totalDrag = calculateTotalDrag(parasiticDrag, inducedDrag, shockwaveDrag, relativeSpeed, relativeVelocity, simulationTime, aircraft, aircraftData);

    *dragCoefficient = calculateDragCoefficient(*relativeSpeed, convertKmhToMs(aircraftData->maxSpeed), aircraft->y, C_D0);

    *netForce = *engineOutput - *totalDrag;
}

void initTextRenderer(void) {
    SDL_Init(SDL_INIT_VIDEO);
    TTF_Init();

    window = SDL_CreateWindow("Flight Stats", SDL_WINDOWPOS_CENTERED, SDL_WINDOWPOS_CENTERED, SCREEN_WIDTH, SCREEN_HEIGHT, SDL_WINDOW_SHOWN);
    renderer = SDL_CreateRenderer(window, -1, SDL_RENDERER_ACCELERATED);

    font = TTF_OpenFont("fonts/Oswald/Oswald-Medium.ttf", 18);
    
    if (!font) {
        printf("Failed to load font!\n");
        exit(1);
    }
}

void renderText(const char *text, int x, int y, SDL_Color color) {
    SDL_Surface *surface = TTF_RenderUTF8_Solid(font, text, color);
    SDL_Texture *texture = SDL_CreateTextureFromSurface(renderer, surface);
    SDL_Rect rect = {x, y, surface->w, surface->h};

    SDL_RenderCopy(renderer, texture, NULL, &rect);
    SDL_FreeSurface(surface);
    SDL_DestroyTexture(texture);
}

void toggleModes(SDL_Event event) {
    if (event.type == SDL_KEYDOWN && event.key.keysym.sym == SDLK_p) {
        debugMode = !debugMode;
    }

    if (event.type == SDL_KEYDOWN && event.key.keysym.sym == SDLK_c) {
        controlsMode = !controlsMode;
    }
}

void renderFlightInfo(AircraftState *aircraft, AircraftData *aircraftData, float fps, float simulationTime) {
    char buffer[128];
    int y = TOP_GAP;
    int debugY = TOP_GAP;

    SDL_SetRenderDrawColor(renderer, BLACK);
    SDL_RenderClear(renderer);

    SDL_Color color;

    // LEFT SIDE (Main Info)
    // Aircraft Info
    color = (SDL_Color){YELLOW};
    sprintf(buffer, "============ %s INFO ============", aircraftData->name);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    color = (SDL_Color){WHITE};
    sprintf(buffer, "FPS: %.2f", fps);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    sprintf(buffer, "Simulated time: %.2fs", simulationTime);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    // Speed Info
    color = (SDL_Color){YELLOW};
    sprintf(buffer, "----- SPEED -----");
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    color = (SDL_Color){CYAN};
    sprintf(buffer, "IAS: %.2f km/h", convertMsToKmh(calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz)));
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    sprintf(buffer, "TAS: %.2f km/h", convertMsToKmh(calculateTAS(aircraft)));
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    sprintf(buffer, "Mach: %.2f", convertMsToMach(calculateTAS(aircraft), aircraft->y));
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    // Wind Info
    color = (SDL_Color){YELLOW};
    sprintf(buffer, "----- WIND -----");
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    color = (SDL_Color){CYAN};
    Vector3 wind = getWindVector(aircraft->y, simulationTime);
    sprintf(buffer, "Wind: X: %.1f m/s  Z: %.1f m/s", wind.x, wind.z);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    // Throttle Info
    color = (SDL_Color){YELLOW};
    sprintf(buffer, "----- THROTTLE -----");
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    if (!aircraft->controls.afterburner) {
        color = (SDL_Color){CYAN};
        sprintf(buffer, "Throttle: %.0f%%", aircraft->controls.throttle * 100);
    } else {
        color = (SDL_Color){RED};
        sprintf(buffer, "Throttle: WEP");
    }
    renderText(buffer, LEFT_GAP, y, color); y += GAP;


    color = (SDL_Color){CYAN};
    if (aircraft->controls.afterburner) {
        sprintf(buffer, "Expected engine output: %dN", aircraftData->afterburnerThrust);
    } else {
        sprintf(buffer, "Expected engine output: %.0fN", (float)aircraftData->thrust * aircraft->controls.throttle);
    }
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    // Flight parameter calculations
    float engineOutput, totalDrag, netForce, relativeSpeed, dragCoefficient;
    float parasiticDrag, inducedDrag, shockwaveDrag;
    Vector3 relativeVelocity;

    calculateFlightParameters(aircraft, aircraftData, 
                            &engineOutput, &totalDrag, &netForce, 
                            &relativeSpeed, &relativeVelocity, 
                            &parasiticDrag, &inducedDrag, &shockwaveDrag, 
                            &dragCoefficient, simulationTime);

    sprintf(buffer, "Actual engine output: %.0fN", engineOutput);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    sprintf(buffer, "Net force: %.0fN", netForce);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    // Position Info
    color = (SDL_Color){YELLOW};
    sprintf(buffer, "----- POSITION -----");
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    color = (SDL_Color){CYAN};
    sprintf(buffer, "X: %.2f", aircraft->x);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    sprintf(buffer, "Y: %.2f", aircraft->y);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    sprintf(buffer, "Z: %.2f", aircraft->z);
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    // Orientation Info
    color = (SDL_Color){YELLOW};
    sprintf(buffer, "----- ORIENTATION -----");
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    color = (SDL_Color){CYAN};
    sprintf(buffer, "Yaw: %.2f°", convertRadiansToDeg(aircraft->yaw));
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    sprintf(buffer, "Pitch: %.2f°", convertRadiansToDeg(aircraft->pitch));
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    sprintf(buffer, "Roll: %.2f°", convertRadiansToDeg(aircraft->roll));
    renderText(buffer, LEFT_GAP, y, color); y += GAP;

    color = (SDL_Color){RED};

    // RIGHT SIDE (DEBUG INFO)
    if (debugMode) {
        sprintf(buffer, "----- DEBUG -----");
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Drag coefficient: %.6f", dragCoefficient);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Induced Drag: %.6fN", inducedDrag);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Parasitic Drag: %.6fN", parasiticDrag);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Shockwave Drag: %.6fN", shockwaveDrag);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Total Drag: %.6fN", totalDrag);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Relative velocity: %.6fm/s", relativeSpeed);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Relative velocity x: %.6fm/s", relativeVelocity.x);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Relative velocity y: %.6fm/s", relativeVelocity.y);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;

        sprintf(buffer, "Relative velocity z: %.6fm/s", relativeVelocity.z);
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP;
    }

    const int controlsX = RIGHT_GAP;
    int controlsY = SCREEN_HEIGHT - 200; // Position near bottom
    
    color = (SDL_Color){GREEN};

    if (controlsMode){
        sprintf(buffer, "----- CONTROLS -----");
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP;
        sprintf(buffer, "W / S: Pitch Up / Down");
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP;
        sprintf(buffer, "A / D: Yaw Left / Right");
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP;
        sprintf(buffer, "Q / E: Roll Left / Right");
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP;
        sprintf(buffer, "Z / W: Throttle Increase / Decrease");
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP;
        sprintf(buffer, "P: Toggle Debug");
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP;
        sprintf(buffer, "C: Toggle Controls");
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP;
    }

    SDL_RenderPresent(renderer);
}

void destroyTextRenderer(void) {
    TTF_CloseFont(font);
    SDL_DestroyRenderer(renderer);
    SDL_DestroyWindow(window);
    TTF_Quit();
    SDL_Quit();
}