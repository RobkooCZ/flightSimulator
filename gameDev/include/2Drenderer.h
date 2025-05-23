/**
 * @file 2Drenderer.h
 * @brief Header file for 2D rendering functions in the flight simulator.
 *
 * This file contains declarations for functions related to 2D rendering,
 * including text rendering, gauge rendering, and throttle rendering.
 */

#ifndef TWOD_RENDERER_H
#define TWOD_RENDERER_H

// Include SDL2 libraries
#include <SDL2/SDL.h>
#include <SDL2/SDL_ttf.h>

// Include things from other header files
#include "aircraft.h"
#include "aircraftData.h"
#include "physics.h"
#include "weather.h"

/*
    #########################################################
    #                                                       #
    #                     MISCELLANEOUS                     #
    #                                                       #
    #########################################################
*/

/**
 * @brief Calculate various flight parameters.
 *
 * @param aircraft Pointer to the aircraft state.
 * @param aircraftData Pointer to the aircraft data.
 * @param engineOutput Pointer to the engine output.
 * @param totalDrag Pointer to the total drag.
 * @param netForce Pointer to the net force.
 * @param relativeSpeed Pointer to the relative speed.
 * @param relativeVelocity Pointer to the relative velocity.
 * @param parasiticDrag Pointer to the parasitic drag.
 * @param inducedDrag Pointer to the induced drag.
 * @param shockwaveDrag Pointer to the shockwave drag.
 * @param dragCoefficient Pointer to the drag coefficient.
 * @param simulationTime The current simulation time.
 */
void calculateFlightParameters(AircraftState *aircraft, AircraftData *aircraftData, 
                               float *engineOutput, float *totalDrag, float *netForce, 
                               float *relativeSpeed, Vector3 *relativeVelocity,
                               float *parasiticDrag, float *inducedDrag, float *shockwaveDrag,
                               float *dragCoefficient, float simulationTime);

/**
 * @brief Initialize the text renderer.
 */
void initTextRenderer(void);

/**
 * @brief Render text on the screen.
 *
 * @param text The text to render.
 * @param x The x-coordinate of the text.
 * @param y The y-coordinate of the text.
 * @param color The color of the text.
 */
void renderText(const char *text, int x, int y, SDL_Color color);

/**
 * @brief Toggle rendering modes based on SDL events.
 *
 * @param event The SDL event.
 */
void toggleModes(SDL_Event event);

/**
 * @brief Destroy the text renderer and free resources.
 */
void destroyTextRenderer(void);

/*
    #########################################################
    #                                                       #
    #               GAUGE RENDERER FUNCTIONS                #
    #                                                       #
    #########################################################
*/

/**
 * @brief Draw a circle on the renderer, using the midpoint algorithm. (~500ms to render)
 * 
 * @param localRenderer The SDL renderer.
 * @param centreX The x-coordinate of the circle center.
 * @param centreY The y-coordinate of the circle center.
 * @param radius The radius of the circle.
 */
void drawCircle(SDL_Renderer *localRenderer, int32_t centreX, int32_t centreY, int32_t radius);

/**
 * @brief Draw ticks on the gauge.
 *
 * @param localRenderer The SDL renderer.
 * @param centerX The x-coordinate of the gauge center.
 * @param centerY The y-coordinate of the gauge center.
 * @param radius The radius of the gauge.
 * @param numTicks The number of ticks to draw.
 * @param startAngle The starting angle of the ticks.
 * @param endAngle The ending angle of the ticks.
 */
void drawTicks(SDL_Renderer *localRenderer, int centerX, int centerY, int radius, int numTicks, float startAngle, float endAngle);

/**
 * @brief Draw numbers on the gauge.
 *
 * @param localRenderer The SDL renderer.
 * @param centerX The x-coordinate of the gauge center.
 * @param centerY The y-coordinate of the gauge center.
 * @param radius The radius of the gauge.
 * @param numTicks The number of ticks.
 * @param maxValue The maximum value to display.
 * @param startAngle The starting angle of the ticks.
 * @param endAngle The ending angle of the ticks.
 */
void drawNumbers(SDL_Renderer *localRenderer, int centerX, int centerY, int radius, int numTicks, float maxValue, float startAngle, float endAngle);

/**
 * @brief Draw the needle on the gauge.
 *
 * @param localRenderer The SDL renderer.
 * @param cx The x-coordinate of the gauge center.
 * @param cy The y-coordinate of the gauge center.
 * @param radius The radius of the gauge.
 * @param val The current value to draw.
 * @param maxVal The maximum value.
 * @param startAngle The starting angle of the needle.
 * @param endAngle The ending angle of the needle.
 */
void drawNeedle(SDL_Renderer *localRenderer, int cx, int cy, int radius, float val, float maxVal, float startAngle, float endAngle);

/**
 * @brief Render the Mach counter.
 *
 * @param localRenderer The SDL renderer.
 * @param cx The x-coordinate of the counter center.
 * @param cy The y-coordinate of the counter center.
 */
void machCounter(SDL_Renderer* localRenderer, int cx, int cy);

/**
 * @brief Render the speed gauge.
 *
 * @param renderer The SDL renderer.
 * @param localFont The font to use for rendering text.
 * @param cx The x-coordinate of the gauge center.
 * @param cy The y-coordinate of the gauge center.
 * @param radius The radius of the gauge.
 * @param speed The current speed.
 * @param maxSpeed The maximum speed.
 */
void renderSpeedGauge(SDL_Renderer* renderer, TTF_Font* localFont, int cx, int cy, int radius, float speed, float maxSpeed);

/*
    #########################################################
    #                                                       #
    #                        THROTTLE                       #
    #                                                       #
    #########################################################
*/

/**
 * @brief Render the throttle bar.
 *
 * @param localRenderer The SDL renderer.
 * @param throttle The current throttle value.
 * @param x The x-coordinate of the throttle bar.
 * @param y The y-coordinate of the throttle bar.
 */
void throttleBar(SDL_Renderer *localRenderer, float throttle, int x, int y);

/*
    #########################################################
    #                                                       #
    #                       FUEL GAUGE                      #
    #                                                       #
    #########################################################
*/

/**
 * @brief Render the half circle for the fuel gauge.
 * 
 * @param localRenderer The SDL renderer.
 * @param cx The x-coordinate of the gauge center.
 * @param cy The y-coordinate of the gauge center.
 * @param radius The radius of the gauge.
 */
void renderHalfCircle(SDL_Renderer *localRenderer, int cx, int cy, int radius);

/**
 * @brief Render the fuel gauge.
 *
 * @param localRenderer The SDL renderer.
 * @param localFont The font to use for rendering text.
 * @param cx The x-coordinate of the gauge center.
 * @param cy The y-coordinate of the gauge center.
 * @param radius The radius of the gauge.
 * @param fuel The current fuel level.
 * @param maxFuel The maximum fuel level.
 */
void renderFuelGauge(SDL_Renderer* localRenderer, TTF_Font* localFont, int cx, int cy, int radius, float fuel, float maxFuel);

/*
    #########################################################
    #                                                       #
    #                        RENDERER                       #
    #                                                       #
    #########################################################
*/

/**
 * @brief Render flight information on the screen.
 *
 * @param aircraft Pointer to the aircraft state.
 * @param aircraftData Pointer to the aircraft data.
 * @param fps The current frames per second.
 * @param simulationTime The current simulation time.
*/
void renderFlightInfo(AircraftState *aircraft, AircraftData *aircraftData, float fps, float simulationTime);

#endif // TWOD_RENDERER_H