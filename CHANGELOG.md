# Changelog

Every update to this **Flight Simulator** will be documented in this file. Latest at the top, oldest at the bottom.

## Table of contents

- [0.0](#version-000---03022025)
    - [0.0.0](#version-000---03022025)
    - [0.0.1](#version-001---03022025)
- [0.1](#version-01---08022025) - "Physics update"
    - [0.1.1](#version-011---13022025) - "Aircraft picking update part 1"
    - [0.1.2](#version-012---13022025) - "Aircraft picking update part 2"
- [0.2](#version-02---14022025) - "Real time controls"
    - [0.2.1](#version-021---15022025)
    - [0.2.2](#version-022---16022025) - "Major bug fixing update"
    - [0.2.3](#version-023---16022025) - "Windows fix versions"
        - [0.2.3.1](#version-0231---16022025)
        - [0.2.3.2](#version-0232---16022025)
        - [0.2.3.3](#version-0233---16022025)
        - [0.2.3.3.1](#version-02331---16022025)
        - [0.2.3.4](#version-0234---16022025)
        - [0.2.3.4.1](#version-02341---16022025)
    - [0.2.4](#version-024---16022025) - "Wind update"

---

## [Version 0.2.4] - 16.02.2025

### Added
- Wind and turbulence
- Simulation time is added and displayed

### Changed
- Drag and lift is now calculated using relative speed, instead of ground speed of the aircraft

## [Version 0.2.3.4.1] - 16.02.2025

### Fixed
- "˚" not printing correctly on windows (attempt 2 (fixed))

## [Version 0.2.3.4] - 16.02.2025

### Fixed
- "˚" not printing correctly on windows (unsuccessful)

## [Version 0.2.3.3.1] - 16.02.2025

### Fixed
- strict-overflow error in selectAircraft() fix (attempt 2 (successful))

## [Version 0.2.3.3] - 16.02.2025

### Fixed
- strict-overflow error in selectAircraft() fix (unsuccessful)

## [Version 0.2.3.2] - 16.02.2025

### Fixed
- cmake for windows flags fix (-fsanitize)

## [Version 0.2.3.1] - 16.02.2025

### Fixed
- minor windows only conversion errors

## [Version 0.2.3] - 16.02.2025

### Added
- Velocity change based on orientation (yaw, pitch, roll)

### Changed
- Improved AoA calculation, now if you pitch up using controls, you climb steadily

### Fixed
- Some windows only functions
- Minor bugs in controls.c

## [Version 0.2.2] - 16.02.2025

### Added
- windows compilation support using CMake

### Changed
- the compilation flags are now much stricter
- Hugely improved the README.md file

### Fixed
- Many conversion issues that caused precision problems
- Static wing area in calculateLift() function changed to the selected aircraft's wing area
- other minor warnings that were raised due to the stricter compiler

## [Version 0.2.1] - 15.02.2025

### Added
- advanced TAS calculation
- pressure at altitude calculation
- changelog to track updates

### Changed
- made mach calculation more realistic (now based on altitude)
- air pressure calculation at a specific altitude more precise and not limited to the static array

## [Version 0.2] - 14.02.2025

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

## [Version 0.1.2] - 13.02.2025

### Added
- reading aircraft data from file based on user choice

### Changed
- instead of static variables in the physics, picked aircraft's values are used

### Removed
- temporarily removed Makefile modifiaction for windows (not functional)

### Fixed
- oscilations in level flight

## [Version 0.1.1] - 13.02.2025

### Added
- smooth real-time aircraft picking menu
- small changes to the text renderer to fix display issues

## [Version 0.1] - 08.02.2025

### Added
- simple smooth text renderer
- simple physics:
    - Lift
    - AoA
    - Drag
    - Thrust
- helper functions for display and calculations

## [Version 0.0.1] - 03.02.2025

### Added
- 60 FPS main game loop setup

## [Version 0.0.0] - 03.02.2025
### Added
- Initial release (setup)