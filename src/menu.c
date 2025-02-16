#include "menu.h"
#include "controls.h" // for real time input
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#ifdef _WIN32
    #define CLEAR "cls"
#else
    #define CLEAR "clear"
#endif

// Function to load aircraft names from a file
int loadAircraftNames(const char *filename, Aircraft aircraftList[], int *aircraftCount) {
    // Open the file with a relative path. Ensure that the working directory is the project root.
    FILE *file = fopen(filename, "r"); 

    if (file == NULL) {
        printf("Error: Could not open file %s\n", filename);
        return 0; // Indicate failure
    }

    char line[255];
    // Skip the first line if it is a header or comment line (starts with '#' or "name|")
    if (fgets(line, sizeof(line), file) != NULL) {
        if (line[0] != '#' && strstr(line, "name|") == NULL) {
            // If this line is actual data, reset file pointer to beginning.
            rewind(file);
        }
    }

    int count = 0;
    while (fgets(line, sizeof(line), file) != NULL) {
        // Remove trailing newline
        line[strcspn(line, "\n")] = '\0';
        // Skip empty lines or comment lines
        if (line[0] == '\0' || line[0] == '#') {
            continue;
        }
        // Extract aircraft name: if data is pipe '|' separated, take the first token.
        char *token = strtok(line, "|");
        if (token != NULL) {
            strncpy(aircraftList[count].name, token, MAX_NAME_LENGTH - 1);
            aircraftList[count].name[MAX_NAME_LENGTH - 1] = '\0';
            count++;
        }
    }
    fclose(file);
    *aircraftCount = count;
    return 1; // Indicate success
}

// Function to display the menu
void displayMenu(Aircraft aircraftList[], int aircraftCount, int selectedIndex){
    system(CLEAR); // Clear the screen
    printf("===== SELECTED YOUR AIRCRAFT =====\n");
    printf("Use W, S keys to navigate and press Enter to select\n\n");

    for (int i = 0; i < aircraftCount; i++) { // Loop through each aircraft
        if (i == selectedIndex) { // If this aircraft is selected
            printf("> %s\n", aircraftList[i].name); // Highlight the current selected aircraft
        } else {
            printf("  %s\n", aircraftList[i].name); // Print the aircraft name
        }
    }
}

int selectAircraft(Aircraft aircraftList[], int aircraftCount){
    int selectedIndex = 0; // Initialize the selected index to 0
    char key; // Variable to store the key press

    #ifdef _WIN32
        while (1){
            displayMenu(aircraftList, aircraftCount, selectedIndex); // Display the menu
            key = (char)_getch(); // Get key press

            if (key == '\r'){ // Enter key pressed
                return selectedIndex; // Return the selected index
            }
            else if(key == 'w' || key == 72){ // 'w' or up arrow key pressed
                if (selectedIndex > 0) selectedIndex--; // Move selection up
            }
            else if(key == 's' || key == 80){ // 's' or down arrow key pressed
                if (selectedIndex < aircraftCount - 1) selectedIndex++; // Move selection down
            }
        }
    #else
        enableRawMode(); // Enable raw mode for terminal input
        while (1){
            displayMenu(aircraftList, aircraftCount, selectedIndex); // Display the menu
            key = getKeyPress(); // Get key press

            if (key == '\n'){ // Enter key pressed
                disableRawMode(); // Disable raw mode
                return selectedIndex; // Return the selected index
            }
            else if(key == 'w' || key == 'A'){ // 'w' or up arrow key pressed
                if (selectedIndex > 0) selectedIndex--; // Move selection up
            }
            else if(key == 's' || key == 'B'){ // 's' or down arrow key pressed
                if (selectedIndex < aircraftCount - 1) selectedIndex++; // Move selection down
            }
        }
    #endif
}