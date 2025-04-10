// include matrix3 header file
#include "matrix3.h"

// include standard libraries
#include <stdio.h>
#include <math.h>

// include SIMD intrinsics
// #include <immintrin.h>


/*
    ############################################
    #                                          #
    #               DEFINITIONS                #
    #                                          #
    ############################################
*/

Matrix3 Matrix3_Identity(void){
    return (Matrix3){
        {1, 0, 0},
        {0, 1, 0},
        {0, 0, 1}
    };
}

Matrix3 Matrix3_Multiply(Matrix3 M1, Matrix3 M2){
    return (Matrix3){
        {M1.m[0][0] * M2.m[0][0] + M1.m[0][1] * M2.m[1][0] + M1.m[0][2] * M2.m[2][0], M1.m[0][0] * M2.m[0][1] + M1.m[0][1] * M2.m[1][1] + M1.m[0][2] * M2.m[2][1], M1.m[0][0] * M2.m[0][2] + M1.m[0][1] * M2.m[1][2] + M1.m[0][2] * M2.m[2][2]},
        {M1.m[1][0] * M2.m[0][0] + M1.m[1][1] * M2.m[1][0] + M1.m[1][2] * M2.m[2][0], M1.m[1][0] * M2.m[0][1] + M1.m[1][1] * M2.m[1][1] + M1.m[1][2] * M2.m[2][1], M1.m[1][0] * M2.m[0][2] + M1.m[1][1] * M2.m[1][2] + M1.m[1][2] * M2.m[2][2]},
        {M1.m[2][0] * M2.m[0][0] + M1.m[2][1] * M2.m[1][0] + M1.m[2][2] * M2.m[2][0], M1.m[2][0] * M2.m[0][1] + M1.m[2][1] * M2.m[1][1] + M1.m[2][2] * M2.m[2][1], M1.m[2][0] * M2.m[0][2] + M1.m[2][1] * M2.m[1][2] + M1.m[2][2] * M2.m[2][2]}
    };
}

Vector3 Matrix3_Transform(Matrix3 M, Vector3 V){
    return (Vector3){
        (M.m[0][0] * V.X + M.m[0][1] * V.Y + M.m[0][2] * V.Z),
        (M.m[1][0] * V.X + M.m[1][1] * V.Y + M.m[1][2] * V.Z),
        (M.m[2][0] * V.X + M.m[2][1] * V.Y + M.m[2][2] * V.Z)
    };
}

Matrix3 Matrix3_Transpose(Matrix3 M){
    return (Matrix3){
        {M.m[0][0], M.m[1][0], M.m[2][0]},
        {M.m[0][1], M.m[1][1], M.m[2][1]},
        {M.m[0][2], M.m[1][2], M.m[2][2]}
    };
}