# Compiler and flags
CC = gcc
CFLAGS = -Wall -Wextra -Wpedantic -Werror -Wconversion -Wshadow -Wstrict-overflow=5 \
         -Wfloat-equal -Wcast-qual -Wcast-align -Wwrite-strings -Wmissing-prototypes \
         -Wstrict-prototypes -Wold-style-definition -Wredundant-decls -Winline -Wundef \
         -Wswitch-default -Wswitch-enum -Wunreachable-code -Wformat=2 -Winit-self \
         -Wlogical-op -Wduplicated-cond -Wduplicated-branches -Wnull-dereference \
         -fanalyzer -fstack-protector-strong -fsanitize=undefined,address -g -O2 \
         -std=c17 -pedantic -Iinclude $(shell pkg-config --cflags sdl2 SDL2_ttf)

LDFLAGS = -lm $(shell pkg-config --libs sdl2 SDL2_ttf)  # Link math and SDL2 libraries

# Folders
SRC_DIR = src
BUILD_DIR = build
FONTS_DIR = fonts

# Source files
SRC = $(wildcard $(SRC_DIR)/*.c)
OBJ = $(patsubst $(SRC_DIR)/%.c, $(BUILD_DIR)/%.o, $(SRC))
BIN = $(BUILD_DIR)/flightSimulator

# Default target
all: $(BIN)

# Create build folder if it doesn't exist, copy fonts folder, then compile
$(BIN): $(OBJ)
	mkdir -p $(BUILD_DIR)
	$(CC) $(CFLAGS) -o $(BIN) $(OBJ) $(LDFLAGS)
	# Copy the fonts folder into the build directory
	cp -r $(FONTS_DIR) $(BUILD_DIR)/

# Compile each .c file into .o in the build folder
$(BUILD_DIR)/%.o: $(SRC_DIR)/%.c
	mkdir -p $(BUILD_DIR)
	$(CC) $(CFLAGS) -c $< -o $@

# Clean build files
clean:
	rm -rf $(BUILD_DIR)