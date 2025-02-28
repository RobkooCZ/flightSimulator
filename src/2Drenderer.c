/** 
 * @file 2Drenderer.c
 * @brief Functions for rendering 2D elements in the flight simulator.
 * 
 * This file contains functions for rendering 2D elements in the flight simulator,
 * such as text, gauges, and throttle bars.
*/

// Include the header file
#include "2Drenderer.h"

// Include the necessary libraries
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
#define RIGHT_GAP 700 
#define TOP_GAP 20
#define GAP 25

// Toggles for different modes
static int debugMode = 0; // Toggle for debug mode
static int controlsMode = 1; // Toggle controls mode
static int textMode = 1; // Toggle mode (1 = text, 0 = visual)

/*
    #########################################################
    #                                                       #
    #                     MISCELLANEOUS                     #
    #                                                       #
    #########################################################
*/

void calculateFlightParameters(AircraftState *aircraft, AircraftData *aircraftData, 
                               float *engineOutput, float *totalDrag, float *netForce, 
                               float *relativeSpeed, Vector3 *relativeVelocity,
                               float *parasiticDrag, float *inducedDrag, float *shockwaveDrag,
                               float *dragCoefficient, float simulationTime){
    // Calculate engine output based on throttle and afterburner status
    *engineOutput = calculateThrust(
        aircraftData->thrust, 
        aircraftData->afterburnerThrust, 
        aircraft, 
        (int)(aircraft->controls.throttle * 100)
    );

    // Calculate total drag, including parasitic, induced, and shockwave drag
    *totalDrag = calculateTotalDrag(parasiticDrag, inducedDrag, shockwaveDrag, relativeSpeed, relativeVelocity, simulationTime, aircraft, aircraftData);

    // Calculate drag coefficient based on relative speed, max speed, altitude, and a constant C_D0
    *dragCoefficient = calculateDragCoefficient(*relativeSpeed, convertKmhToMs(aircraftData->maxSpeed), aircraft->y, C_D0);

    // Calculate net force as the difference between engine output and total drag
    *netForce = *engineOutput - *totalDrag;
}

void initTextRenderer(void) {
    SDL_Init(SDL_INIT_VIDEO); // Initialize SDL2 video subsystem
    TTF_Init(); // Initialize SDL2_ttf library

    // Create an SDL window with the title "Flight Stats", centered on the screen, with specified width and height, and shown
    window = SDL_CreateWindow("Flight Stats", SDL_WINDOWPOS_CENTERED, SDL_WINDOWPOS_CENTERED, SCREEN_WIDTH, SCREEN_HEIGHT, SDL_WINDOW_SHOWN);
    
    // Create an SDL renderer for the window with hardware acceleration
    renderer = SDL_CreateRenderer(window, -1, SDL_RENDERER_ACCELERATED);

    // Load the font from the specified path with a size of 18
    font = TTF_OpenFont("fonts/Oswald/Oswald-Medium.ttf", 18);
    
    // Check if the font failed to load
    if (!font) {
        printf("Failed to load font!\n"); // Print error message
        exit(1); // Exit the program with an error code
    }
}

void renderText(const char *text, int x, int y, SDL_Color color) {
    // Render the text to an SDL surface using the specified font and color
    SDL_Surface *surface = TTF_RenderUTF8_Solid(font, text, color);
    
    // Create a texture from the rendered surface
    SDL_Texture *texture = SDL_CreateTextureFromSurface(renderer, surface);
    
    // Define the rectangle where the text will be rendered
    SDL_Rect rect = {x, y, surface->w, surface->h};

    // Copy the texture to the renderer at the specified rectangle
    SDL_RenderCopy(renderer, texture, NULL, &rect);
    
    // Free the surface as it is no longer needed
    SDL_FreeSurface(surface);
    
    // Destroy the texture to free up resources
    SDL_DestroyTexture(texture);
}

