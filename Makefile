# Compiler and flags
CC = gcc
CFLAGS = -Wall -Wextra -std=c11 -O2 -Iinclude
LDFLAGS = -lm  # Link math library

# Folders
SRC_DIR = src
BUILD_DIR = build

# Source files
SRC = $(wildcard $(SRC_DIR)/*.c)
OBJ = $(patsubst $(SRC_DIR)/%.c, $(BUILD_DIR)/%.o, $(SRC))
BIN = $(BUILD_DIR)/flightSimulator

# Detect OS and set variables
ifeq ($(OS),Windows_NT)
    EXE_EXT = .exe
    RM = del /F /Q
    MKDIR = mkdir
else
    UNAME_S := $(shell uname -s)
    ifeq ($(UNAME_S),Linux)
        EXE_EXT =
        RM = rm -rf
        MKDIR = mkdir -p
    endif
endif

BIN = $(BUILD_DIR)/flightSimulator$(EXE_EXT)

# Default target
all: $(BIN)

# Create build folder if it doesn't exist, then compile
$(BIN): $(OBJ)
    $(MKDIR) $(BUILD_DIR)
    $(CC) $(CFLAGS) -o $(BIN) $(OBJ) $(LDFLAGS)

# Compile each .c file into .o in the build folder
$(BUILD_DIR)/%.o: $(SRC_DIR)/%.c
    $(MKDIR) $(BUILD_DIR)
    $(CC) $(CFLAGS) -c $< -o $@

# Clean build files
clean:
    $(RM) $(BUILD_DIR)$(EXE_EXT)
    $(RM) $(BUILD_DIR)