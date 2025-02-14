#ifndef UTILS_H
#define UTILS_H

#define TARGET_FPS 60
#define FRAME_TIME_MICROSECONDS (1000000 / TARGET_FPS)  // in ms

// Function declarations
void sleepMicroseconds(long microseconds);
void sleepMilliseconds(int milliseconds);
long getTimeMicroseconds();

#endif // UTILS_H