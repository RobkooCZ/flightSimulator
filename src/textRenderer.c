#include "textRenderer.h"
#include "physics.h"

#include <stdlib.h>
#include <stdio.h>

void printInfo(AircraftState *aircraft, float fps){
    printf("\033[H"); // ASCII escape code to avoid flickering

    printf("----- J29F INFO -----");
    printf("\nFPS: %.2f  ", fps);

    printf("\n\nAirspeed: %.2fkm/h  ", convertMsToKmh(calculateMagnitude(aircraft->vx, aircraft->vy, aircraft->vz)));
    
    printf("\n\nThrottle: %d%%  ", 100);

    printf("\n\nX: %.2f  ", aircraft->x);
    printf("\nY: %.2f  ", aircraft->y);
    printf("\nZ: %.2f  ", aircraft->z);

    printf("\n\nVX: %.2f  ", aircraft->vx);
    printf("\nVY: %.2f  ", aircraft->vy);
    printf("\nVZ: %.2f  ", aircraft->vz);

    printf("\n\nYaw: %.2f˚  ", convertRadiansToDeg(aircraft->yaw));
    printf("\nPitch: %.2f˚  ", convertRadiansToDeg(aircraft->pitch));
    printf("\nRoll: %.2f˚  ", convertRadiansToDeg(aircraft->roll));

    printf("\n\nAoA: %.2f°  ", convertRadiansToDeg(calculateAoA(aircraft)));
    printf("\nLift: %.2fN  ", calculateLift(aircraft));
}