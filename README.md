# Robkoo's Flight Simulator

A command-line based flight simulator with real-time controls and simple physics.

## Author

Created by Robkoo

## Table of Contents

- [Changelog](#changelog)
- [Documentation](#documentation)
- [Requirements](#requirements)
    - [Linux (GCC) Requirements](#linux-gcc-requirements)
        - [Build Tool Requirements](#build-tool-requirements)
    - [Windows (MinGW-w64) Requirements](#windows-mingw-w64-requirements)
- [Compilation Instructions](#compilation-instructions)
    - [Linux (gcc)](#linux-gcc)
    - [Windows (MinGW)](#windows-mingw)
- [Notes](#notes)

---

## Changelog

For a detailed list of changes, please refer to the [CHANGELOG.md](CHANGELOG.md) file.

## Documentation

If you're curious about the physics and math behind this simulator, you can read the [documentation](DOCUMENTATION.md) file.

---

## Requirements

### Linux (GCC) Requirements

Ensure you have **GCC** installed. Install it as shown below:

#### Arch Linux
```sh
sudo pacman -S base-devel gcc
```

#### Ubuntu/Debian
```sh
sudo apt update && sudo apt install build-essential
```

#### Fedora
```sh
sudo dnf install @development-tools gcc
```

Verify installation:
```sh
gcc --version
```

#### Build Tool Requirements

You need either **Make** or **CMake**.

##### Make

Install **Make** as follows:

**Arch Linux:**
```sh
sudo pacman -S make
```

**Ubuntu/Debian:**
```sh
sudo apt update && sudo apt install make
```

**Fedora:**
```sh
sudo dnf install make
```

Verify installation:
```sh
make --version
```

##### CMake

Install **CMake** using:

**Arch Linux:**
```sh
sudo pacman -S cmake
```

**Ubuntu/Debian:**
```sh
sudo apt update && sudo apt install cmake
```

**Fedora:**
```sh
sudo dnf install cmake
```

Verify installation:
```sh
cmake --version
```

---

### Windows (MinGW-w64) Requirements

Ensure you have **MinGW-w64** and **CMake** installed.

1. Download **MinGW-w64** from [mingw-w64.org](https://www.mingw-w64.org/downloads/) and install it.
2. Add MinGW binaries to **PATH**:
```sh
set PATH=C:\path\to\mingw64\bin;%PATH%
```
> Note: See [Note 1](#note-1).
3. Download **CMake** from [cmake.org](https://cmake.org/download/) and install it.
4. Ensure CMake binaries are in **PATH**:
```sh
set PATH=C:\path\to\cmake\bin;%PATH%
```
> Note: See [Note 1](#note-1).

Verify installations:
```sh
gcc --version
```
```sh
cmake --version
```

---

## Compilation Instructions

### Linux (gcc)

#### Using Make

1. Navigate to the project directory.
2. Compile the project:
```sh
make
```
3. Run the simulator:
```sh
./build/flightSimulator
```

#### Using CMake

1. Navigate to the project directory.
2. Compile the project:
```sh
cmake -B build && cmake --build build
```
3. Run the simulator:
```sh
./build/flightSimulator
```

---

### Windows (MinGW)

#### Command Prompt (CMD)

1. Navigate to the project directory.
2. Compile the project:
```sh
cmake -G "MinGW Makefiles" -B build && cmake --build build
```
3. Run the simulator:
```sh
build\flightSimulator.exe
```

#### PowerShell

1. Navigate to the project directory.
2. Compile the project:
```sh
cmake -G "MinGW Makefiles" -B build ; cmake --build build
```
3. Run the simulator:
```sh
.\build\flightSimulator.exe
```

---

## Notes

<a name="note-1"></a>
> Note 1: To make these changes permanent, you can add them to your environment variables manually (through System Properties > Environment Variables). If you add it to your PATH manually, make sure you restart your terminal or your computer for the changes to take effect.