void toggleModes(SDL_Event event) {
    // Check if a key was pressed
    if (event.type == SDL_KEYDOWN) {
        // Toggle debug mode if 'p' key is pressed
        if (event.key.keysym.sym == SDLK_p) {
            debugMode = !debugMode;
        }
        // Toggle controls mode if 'c' key is pressed
        if (event.key.keysym.sym == SDLK_c) {
            controlsMode = !controlsMode;
        }
        // Toggle text mode if 'm' key is pressed
        if (event.key.keysym.sym == SDLK_m) {
            textMode = !textMode;
        }
    }
}

void destroyTextRenderer(void) {
    TTF_CloseFont(font); // Close the font
    SDL_DestroyRenderer(renderer); // Destroy the renderer
    SDL_DestroyWindow(window); // Destroy the window
    TTF_Quit(); // Quit the SDL_ttf library
    SDL_Quit(); // Quit the SDL library
}

/*
    #########################################################
    #                                                       #
    #               GAUGE RENDERER FUNCTIONS                #
    #                                                       #
    #########################################################
*/

void drawCircle(SDL_Renderer *localRenderer, int32_t centreX, int32_t centreY, int32_t radius) {
    SDL_SetRenderDrawColor(localRenderer, GREEN);  // Set the drawing color to green

    const int32_t diameter = (radius * 2);  // Calculate the diameter of the circle

    int32_t x = (radius - 1);  // Initialize x to radius - 1
    int32_t y = 0;  // Initialize y to 0
    int32_t tx = 1;  // Initialize tx to 1
    int32_t ty = 1;  // Initialize ty to 1
    int32_t error = (tx - diameter);  // Initialize the error term

    while (x >= y) {  // Loop until x is less than y
        // Each of the following renders an octant of the circle
        SDL_RenderDrawPoint(localRenderer, centreX + x, centreY - y);  // Draw point in octant 1
        SDL_RenderDrawPoint(localRenderer, centreX + x, centreY + y);  // Draw point in octant 2
        SDL_RenderDrawPoint(localRenderer, centreX - x, centreY - y);  // Draw point in octant 3
        SDL_RenderDrawPoint(localRenderer, centreX - x, centreY + y);  // Draw point in octant 4
        SDL_RenderDrawPoint(localRenderer, centreX + y, centreY - x);  // Draw point in octant 5
        SDL_RenderDrawPoint(localRenderer, centreX + y, centreY + x);  // Draw point in octant 6
        SDL_RenderDrawPoint(localRenderer, centreX - y, centreY - x);  // Draw point in octant 7
        SDL_RenderDrawPoint(localRenderer, centreX - y, centreY + x);  // Draw point in octant 8

        if (error <= 0) {  // If the error term is non-positive
            ++y;  // Increment y
            error += ty;  // Update the error term
            ty += 2;  // Increment ty by 2
        }

        if (error > 0) {  // If the error term is positive
            --x;  // Decrement x
            tx += 2;  // Increment tx by 2
            error += (tx - diameter);  // Update the error term
        }
    }
}

void drawTicks(SDL_Renderer *localRenderer, int centerX, int centerY, int radius, int numTicks) {
    float startAngle = -70.0f;  // Starting angle for 0 km/h (= 20 deg from the top)
    float endAngle = 250.0f;   // Ending angle for maxSpeed km/h (= -20 deg from the top)

    // Calculate the angle step
    float angleStep = (endAngle - startAngle) / (float)(numTicks - 1);

    SDL_SetRenderDrawColor(localRenderer, GREEN);  // Green color for the ticks
    for (int i = 0; i < numTicks; i++) {
        float angle = startAngle + (angleStep * (float)i);  // Calculate the angle for each tick
        int tickLength = 20;

        // Convert angle to radians
        float rad = angle * (PI / 180.0f);

        // Calculate tick position (end point)
        int tickEndX = (int)((float)centerX + (float)(radius - tickLength) * cosf(rad));
        int tickEndY = (int)((float)centerY + (float)(radius - tickLength) * sinf(rad));

        // Calculate tick start position (from center)
        int tickStartX = (int)((float)centerX + (float)radius * cosf(rad));
        int tickStartY = (int)((float)centerY + (float)radius * sinf(rad));

        // Draw the tick line
        SDL_RenderDrawLine(localRenderer, tickStartX, tickStartY, tickEndX, tickEndY);
    }
}

