#ifndef VECTOR3_H
#define VECTOR3_H

// Include standard libraries
#include <stdio.h>
#include <math.h>

/*
    ############################################
    #                                          #
    #                  STRUCT                  #
    #                                          #
    ############################################
*/

/**
 * @brief Represents a 3D vector.
 * 
 * This structure stores a 3D vector with X, Y, and Z components, commonly used for:
 * 
 * - Representing points or directions in 3D space.
 * - Performing vector arithmetic (e.g., addition, subtraction).
 * - Calculating dot products, cross products, and magnitudes.
 * 
 * ## Vector Layout
 * ```
 *   V = (X, Y, Z)
 * ```
 * 
 * ## Common Uses
 * - Physics simulations: Representing positions, velocities, and forces.
 * - Graphics: Representing vertices, normals, and directions.
 * - Mathematics: Performing geometric calculations.
 */
typedef struct Vector3 {
    float X; /**< X component of the vector */
    float Y; /**< Y component of the vector */
    float Z; /**< Z component of the vector */
} Vector3;

/*
    ############################################
    #                                          #
    #                PROTOTYPES                #
    #                                          #
    ############################################
*/

/**
 * @brief Adds two 3D vectors.
 * 
 * Computes the element-wise sum of two vectors:
 * ```
 *   V' = V1 + V2
 *   V'_x = V1_x + V2_x
 *   V'_y = V1_y + V2_y
 *   V'_z = V1_z + V2_z
 * ```
 * 
 * @param v1 The first vector.
 * @param v2 The second vector.
 * @return The resulting vector after addition.
 */
Vector3 Vector3_Add(Vector3 v1, Vector3 v2);

/**
 * @brief Subtracts one 3D vector from another.
 * 
 * Computes the element-wise difference of two vectors:
 * ```
 *   V' = V1 - V2
 *   V'_x = V1_x - V2_x
 *   V'_y = V1_y - V2_y
 *   V'_z = V1_z - V2_z
 * ```
 * 
 * @param v1 The first vector.
 * @param v2 The second vector.
 * @return The resulting vector after subtraction.
 */
Vector3 Vector3_Subtract(Vector3 v1, Vector3 v2);

/**
 * @brief Scales a 3D vector by a scalar.
 * 
 * Multiplies each component of the vector by the scalar:
 * ```
 *   V' = V * scalar
 *   V'_x = V_x * scalar
 *   V'_y = V_y * scalar
 *   V'_z = V_z * scalar
 * ```
 * 
 * @param v The vector to scale.
 * @param scalar The scalar value.
 * @return The scaled vector.
 */
Vector3 Vector3_Scale(Vector3 v, float scalar);

/**
 * @brief Computes the dot product of two 3D vectors.
 * 
 * The dot product is defined as:
 * ```
 *   dot(V1, V2) = (V1_x * V2_x) + (V1_y * V2_y) + (V1_z * V2_z)
 * ```
 * 
 * ## Common Uses
 * - Determining the angle between two vectors.
 * - Checking if two vectors are orthogonal (dot product = 0).
 * - Calculating projections.
 * 
 * @param v1 The first vector.
 * @param v2 The second vector.
 * @return The dot product of the two vectors.
 */
float Vector3_Dot(Vector3 v1, Vector3 v2);

/**
 * @brief Computes the cross product of two 3D vectors.
 * 
 * The cross product is defined as:
 * ```
 *   V' = V1 × V2
 *   V'_x = (V1_y * V2_z) - (V1_z * V2_y)
 *   V'_y = (V1_z * V2_x) - (V1_x * V2_z)
 *   V'_z = (V1_x * V2_y) - (V1_y * V2_x)
 * ```
 * 
 * ## Common Uses
 * - Finding a vector perpendicular to two given vectors.
 * - Calculating surface normals in 3D graphics.
 * 
 * @param v1 The first vector.
 * @param v2 The second vector.
 * @return The resulting vector after the cross product.
 */
Vector3 Vector3_Cross(Vector3 v1, Vector3 v2);

/**
 * @brief Computes the magnitude (length) of a 3D vector.
 * 
 * The magnitude is defined as:
 * ```
 *   |V| = sqrt(V_x^2 + V_y^2 + V_z^2)
 * ```
 * 
 * ## Common Uses
 * - Normalizing vectors.
 * - Calculating distances in 3D space.
 * 
 * @param v The vector whose magnitude is to be computed.
 * @return The magnitude of the vector.
 */
float Vector3_Magnitude(Vector3 v);

/**
 * @brief Normalizes a 3D vector.
 * 
 * Normalization scales the vector to have a magnitude of 1:
 * ```
 *   V' = V / |V|
 * ```
 * If the vector has zero magnitude, the result is undefined.
 * 
 * ## Common Uses
 * - Converting vectors to unit vectors for direction calculations.
 * - Ensuring consistent scaling in physics and graphics.
 * 
 * @param v The vector to normalize.
 * @return The normalized vector.
 */
Vector3 Vector3_Normalize(Vector3 v);

#endif // VECTOR3_H