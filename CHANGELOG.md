# Changelog

Every update to this **Flight Simulator** will be documented in this file.

## [Version 1.2.2] - 16.02.2025

### Added
- windows compilation support using CMake

### Changed
- the compilation flags are now much stricter
- Hugely improved the README.md file

### Fixed
- Many conversion issues that caused precision problems
- Static wing area in calculateLift() function changed to the selected aircraft's wing area
- other minor warnings that were raised due to the stricter compiler

## [Version 1.2.1] - 15.02.2025

### Added
- advanced TAS calculation
- pressure at altitude calculation
- changelog to track updates

### Changed
- made mach calculation more realistic (now based on altitude)
- air pressure calculation at a specific altitude more precise and not limited to the static array

## [Version 1.2] - 14.02.2025

### Added
- Real time controls (yaw, pitch, roll and thrust)
- Logic for afterburner and limiting thrust

### Changed
- lowered the threshold for damping of the oscilations
- moved around some functions (e.g. rawMode() functions from menu.c to controls.c)
- Minor additions to the text renderer (more info)

### Removed
- the damping force (it is useless)

### Fixed
- issues with thrust calculations

## [Version 1.1.2] - 13.02.2025

### Added
- reading aircraft data from file based on user choice

### Changed
- instead of static variables in the physics, picked aircraft's values are used

### Removed
- temporarily removed Makefile modifiaction for windows (not functional)

### Fixed
- oscilations in level flight

## [Version 1.1.1] - 13.02.2025

### Added
- smooth real-time aircraft picking menu
- small changes to the text renderer to fix display issues

## [Version 1.1] - 08.02.2025

### Added
- simple smooth text renderer
- simple physics:
    - Lift
    - AoA
    - Drag
    - Thrust
- helper functions for display and calculations

## [Version 1.0.1] - 03.02.2025

### Added
- 60 FPS main game loop setup

## [Version 1.0.0] - 03.02.2025
### Added
- Initial release (setup)