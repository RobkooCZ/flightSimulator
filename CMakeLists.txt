cmake_minimum_required(VERSION 3.10)
project(FlightSimulator C)

# Compiler settings
set(CMAKE_C_STANDARD 17)
set(CMAKE_C_FLAGS "${CMAKE_C_FLAGS} -Wall -Wextra -Wpedantic -Werror -Wconversion -Wshadow -Wstrict-overflow=5 -Wfloat-equal -Wcast-qual -Wcast-align -Wwrite-strings -Wmissing-prototypes -Wstrict-prototypes -Wold-style-definition -Wredundant-decls -Winline -Wundef -Wswitch-default -Wswitch-enum -Wunreachable-code -Wformat=2 -Winit-self -Wlogical-op -Wduplicated-cond -Wduplicated-branches -Wnull-dereference -fanalyzer -fstack-protector-strong -fsanitize=undefined,address -g -O2 -std=c17 -pedantic -Iinclude")

# Include directories
include_directories(include)

# Find all source files in the src/ directory
file(GLOB SOURCES "src/*.c")

# Create the executable
add_executable(flightSimulator ${SOURCES})

# Link the math library (-lm)
target_link_libraries(flightSimulator m)

# Set the output directory to the build folder
set_target_properties(flightSimulator PROPERTIES RUNTIME_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR})