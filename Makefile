# Compiler and flags
CC = gcc
CFLAGS = -Wall -Wextra -Wpedantic -Werror -Wconversion -Wshadow -Wstrict-overflow=5 -Wfloat-equal -Wcast-qual -Wcast-align -Wwrite-strings -Wmissing-prototypes -Wstrict-prototypes -Wold-style-definition -Wredundant-decls -Winline -Wundef -Wswitch-default -Wswitch-enum -Wunreachable-code -Wformat=2 -Winit-self -Wlogical-op -Wduplicated-cond -Wduplicated-branches -Wnull-dereference -fanalyzer -fstack-protector-strong -fsanitize=undefined,address -g -O2 -std=c17 -pedantic -Iinclude
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