void drawNumbers(SDL_Renderer *localRenderer, int centerX, int centerY, int radius, int numTicks, int maxSpeed) {
    float startAngle = -70.0f;  // Starting angle for 0 km/h (= 20 deg from the top)
    float endAngle = 250.0f;    // Ending angle for maxSpeed km/h (= -20 deg from the top)

    TTF_Font *localFont = TTF_OpenFont("fonts/Oswald/Oswald-Medium.ttf", 12);

    // Calculate the angle step
    float angleStep = (endAngle - startAngle) / (float)(numTicks - 1);

    SDL_SetRenderDrawColor(localRenderer, GREEN);  // Set the number color to GREEN
    for (int i = 0; i < numTicks; i++) {
        float angle = startAngle + (angleStep * (float)i);  // Calculate the angle for each number

        // Calculate the number position (slightly inside the radius)
        int numberRadius = radius - 40;  // Move the number 40px inside the circle
        float rad = angle * (PI / 180.0f);

        // Calculate the position for each number
        int numberPosX = (int)((float)centerX + (float)numberRadius * cosf(rad));
        int numberPosY = (int)((float)centerY + (float)numberRadius * sinf(rad));

        // Convert the number to a string
        char numberStr[10];
        sprintf(numberStr, "%d", i * maxSpeed/(numTicks-1)); // Display 0, maxSpeed/26, ...

        // Render the text
        SDL_Color textColor = {GREEN};  // Text color 
        SDL_Surface* textSurface = TTF_RenderText_Solid(localFont, numberStr, textColor);
        if (textSurface == NULL) { // Check if the text surface was created successfully
            printf("Unable to render text surface! SDL_ttf Error: %s\n", TTF_GetError());
            continue;
        }

        // Create a texture from the text surface
        SDL_Texture* textTexture = SDL_CreateTextureFromSurface(localRenderer, textSurface);
        SDL_FreeSurface(textSurface);

        // Get the text's width and height
        int textWidth = 0;
        int textHeight = 0;
        SDL_QueryTexture(textTexture, NULL, NULL, &textWidth, &textHeight);

        // Set the destination rectangle (position the number)
        SDL_Rect renderQuad = {numberPosX - textWidth / 2, numberPosY - textHeight / 2, textWidth, textHeight};

        // Render the number
        SDL_RenderCopy(localRenderer, textTexture, NULL, &renderQuad);

        // Free the texture
        SDL_DestroyTexture(textTexture);
    }

    TTF_CloseFont(localFont);
}

void drawNeedle(SDL_Renderer *localRenderer, int centerX, int centerY, int radius, float speed, float maxSpeed) {
    // Map speed (0-maxSpeed) to angle (-70° to 250°)
    float startAngle = -70.0f;  // Starting angle for 0 km/h (= 20 deg from the top)
    float endAngle = 250.0f;    // Ending angle for maxSpeed km/h (= -20 deg from the top)
    
    // Calculate the angle for the given speed
    float angle = startAngle + ((speed / maxSpeed) * (endAngle - startAngle));

    // Convert the angle to radians
    float rad = angle * (PI / 180.0f);

    // Needle length
    int needleLength = radius - 30; // The needle will be a bit shorter than the ticks
    
    // Calculate the end position of the needle
    int needleEndX = (int)((float)centerX + (float)needleLength * cosf(rad));
    int needleEndY = (int)((float)centerY + (float)needleLength * sinf(rad));

    // Draw the needle (line from the center to the calculated end point)
    SDL_SetRenderDrawColor(localRenderer, RED);  // Red color for the needle
    SDL_RenderDrawLine(localRenderer, centerX, centerY, needleEndX, needleEndY);
}

