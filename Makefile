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

# Default target
all: $(BIN)

# Create build folder if it doesn't exist, then compile
$(BIN): $(OBJ)
	mkdir -p $(BUILD_DIR)
	$(CC) $(CFLAGS) -o $(BIN) $(OBJ) $(LDFLAGS)

# Compile each .c file into .o in the build folder
$(BUILD_DIR)/%.o: $(SRC_DIR)/%.c
	mkdir -p $(BUILD_DIR)
	$(CC) $(CFLAGS) -c $< -o $@

# Clean build files
clean:
	rm -rf $(BUILD_DIR)
