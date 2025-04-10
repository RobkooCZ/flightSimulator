/**
 * @file utils.h
 * @brief Utility functions and definitions for the flight simulator.
 *
 * This header file contains macro definitions and function declarations
 * for utility functions used in the flight simulator project.
 */

#ifndef UTILS_H
#define UTILS_H

/**
 * @def TARGET_FPS
 * @brief The target frames per second for the simulator.
 */
#define TARGET_FPS 60
/**
 * @def FRAME_TIME_MICROSECONDS
 * @brief The frame time in microseconds, calculated based on the target FPS.
 */
#define FRAME_TIME_MICROSECONDS (1000000 / TARGET_FPS)
/**
 * @brief Sleeps for a specified number of microseconds.
 *
 * @param microseconds The number of microseconds to sleep.
 */
void sleepMicroseconds(long microseconds);

/**
 * @brief Sleeps for a specified number of milliseconds.
 *
 * @param milliseconds The number of milliseconds to sleep.
 */
void sleepMilliseconds(int milliseconds);

/**
 * @brief Gets the current time in microseconds.
 *
 * @return The current time in microseconds.
 */
long getTimeMicroseconds(void);

#endif // UTILS_H