void machCounter(SDL_Renderer* localRenderer, int cx, int cy, float speed, int alt){
    // Convert speed from km/h to Mach number based on altitude
    float mach = convertMsToMach(convertKmhToMs(speed), (float)alt);

    // Create a string to hold the Mach number text
    char machText[20];
    sprintf(machText, "%.2f", mach); // Format the Mach number to two decimal places

    // Open the font with size 50
    TTF_Font *localFont = TTF_OpenFont("fonts/Oswald/Oswald-Medium.ttf", 50);

    // Set the text color to white
    SDL_Color textColor = {WHITE};  
    // Render the Mach number text to a surface
    SDL_Surface* machSurface = TTF_RenderText_Solid(localFont, machText, textColor);
    // Create a texture from the rendered surface
    SDL_Texture* machTexture = SDL_CreateTextureFromSurface(localRenderer, machSurface);

    // Get the width and height of the rendered text
    int machWidth = machSurface->w;
    int machHeight = machSurface->h;
    // Calculate the position to center the text
    int machX = cx - machWidth / 2;
    int machY = cy - machHeight - 5;  // Slightly above the center

    // Define the rectangle where the text will be rendered
    SDL_Rect machRect = {machX, machY, machWidth, machHeight};
    // Copy the texture to the renderer at the specified rectangle
    SDL_RenderCopy(localRenderer, machTexture, NULL, &machRect);

    // Free the Mach texture and surface
    SDL_DestroyTexture(machTexture);
    SDL_FreeSurface(machSurface);
    // Close the font
    TTF_CloseFont(localFont);
}

void renderSpeedGauge(SDL_Renderer* localRenderer, TTF_Font* localFont, int cx, int cy, int radius, float speed, float maxSpeed, int alt) {    
    // Draw the circle for the gauge
    drawCircle(localRenderer, cx, cy, radius);

    const int numTicks = 26; // Number of ticks on the gauge

    // Draw the ticks on the gauge
    drawTicks(localRenderer, cx, cy, radius, numTicks);

    // Draw the numbers on the gauge
    drawNumbers(localRenderer, cx, cy, radius, numTicks, (int)maxSpeed);

    // Draw the needle indicating the current speed
    drawNeedle(localRenderer, cx, cy, radius, speed, maxSpeed);

    // Print the Mach number on the gauge
    machCounter(localRenderer, cx, cy, speed, alt);

    // Draw "km/h" text slightly above the needle center
    char unitText[] = "km/h"; // Unit text
    SDL_Color textColor = {WHITE};  // White text color
    SDL_Surface* unitSurface = TTF_RenderText_Solid(localFont, unitText, textColor); // Render the unit text to a surface
    SDL_Texture* unitTexture = SDL_CreateTextureFromSurface(localRenderer, unitSurface); // Create a texture from the surface

    int unitWidth = unitSurface->w; // Get the width of the unit text
    int unitHeight = unitSurface->h; // Get the height of the unit text
    int unitX = cx - unitWidth / 2; // Calculate the x position to center the text
    int unitY = cy - radius - unitHeight + 50; // Calculate the y position slightly above the needle center

    // Define the rectangle where the unit text will be rendered
    SDL_Rect unitRect = {unitX, unitY, unitWidth, unitHeight};
    // Copy the texture to the renderer at the specified rectangle
    SDL_RenderCopy(localRenderer, unitTexture, NULL, &unitRect);

    // Free the unit texture and surface
    SDL_DestroyTexture(unitTexture);
    SDL_FreeSurface(unitSurface);
}

/*
    #########################################################
    #                                                       #
    #                        THROTTLE                       #
    #                                                       #
    #########################################################
*/

