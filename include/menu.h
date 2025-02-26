#ifndef MENU_H
#define MENU_H

#define MAX_AIRCRAFT 50
#define MAX_NAME_LENGTH 20

#ifdef _WIN32
    #include <conio.h>
    #include <windows.h>
    #define CLEAR "cls"

    // ----- INPUT HANDLING THREAD -----
    DWORD WINAPI inputThread(LPVOID lpParam);
#else
    #include <termios.h>
    #include <fcntl.h>
    #include <unistd.h>
    #include <pthread.h>
    #define CLEAR "clear"

    // ----- INPUT SYSTEM -----
    void enableRawMode(void);
    void disableRawMode(void);
    char getKeyPress(void);

    // ----- INPUT HANDLING THREAD -----
    void *inputThread(void *arg);
    int kbhit(void);
#endif

// Struct for holding aircraft data
typedef struct {
    char name[MAX_NAME_LENGTH];
} Aircraft;

// Function prototypes
int loadAircraftNames(const char *filename, Aircraft aircraftList[], int *aircraftCount);
void displayMenu(Aircraft aircraftList[], int aircraftCount, int selectedIndex);
int selectAircraft(Aircraft aircraftList[], int aircraftCount);

#endif