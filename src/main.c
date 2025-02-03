#include "utils.h"

int main() {
    long startTime, elapsedTime;

    while (1) {
        startTime = getTimeMicroseconds();

        // PSEUDOCODE
        // // 1. Process input (e.g., throttle, controls)
        // handleInput();

        // // 2. Update physics (velocity, acceleration, forces)
        // updatePhysics();

        // // 3. Render (if using graphics)
        // render();

        // 4. Frame rate control
        elapsedTime = getTimeMicroseconds() - startTime;
        if (elapsedTime < FRAME_TIME_MICROSECONDS) {
            sleepMicroseconds(FRAME_TIME_MICROSECONDS - elapsedTime);
        }
    }

    return 0;
}