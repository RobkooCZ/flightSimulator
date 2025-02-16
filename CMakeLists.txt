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

# Link math library on UNIX systems
if(UNIX)
    target_link_libraries(flightSimulator m)
endif()

# Set the output directory to the build folder
set_target_properties(flightSimulator PROPERTIES RUNTIME_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR})