#ifndef MATRIX3_H
#define MATRIX3_H

// Include vector3 library
#include "vector3.h"

/*
    ############################################
    #                                          #
    #                  STRUCT                  #
    #                                          #
    ############################################
*/

/**
 * @brief Represents a 3x3 matrix.
 * 
 * This structure stores a row-major 3×3 matrix, commonly used for 2D/3D transformations such as:
 * 
 * - Rotations (e.g., rotating a vector in space)
 * 
 * - Scaling (e.g., resizing an object)
 * 
 * - Shearing & Skewing (e.g., distorting a shape)
 * 
 * - General Linear Transformations
 * 
 * ## Matrix Layout (Row-Major Order)
 * ```
 *   | m00  m01  m02 |
 *   | m10  m11  m12 |
 *   | m20  m21  m22 |
 * ```
 * 
 * ## Common Operations
 * 
 * - Matrix-Matrix Multiplication → Combines transformations.
 * 
 * - Matrix-Vector Multiplication → Applies a transformation to a vector.
 * 
 * - Transpose & Inversion → Used in physics, simulations, and rendering.
 */
typedef struct Matrix3 {
    float m[3][3];  /**< Row-major 3×3 matrix elements */
} Matrix3;

/*
    ############################################
    #                                          #
    #                PROTOTYPES                #
    #                                          #
    ############################################
*/

/**
 * @brief Creates a 3x3 identity matrix.
 * 
 * The identity matrix is:
 * ```
 * | 1  0  0 |
 * | 0  1  0 |
 * | 0  0  1 |
 * ```
 * When multiplied with another matrix or vector, it leaves them unchanged.
 * 
 * @return A 3x3 identity matrix.
 */
Matrix3 Matrix3_Identity(void);

/**
 * @brief Multiplies two 3x3 matrices.
 * 
 * This function performs standard matrix multiplication in row-major order.
 * 
 * The result is a matrix that applies the transformation of `m1`, followed by `m2`.  
 * 
 * ## Matrix Multiplication Formula
 * 
 * Given two matrices:
 * ```
 *   M1 = | m00  m01  m02 |      M2 = | m00  m01  m02 |
 *        | m10  m11  m12 |           | m10  m11  m12 |
 *        | m20  m21  m22 |           | m20  m21  m22 |
 * ```
 * The resulting matrix `M' = M1 * M2` is computed as:
 * ```
 *   | (m00*m00 + m01*m10 + m02*m20)   (m00*m01 + m01*m11 + m02*m21)   (m00*m02 + m01*m12 + m02*m22) |
 *   | (m10*m00 + m11*m10 + m12*m20)   (m10*m01 + m11*m11 + m12*m21)   (m10*m02 + m11*m12 + m12*m22) |
 *   | (m20*m00 + m21*m10 + m22*m20)   (m20*m01 + m21*m11 + m22*m21)   (m20*m02 + m21*m12 + m22*m22) |
 * ```
 * 
 * ## Common Uses
 * 
 * - Combining transformations: Multiplying two matrices combines their effects.
 * 
 * - Rotations & Scaling: Successive transformations can be chained efficiently.
 * 
 * - Transformation Pipelines: Used in physics, graphics, and simulations.
 * 
 * @param M1 The first matrix.
 * @param M2 The second matrix.
 * @return The resulting 3x3 matrix product.
 */
Matrix3 Matrix3_Multiply(Matrix3 M1, Matrix3 M2);

/**
 * @brief Applies a 3x3 transformation matrix to a 3D vector.
 * 
 * This function performs matrix-vector multiplication: `V' = M * V`.
 * 
 * ## Transformation Formula
 * ```
 *   | V'_x |   =   | m00  m01  m02 |   *   | V_x |
 *   | V'_y |       | m10  m11  m12 |       | V_y |
 *   | V'_z |       | m20  m21  m22 |       | V_z |
 * ```
 * Expanded form:
 * ```
 *   V'_x = (m00 * V_x) + (m01 * V_y) + (m02 * V_z)
 *   V'_y = (m10 * V_x) + (m11 * V_y) + (m12 * V_z)
 *   V'_z = (m20 * V_x) + (m21 * V_y) + (m22 * V_z)
 * ```
 * 
 * ## Common Uses
 * 
 * - Rotations: If `M` is a rotation matrix, `V'` is the rotated vector.
 * 
 * - Scaling: If `M` contains scaling factors, `V'` is the scaled vector.
 * 
 * - Shearing & Custom Transforms: Can be used for any linear transformation.
 * 
 * @param M The transformation matrix.
 * @param V The vector to transform.
 * @return The transformed vector.
 */
Vector3 Matrix3_Transform(Matrix3 M, Vector3 V);

/**
 * @brief Computes the transpose of a 3x3 matrix.
 * 
 * The transpose swaps rows and columns.
 * 
 * ## Original Matrix
 * ```
 *   | m00  m01  m02 |
 *   | m10  m11  m12 |
 *   | m20  m21  m22 |
 * ```
 * 
 * ## Transposed Matrix
 * ```
 *   | m00  m10  m20 |
 *   | m01  m11  m21 |
 *   | m02  m12  m22 |
 * ```
 * 
 * @param M The matrix to transpose.
 * @return The transposed matrix.
 */
Matrix3 Matrix3_Transpose(Matrix3 M);

#endif // MATRIX3_H