void throttleBar(SDL_Renderer *localRenderer, float throttle, int x, int y){
    float displayThrottle = throttle * 100; // Convert throttle to percentage
    int afterburner = 0; // Flag to check if afterburner is active

    if (displayThrottle > 100){ // If throttle exceeds 100%
        afterburner = 1; // Set afterburner flag
        displayThrottle = 100; // Cap throttle at 100%
    }

    int barWidth = 100; // Width of the throttle bar
    int barHeight = 350; // Height of the throttle bar
    int borderThickness = 3; // Thickness of the border

    int filledHeight = (int)((displayThrottle / 100.0f) * ((float)barHeight - (float)borderThickness * 2.0f)); // Calculate the filled height based on throttle percentage

    // Draw "border"
    SDL_SetRenderDrawColor(localRenderer, GREEN); // Set color to green
    SDL_Rect border = { x, y, barWidth, barHeight }; // Define the border rectangle
    SDL_RenderDrawRect(localRenderer, &border); // Draw the border

    // Fill it in to give the illusion of an empty throttle
    SDL_SetRenderDrawColor(localRenderer, BLACK); // Set color to black
    SDL_Rect background = { x + borderThickness, y + borderThickness, 
                            barWidth - borderThickness * 2, barHeight - borderThickness * 2 }; // Define the background rectangle
    SDL_RenderFillRect(localRenderer, &background); // Fill the background

    // Draw filled portion
    if (afterburner){
        SDL_SetRenderDrawColor(localRenderer, RED); // Set color to red if afterburner is active
    }
    else{
        SDL_SetRenderDrawColor(localRenderer, GREEN); // Set color to green otherwise
    }

    SDL_Rect filled = { x + borderThickness, y + barHeight - borderThickness - filledHeight, 
                        barWidth - borderThickness * 2, filledHeight }; // Define the filled rectangle
    SDL_RenderFillRect(localRenderer, &filled); // Fill the rectangle

    // Render the throttle percentage or WEP above the bar
    SDL_Color textColor;  // Define text color
    char throttleText[10]; // Buffer for throttle text
    if (afterburner) {
        textColor = (SDL_Color){RED}; // Set text color to red
        sprintf(throttleText, "WEP"); // Set text to "WEP"
    }
    else {
        textColor = (SDL_Color){WHITE}; // Set text color to white
        sprintf(throttleText, "%.0f%%", throttle * 100); // Set text to throttle percentage
    }

    TTF_Font *localFont = TTF_OpenFont("fonts/Oswald/Oswald-Medium.ttf", 18); // Open the font
    SDL_Surface* textSurface = TTF_RenderText_Solid(localFont, throttleText, textColor); // Render the text to a surface
    SDL_Texture* textTexture = SDL_CreateTextureFromSurface(localRenderer, textSurface); // Create a texture from the surface

    int textWidth = textSurface->w; // Get the width of the text
    int textHeight = textSurface->h; // Get the height of the text
    int textX = x + (barWidth - textWidth) / 2; // Calculate the x position to center the text
    int textY = y - textHeight - 5;  // Calculate the y position slightly above the bar

    // Render the throttle text
    SDL_Rect textRect = {textX, textY, textWidth, textHeight}; // Define the text rectangle
    SDL_RenderCopy(localRenderer, textTexture, NULL, &textRect); // Copy the texture to the renderer

    // Free the text texture and surface
    SDL_DestroyTexture(textTexture); // Destroy the texture
    SDL_FreeSurface(textSurface); // Free the surface
    TTF_CloseFont(localFont); // Close the font
}

/*
    #########################################################
    #                                                       #
    #                        RENDERER                       #
    #                                                       #
    #########################################################
*/

