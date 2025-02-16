#include <string.h>
#include <stdlib.h>
#include "aircraftData.h"

void getAircraftDataByName(const char *filename, const char *aircraftName, AircraftData *aircraftData) {
    FILE *file = fopen(filename, "r");
    const char *DELIMITER = "|";

    if (!file) {
        printf("Error: Could not open data file %s\n", filename);
        return;
    }
    
    char line[255];
    // Skip the header line
    if (fgets(line, sizeof(line), file) == NULL) {
        fclose(file);
        return;
    }
    
    while (fgets(line, sizeof(line), file) != NULL) {
        // Remove trailing newline
        line[strcspn(line, "\n")] = '\0';
        // Skip empty or comment lines
        if (line[0] == '\0' || line[0] == '#') {
            continue;
        }
        
        // Tokenize the line using "|" separator
        char *token = strtok(line, DELIMITER);
        if (token == NULL) 
            continue;
        
        // Check for a matching aircraft name
        if (strcmp(token, aircraftName) == 0) {
            // Copy the name into the fixed-size array
            strncpy(aircraftData->name, token, MAX_NAME_LENGTH - 1);
            aircraftData->name[MAX_NAME_LENGTH - 1] = '\0';
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->mass = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->wingArea = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->wingSpan = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->sweepAngle = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->thrust = atoi(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->afterburnerThrust = atoi(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->maxSpeed = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->stallSpeed = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->serviceCeiling = atoi(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->fuelCapacity = atoi(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->cd0 = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->maxAoA = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->fuelBurn = (float)atof(token);
            
            token = strtok(NULL, DELIMITER);
            if (token) aircraftData->afterburnerFuelBurn = (float)atof(token);
            
            break;
        }
    }
    fclose(file);
}