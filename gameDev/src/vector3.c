// Include vector3.h
#include "vector3.h"

// Include standard libraries
#include <stdio.h>
#include <math.h>

// Include logger library incase normalizing vector fails
#include "logger.h"

/*
    ############################################
    #                                          #
    #               DEFINITIONS                #
    #                                          #
    ############################################
*/

Vector3 Vector3_Add(Vector3 V1, Vector3 V2){
    return (Vector3){
        V1.X + V2.X,
        V1.Y + V2.Y,
        V1.Z + V2.Z
    };
}

Vector3 Vector3_Subtract(Vector3 V1, Vector3 V2){
    return (Vector3){
        V1.X - V2.X,
        V1.Y - V2.Y,
        V1.Z - V2.Z
    };
}

Vector3 Vector3_Scale(Vector3 V, float scalar){
    return (Vector3){
        V.X * scalar,
        V.Y * scalar,
        V.Z * scalar
    };
}

float Vector3_Dot(Vector3 V1, Vector3 V2){
    return (V1.X * V2.X) + (V1.Y * V2.Y) + (V1.Z * V2.Z);
}

Vector3 Vector3_Cross(Vector3 V1, Vector3 V2){
    return (Vector3){
        (V1.Y * V2.Z) - (V1.Z * V2.Y),
        (V1.Z * V2.X) - (V1.X * V2.Z),
        (V1.X * V2.Y) - (V1.Y * V2.X)
    };
}

float Vector3_Magnitude(Vector3 V){
    return sqrtf((V.X * V.X) + (V.Y * V.Y) + (V.Z * V.Z));
}

Vector3 Vector3_Normalize(Vector3 V){
    // get the magnitude of vector3 V
    float magnitude = Vector3_Magnitude(V);

    if (magnitude == 0){
        // log error message
        logMessage(LOG_WARNING, "Magnitude of vector V (X: %f, Y: %f, Z: %f) is zero. Cannot normalize vector.", V.X, V.Y, V.Z);
        // return the original vector
        return V;
    }

    // return the normalized vector
    return (Vector3){
        V.X / magnitude,
        V.Y / magnitude,
        V.Z / magnitude
    };
}