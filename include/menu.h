#ifndef MENU_H
#define MENU_H

#define MAX_AIRCRAFT 50
#define MAX_NAME_LENGTH 20

// Struct for holding aircraft data
typedef struct {
    char name[MAX_NAME_LENGTH];
} Aircraft;

// Function prototypes
int loadAircraftNames(const char *filename, Aircraft aircraftList[], int *aircraftCount);
void displayMenu(Aircraft aircraftList[], int aircraftCount, int selectedIndex);
int selectAircraft(Aircraft aircraftList[], int aircraftCount);

#endif