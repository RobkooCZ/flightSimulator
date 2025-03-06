/**
 * @file aircraftData.c
 * 
 * @brief This file contains functions to read aircraft data from a file and populate an AircraftData structure.
 */

// Include the header file for this source file
#include "aircraftData.h" 

// Include neccessary libraries
#include <string.h>
#include <stdlib.h> 

void getAircraftDataByName(const char *filename, const char *aircraftName, AircraftData *aircraftData) {
    FILE *file = fopen(filename, "r"); // Open the file for reading
    const char *DELIMITER = "|"; // Define the delimiter used in the file

    if (!file) { // Check if the file was opened successfully
        printf("Error: Could not open data file %s\n", filename); // Print an error message if the file could not be opened
        return; // Return from the function
    }
    
    char line[255]; // Buffer to hold each line read from the file
    // Skip the header line
    if (fgets(line, sizeof(line), file) == NULL) { // Read the first line (header) and check if it was successful
        fclose(file); // Close the file
        return; // Return from the function
    }
    
    while (fgets(line, sizeof(line), file) != NULL) { // Read each subsequent line from the file
        // Remove trailing newline
        line[strcspn(line, "\n")] = '\0'; // Replace the newline character with a null terminator
        // Skip empty or comment lines
        if (line[0] == '\0' || line[0] == '#') { // Check if the line is empty or a comment
            continue; // Skip to the next iteration of the loop
        }
        
        // Tokenize the line using "|" separator
        char *token = strtok(line, DELIMITER); // Get the first token (aircraft name)
        if (token == NULL) // Check if the token is NULL
            continue; // Skip to the next iteration of the loop
        
        // Check for a matching aircraft name
        if (strcmp(token, aircraftName) == 0) { // Compare the token with the aircraft name
            // Copy the name into the fixed-size array
            strncpy(aircraftData->name, token, MAX_NAME_LENGTH - 1); // Copy the name to the aircraft data structure
            aircraftData->name[MAX_NAME_LENGTH - 1] = '\0'; // Ensure the name is null-terminated
            
            token = strtok(NULL, DELIMITER); // Get the next token (mass)
            if (token) aircraftData->mass = (float)atof(token); // Convert the token to a float and assign it to mass
            
            token = strtok(NULL, DELIMITER); // Get the next token (wing area)
            if (token) aircraftData->wingArea = (float)atof(token); // Convert the token to a float and assign it to wing area
            
            token = strtok(NULL, DELIMITER); // Get the next token (wing span)
            if (token) aircraftData->wingSpan = (float)atof(token); // Convert the token to a float and assign it to wing span
            
            token = strtok(NULL, DELIMITER); // Get the next token (sweep angle)
            if (token) aircraftData->sweepAngle = (float)atof(token); // Convert the token to a float and assign it to sweep angle
            
            token = strtok(NULL, DELIMITER); // Get the next token (thrust)
            if (token) aircraftData->thrust = atoi(token); // Convert the token to an integer and assign it to thrust
            
            token = strtok(NULL, DELIMITER); // Get the next token (afterburner thrust)
            if (token) aircraftData->afterburnerThrust = atoi(token); // Convert the token to an integer and assign it to afterburner thrust
            
            token = strtok(NULL, DELIMITER); // Get the next token (max speed)
            if (token) aircraftData->maxSpeed = (float)atof(token); // Convert the token to a float and assign it to max speed
            
            token = strtok(NULL, DELIMITER); // Get the next token (stall speed)
            if (token) aircraftData->stallSpeed = (float)atof(token); // Convert the token to a float and assign it to stall speed
            
            token = strtok(NULL, DELIMITER); // Get the next token (service ceiling)
            if (token) aircraftData->serviceCeiling = atoi(token); // Convert the token to an integer and assign it to service ceiling
            
            token = strtok(NULL, DELIMITER); // Get the next token (fuel capacity)
            if (token) aircraftData->fuelCapacity = atoi(token); // Convert the token to an integer and assign it to fuel capacity
            
            token = strtok(NULL, DELIMITER); // Get the next token (cd0)
            if (token) aircraftData->cd0 = (float)atof(token); // Convert the token to a float and assign it to cd0
            
            token = strtok(NULL, DELIMITER); // Get the next token (max AoA)
            if (token) aircraftData->maxAoA = (float)atof(token); // Convert the token to a float and assign it to max AoA
            
            token = strtok(NULL, DELIMITER); // Get the next token (fuel burn)
            if (token) aircraftData->fuelBurn = (float)atof(token); // Convert the token to a float and assign it to fuel burn
            
            token = strtok(NULL, DELIMITER); // Get the next token (afterburner fuel burn)
            if (token) aircraftData->afterburnerFuelBurn = (float)atof(token); // Convert the token to a float and assign it to afterburner fuel burn
            
            token = strtok(NULL, DELIMITER); // Get the next token (lpha)
            if (token) aircraftData->alpha = (float)atof(token); // Convert the token to a float and assign it to alpha
            
            token = strtok(NULL, DELIMITER); // Get the next token (kw)
            if (token) aircraftData->kw = (float)atof(token); // Convert the token to a float and assign it to kw
            
            token = strtok(NULL, DELIMITER); // Get the next token (Md)
            if (token) aircraftData->Md = (float)atof(token); // Convert the token to a float and assign it to Md
            

            break; // Break out of the loop as the matching aircraft data has been found
        }
    }
    fclose(file); // Close the file
}