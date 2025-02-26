#ifndef TEXT_RENDERER_H
#define TEXT_RENDERER_H

#include <SDL2/SDL.h>
#include <SDL2/SDL_ttf.h>

#include "aircraft.h"
#include "aircraftData.h"
#include "physics.h"
#include "weather.h"

void calculateFlightParameters(AircraftState *aircraft, AircraftData *aircraftData, 
                               float *engineOutput, float *totalDrag, float *netForce, 
                               float *relativeSpeed, Vector3 *relativeVelocity,
                               float *parasiticDrag, float *inducedDrag, float *shockwaveDrag,
                               float *dragCoefficient, float simulationTime);

void initTextRenderer(void);
void renderText(const char *text, int x, int y, SDL_Color color);
void toggleModes(SDL_Event event);
void renderFlightInfo(AircraftState *aircraft, AircraftData *aircraftData, float fps, float simulationTime);
void destroyTextRenderer(void);

#endif // TEXT_RENDERER_H