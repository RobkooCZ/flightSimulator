/**
 * @file menu.h
 * @brief Header file for the flight simulator menu system.
 *
 * This file contains definitions, includes, and function prototypes for handling
 * the menu system in the flight simulator application. It supports both Windows
 * and Unix-like systems.
 */

#ifndef MENU_H
#define MENU_H

/**
 * @def MAX_AIRCRAFT
 * @brief Maximum number of aircraft that can be handled.
 */
#define MAX_AIRCRAFT 50

/**
 * @def MAX_NAME_LENGTH
 * @brief Maximum length of an aircraft name.
 */
#define MAX_NAME_LENGTH 20

#ifdef _WIN32
    // Include necessary libraries for Windows
    #include <conio.h>
    #include <windows.h>
    #define CLEAR "cls"

    /**
     * @brief Thread function for handling input on Windows.
     *
     * @param lpParam Pointer to thread parameter.
     * @return DWORD Return value of the thread.
     */
    DWORD WINAPI inputThread(LPVOID lpParam);
#else
    // Include necessary libraries for Unix-like systems
    #include <termios.h>
    #include <fcntl.h>
    #include <unistd.h>
    #include <pthread.h>
    #define CLEAR "clear"

    /**
     * @brief Enable raw mode for terminal input.
     */
    void enableRawMode(void);

    /**
     * @brief Disable raw mode for terminal input.
     */
    void disableRawMode(void);

    /**
     * @brief Get a key press from the terminal.
     *
     * @return char The key that was pressed.
     */
    char getKeyPress(void);

    /**
     * @brief Thread function for handling input on Unix-like systems.
     *
     * @param arg Pointer to thread argument.
     * @return void* Return value of the thread.
     */
    void *inputThread(void *arg);

    /**
     * @brief Check if a key has been pressed.
     *
     * @return int Non-zero if a key has been pressed, zero otherwise.
     */
    int kbhit(void);
#endif

/**
 * @struct Aircraft
 * @brief Struct for holding aircraft data.
 *
 * This struct contains the name of the aircraft.
 */
typedef struct {
    char name[MAX_NAME_LENGTH];
} Aircraft;

/**
 * @brief Load aircraft names from a file.
 *
 * @param filename The name of the file to load aircraft names from.
 * @param aircraftList Array to store the loaded aircraft names.
 * @param aircraftCount Pointer to an integer to store the number of loaded aircraft.
 * @return int Zero on success, non-zero on failure.
 */
int loadAircraftNames(const char *filename, Aircraft aircraftList[], int *aircraftCount);

/**
 * @brief Display the menu with the list of aircraft.
 *
 * @param aircraftList Array of aircraft to display.
 * @param aircraftCount Number of aircraft in the list.
 * @param selectedIndex Index of the currently selected aircraft.
 */
void displayMenu(Aircraft aircraftList[], int aircraftCount, int selectedIndex);

/**
 * @brief Select an aircraft from the list.
 *
 * @param aircraftList Array of aircraft to select from.
 * @param aircraftCount Number of aircraft in the list.
 * @return int Index of the selected aircraft.
 */
int selectAircraft(Aircraft aircraftList[], int aircraftCount);

#endif