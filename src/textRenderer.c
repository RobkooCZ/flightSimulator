#include "textRenderer.h"
#include "physics.h"

#include <stdlib.h>
#include <stdio.h>

#define DEGREE_ASCII_ESCAPE_CODE "\u00B0"

void printInfo(AircraftState *aircraft, AircraftData *aircraftData, float fps){
    printf("\033[H"); // ASCII escape code to avoid flickering

    printf("----- %s INFO -----", aircraftData->name);
    printf("\nFPS: %.2f  ", fps);

    printf("\n\nIAS: %.2fkm/h  ", convertMsToKmh(calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz)));
    printf("\nTAS: %.2fkm/h  ", convertMsToKmh(calculateTAS(aircraft)));
    printf("\nMach: %.2f  ", convertMsToMach(calculateTAS(aircraft), aircraft->y));

    if (!aircraft->controls.afterburner) {
        printf("\n\nThrottle: %.0f%%  ", aircraft->controls.throttle * 100);
    }
    else {
        printf("\n\nThrottle: WEP  ");
    }

    printf("\nAfterburner: %s  ", aircraft->controls.afterburner ? "ON" : "OFF");

    if (aircraft->controls.afterburner){
        printf("\nExpected engine output: %dN  ", aircraftData->afterburnerThrust);
    }
    else{
        printf("\nExpected engine output: %.0fN      ", (float)aircraftData->thrust * aircraft->controls.throttle);
    }

    printf("\nActual engine output: %.0fN       ", calculateThrust(
        aircraftData->thrust, 
        aircraftData->afterburnerThrust, 
        aircraft, 
        aircraftData->maxSpeed, 
        (int)(aircraft->controls.throttle * 100)
    ));

    printf("\n\nX: %.2f  ", aircraft->x);
    printf("\nY: %.2f  ", aircraft->y);
    printf("\nZ: %.2f  ", aircraft->z);

    printf("\n\nVX: %.2f  ", aircraft->vx);
    printf("\nVY: %.2f  ", aircraft->vy);
    printf("\nVZ: %.2f  ", aircraft->vz);

    printf("\n\nYaw: %.2f" DEGREE_ASCII_ESCAPE_CODE "  ", convertRadiansToDeg(aircraft->yaw));
    printf("\nPitch: %.2f" DEGREE_ASCII_ESCAPE_CODE "  ", convertRadiansToDeg(aircraft->pitch));
    printf("\nRoll: %.2f" DEGREE_ASCII_ESCAPE_CODE "  ", convertRadiansToDeg(aircraft->roll));

    printf("\n\nAoA: %.2f" DEGREE_ASCII_ESCAPE_CODE "  ", convertRadiansToDeg(calculateAoA(aircraft)));
    printf("\nLift: %.2fN  ", calculateLift(aircraft, aircraftData->wingArea));

    fflush(stdout);
}