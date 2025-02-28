/**
 * @file menu.c
 * This file contains the implementation of the menu system for the flight simulator.
 * 
 * It includes functions for displaying the menu, handling user input, and navigating
 * through different menu options.
 */

// Include header files
#include "menu.h"
#include "controls.h" // for real time input

// Include necessary libraries
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#ifdef _WIN32
    #define CLEAR "cls"
#else
    #define CLEAR "clear"
#endif


#ifndef _WIN32  // Linux Input Handling
    void enableRawMode(void) {
        struct termios term; // Declare a termios structure to hold terminal settings
        tcgetattr(STDIN_FILENO, &term); // Get current terminal attributes
        term.c_lflag &= (tcflag_t)~(ICANON | ECHO); // Disable canonical mode and echo
        tcsetattr(STDIN_FILENO, TCSANOW, &term); // Set the new attributes immediately
    }

    void disableRawMode(void) {
        struct termios term; // Declare a termios structure to hold terminal settings
        tcgetattr(STDIN_FILENO, &term); // Get current terminal attributes
        term.c_lflag |= (ICANON | ECHO); // Enable canonical mode and echo
        tcsetattr(STDIN_FILENO, TCSANOW, &term); // Set the new attributes immediately
    }

    char getKeyPress(void) {
        char key; // Variable to store the key press
        read(STDIN_FILENO, &key, 1); // Read one character from standard input
        return key; // Return the key press
    }

    int kbhit(void) {
        struct termios oldt, newt; // Declare termios structures to hold terminal settings
        int ch; // Variable to store the character
        int oldf; // Variable to store the old file status flags

        tcgetattr(STDIN_FILENO, &oldt); // Get current terminal attributes
        newt = oldt; // Copy the current attributes to newt
        newt.c_lflag &= (tcflag_t)~(ICANON | ECHO); // Disable canonical mode and echo
        tcsetattr(STDIN_FILENO, TCSANOW, &newt); // Set the new attributes immediately
        oldf = fcntl(STDIN_FILENO, F_GETFL, 0); // Get the current file status flags
        fcntl(STDIN_FILENO, F_SETFL, oldf | O_NONBLOCK); // Set the file status flags to non-blocking

        ch = getchar(); // Get a character from standard input

        tcsetattr(STDIN_FILENO, TCSANOW, &oldt); // Restore the old terminal attributes
        fcntl(STDIN_FILENO, F_SETFL, oldf); // Restore the old file status flags

        if (ch != EOF) { // If a character was read
            ungetc(ch, stdin); // Push the character back to the input stream
            return 1; // Return 1 to indicate a key press
        }

        return 0; // Return 0 to indicate no key press
    }
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
    printf("===== SELECT YOUR AIRCRAFT =====\n");
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
    unsigned int selectedIndex = 0;         // Use unsigned since count is nonnegative
    unsigned int maxIndex = (unsigned int)aircraftCount - 1; // Maximum valid index
    char key;                               // Variable to store key press

    #ifdef _WIN32
        while (1){
            displayMenu(aircraftList, aircraftCount, (int)selectedIndex); // Display the menu
            key = (char)_getch(); // Get key press

            if (key == '\r'){ // Enter key pressed
                return (int)selectedIndex; // Return the selected index
            }
            else if(key == 'w' || key == 72){ // 'w' or up arrow key pressed
                if (selectedIndex > 0) 
                    selectedIndex--; // Move selection up
            }
            else if(key == 's' || key == 80){ // 's' or down arrow key pressed
                if (selectedIndex < maxIndex) 
                    selectedIndex++; // Move selection down
            }
        }
    #else
        enableRawMode(); // Enable raw mode for terminal input
        while (1){
            displayMenu(aircraftList, aircraftCount, (int)selectedIndex); // Display the menu
            key = getKeyPress(); // Get key press

            if (key == '\n'){ // Enter key pressed
                disableRawMode(); // Disable raw mode
                return (int)selectedIndex; // Return the selected index
            }
            else if(key == 'w' || key == 'A'){ // 'w' or up arrow key pressed
                if (selectedIndex > 0) 
                    selectedIndex--; // Move selection up
            }
            else if(key == 's' || key == 'B'){ // 's' or down arrow key pressed
                if (selectedIndex < maxIndex) 
                    selectedIndex++; // Move selection down
            }
        }
    #endif
}