void renderFlightInfo(AircraftState *aircraft, AircraftData *aircraftData, float fps, float simulationTime) {
    char buffer[128]; // Buffer for text rendering
    int y = TOP_GAP; // Initial y position for text rendering

    SDL_SetRenderDrawColor(renderer, BLACK); // Set the renderer draw color to black
    SDL_RenderClear(renderer); // Clear the renderer with the current draw color

    SDL_Color color; // Color for text rendering

    // Flight parameter calculations
    float engineOutput, totalDrag, netForce, relativeSpeed, dragCoefficient;
    float parasiticDrag, inducedDrag, shockwaveDrag;
    Vector3 relativeVelocity;

    // Calculate flight parameters
    calculateFlightParameters(aircraft, aircraftData, 
                            &engineOutput, &totalDrag, &netForce, 
                            &relativeSpeed, &relativeVelocity, 
                            &parasiticDrag, &inducedDrag, &shockwaveDrag, 
                            &dragCoefficient, simulationTime);

    color = (SDL_Color){WHITE}; // Set text color to white
    sprintf(buffer, "FPS: %.2f", fps); // Format FPS text
    renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render FPS text and update y position

    sprintf(buffer, "Simulated time: %.2fs", simulationTime); // Format simulation time text
    renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render simulation time text and update y position
    
    sprintf(buffer, "----- POSITION -----"); // Format position header text
    renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render position header text and update y position

    sprintf(buffer, "X: %.2f", aircraft->x); // Format X position text
    renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render X position text and update y position

    sprintf(buffer, "Y: %.2f", aircraft->y); // Format Y position text
    renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render Y position text and update y position

    sprintf(buffer, "Z: %.2f", aircraft->z); // Format Z position text
    renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render Z position text and update y position

    if (textMode) { // Check if text mode is enabled
        // LEFT SIDE (Main Info)
        // Aircraft Info
        color = (SDL_Color){YELLOW}; // Set text color to yellow
        sprintf(buffer, "============ %s INFO ============", aircraftData->name); // Format aircraft info header text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render aircraft info header text and update y position

        // Speed Info
        sprintf(buffer, "----- SPEED -----"); // Format speed header text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render speed header text and update y position

        color = (SDL_Color){CYAN}; // Set text color to cyan
        sprintf(buffer, "IAS: %.2f km/h", convertMsToKmh(calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz))); // Format IAS text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render IAS text and update y position

        sprintf(buffer, "TAS: %.2f km/h", convertMsToKmh(calculateTAS(aircraft))); // Format TAS text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render TAS text and update y position

        sprintf(buffer, "Mach: %.2f", convertMsToMach(calculateTAS(aircraft), aircraft->y)); // Format Mach text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render Mach text and update y position

        // Wind Info
        color = (SDL_Color){YELLOW}; // Set text color to yellow
        sprintf(buffer, "----- WIND -----"); // Format wind header text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render wind header text and update y position

        color = (SDL_Color){CYAN}; // Set text color to cyan
        Vector3 wind = getWindVector(aircraft->y, simulationTime); // Get wind vector
        sprintf(buffer, "Wind: X: %.1f m/s  Z: %.1f m/s", wind.x, wind.z); // Format wind text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render wind text and update y position

        // Throttle Info
        color = (SDL_Color){YELLOW}; // Set text color to yellow
        sprintf(buffer, "----- THROTTLE -----"); // Format throttle header text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render throttle header text and update y position

        if (!aircraft->controls.afterburner) { // Check if afterburner is not active
            color = (SDL_Color){CYAN}; // Set text color to cyan
            sprintf(buffer, "Throttle: %.0f%%", aircraft->controls.throttle * 100); // Format throttle percentage text
        } else { // Afterburner is active
            color = (SDL_Color){RED}; // Set text color to red
            sprintf(buffer, "Throttle: WEP"); // Format throttle WEP text
        }
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render throttle text and update y position

        color = (SDL_Color){CYAN}; // Set text color to cyan
        if (aircraft->controls.afterburner) { // Check if afterburner is active
            sprintf(buffer, "Expected engine output: %dN", aircraftData->afterburnerThrust); // Format expected engine output text for afterburner
        } else { // Afterburner is not active
            sprintf(buffer, "Expected engine output: %.0fN", (float)aircraftData->thrust * aircraft->controls.throttle); // Format expected engine output text
        }
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render expected engine output text and update y position

        sprintf(buffer, "Actual engine output: %.0fN", engineOutput); // Format actual engine output text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render actual engine output text and update y position

        sprintf(buffer, "Net force: %.0fN", netForce); // Format net force text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render net force text and update y position

        color = (SDL_Color){YELLOW}; // Set text color to yellow

        // Orientation Info
        sprintf(buffer, "----- ORIENTATION -----"); // Format orientation header text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render orientation header text and update y position

        color = (SDL_Color){CYAN}; // Set text color to cyan
        sprintf(buffer, "Yaw: %.2f°", convertRadiansToDeg(aircraft->yaw)); // Format yaw text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render yaw text and update y position

        sprintf(buffer, "Pitch: %.2f°", convertRadiansToDeg(aircraft->pitch)); // Format pitch text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render pitch text and update y position

        sprintf(buffer, "Roll: %.2f°", convertRadiansToDeg(aircraft->roll)); // Format roll text
        renderText(buffer, LEFT_GAP, y, color); y += GAP; // Render roll text and update y position
    } else { // Visual mode
        renderSpeedGauge(renderer, font, 200, SCREEN_HEIGHT - 200, 175, convertMsToKmh(calculateTAS(aircraft)), aircraftData->maxSpeed, (int)aircraft->y); // Render speed gauge
        throttleBar(renderer, aircraft->controls.throttle, 200 + 200, SCREEN_HEIGHT - 375); // Render throttle bar
    }

    const int controlsX = RIGHT_GAP; // X position for controls text
    int controlsY = GAP; // Initial y position for controls text
    
    color = (SDL_Color){GREEN}; // Set text color to green

    if (controlsMode) { // Check if controls mode is enabled
        sprintf(buffer, "----- CONTROLS -----"); // Format controls header text
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP; // Render controls header text and update y position
        sprintf(buffer, "W / S: Pitch Up / Down"); // Format pitch controls text
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP; // Render pitch controls text and update y position
        sprintf(buffer, "A / D: Yaw Left / Right"); // Format yaw controls text
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP; // Render yaw controls text and update y position
        sprintf(buffer, "Q / E: Roll Left / Right"); // Format roll controls text
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP; // Render roll controls text and update y position
        sprintf(buffer, "Z / W: Throttle Increase / Decrease"); // Format throttle controls text
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP; // Render throttle controls text and update y position
        sprintf(buffer, "P: Toggle Debug"); // Format toggle debug text
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP; // Render toggle debug text and update y position
        sprintf(buffer, "C: Toggle Controls"); // Format toggle controls text
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP; // Render toggle controls text and update y position
        sprintf(buffer, "M: Change Display Mode"); // Format change display mode text
        renderText(buffer, controlsX, controlsY, color); controlsY += GAP; // Render change display mode text and update y position
    }

    color = (SDL_Color){RED}; // Set text color to red

    int debugY = controlsY + GAP; // Initial y position for debug text

    // RIGHT SIDE (DEBUG INFO)
    if (debugMode) { // Check if debug mode is enabled
        sprintf(buffer, "----- DEBUG -----"); // Format debug header text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render debug header text and update y position

        sprintf(buffer, "Drag coefficient: %.6f", dragCoefficient); // Format drag coefficient text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render drag coefficient text and update y position

        sprintf(buffer, "Induced Drag: %.6fN", inducedDrag); // Format induced drag text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render induced drag text and update y position

        sprintf(buffer, "Parasitic Drag: %.6fN", parasiticDrag); // Format parasitic drag text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render parasitic drag text and update y position

        sprintf(buffer, "Shockwave Drag: %.6fN", shockwaveDrag); // Format shockwave drag text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render shockwave drag text and update y position

        sprintf(buffer, "Total Drag: %.6fN", totalDrag); // Format total drag text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render total drag text and update y position

        sprintf(buffer, "Relative velocity: %.6fm/s", relativeSpeed); // Format relative velocity text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render relative velocity text and update y position

        sprintf(buffer, "Relative velocity x: %.6fm/s", relativeVelocity.x); // Format relative velocity x text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render relative velocity x text and update y position

        sprintf(buffer, "Relative velocity y: %.6fm/s", relativeVelocity.y); // Format relative velocity y text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render relative velocity y text and update y position

        sprintf(buffer, "Relative velocity z: %.6fm/s", relativeVelocity.z); // Format relative velocity z text
        renderText(buffer, RIGHT_GAP, debugY, color); debugY += GAP; // Render relative velocity z text and update y position
    }

    SDL_RenderPresent(renderer); // Present the renderer
}