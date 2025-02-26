cmake_minimum_required(VERSION 3.10)
project(FlightSimulator C)

# Set C standard
set(CMAKE_C_STANDARD 17)
set(CMAKE_C_STANDARD_REQUIRED ON)
set(CMAKE_C_EXTENSIONS OFF)

# Base compiler flags
set(COMMON_FLAGS "-Wall -Wextra -Wpedantic -Werror -Wconversion -Wshadow -Wstrict-overflow=5 -Wfloat-equal -Wcast-qual -Wcast-align -Wwrite-strings -Wmissing-prototypes -Wstrict-prototypes -Wold-style-definition -Wredundant-decls -Winline -Wundef -Wswitch-default -Wswitch-enum -Wunreachable-code -Wformat=2 -Winit-self -Wlogical-op -Wduplicated-cond -Wduplicated-branches -Wnull-dereference -fanalyzer -fstack-protector-strong -g -O2 -pedantic -Iinclude")

# Conditionally add sanitizer flags when not building on Windows
if(NOT WIN32)
    set(SANITIZER_FLAGS "-fsanitize=undefined,address")
else()
    set(SANITIZER_FLAGS "")
endif()

set(CMAKE_C_FLAGS "${COMMON_FLAGS} ${SANITIZER_FLAGS}")

# Include directories
include_directories(include)

# Find all source files in the src/ directory
file(GLOB SOURCES "src/*.c")

# Create the executable
add_executable(flightSimulator ${SOURCES})

# Find SDL2 and SDL2_ttf
find_package(SDL2 REQUIRED)
find_package(SDL2_ttf REQUIRED)
include_directories(${SDL2_INCLUDE_DIRS} ${SDL2_TTF_INCLUDE_DIRS})

# Link math library on UNIX systems
if(UNIX)
    target_link_libraries(
        flightSimulator
        m
        ${SDL2_LIBRARIES}
        ${SDL2_TTF_LIBRARIES}
    )
endif()

# Set the output directory to the build folder
set_target_properties(flightSimulator PROPERTIES RUNTIME_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR})

# Copy font files to the build directory
add_custom_command(
    TARGET flightSimulator POST_BUILD
    COMMAND ${CMAKE_COMMAND} -E make_directory $<TARGET_FILE_DIR:flightSimulator>/fonts
    COMMAND ${CMAKE_COMMAND} -E copy_directory
        ${CMAKE_SOURCE_DIR}/fonts
        $<TARGET_FILE_DIR:flightSimulator>/fonts
)

# MAYBE IN THE FUTURE, NOT RN
# enable_testing()
# find_package(Criterion REQUIRED)
# add_executable(test_main tests/test_main.c)
# target_link_libraries(test_main Criterion::Criterion)
# add_test(NAME test_main COMMAND test_main)