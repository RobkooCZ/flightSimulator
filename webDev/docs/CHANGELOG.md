# Changelog

Every update to this website about my flight simulator will be documented in this file. Latest at the top, oldest at the bottom.

## Table of contents

- [0.0](#version-00---06032025) - "Project Setup"
    - [0.0.1](#version-001---06032025) - "Initial Commit"
- [0.1](#version-01---03042025) - "Web Interface Base"
    - [0.1.1](#version-011---05042025) - "Admin Fix"
- [0.2](#version-02---08042025) - "Core System Rewrite"
    - [0.2.1](#version-021---15042025) - "Exception System"
    - [0.2.2](#version-022---18042025) - "Logging System"
    - [0.2.3](#version-023---19042025) - "File Restructure"
    - [0.2.4](#version-024---19042025) - "Auth Documentation"
    - [0.2.5](#version-025---20042025) - "Bootstrap Implementation"
    - [0.2.6](#version-026---20042025) - "User Logic Fix"
- [0.3](#version-03---21042025) - "User Management Update"
    - [0.3.1](#version-031---23042025) - "Documentation Update"
    - [0.3.2](#version-032---24042025) - "Timezone Fix"
    - [0.3.3](#version-033---25042025) - "Database Connection Fix"
    - [0.3.4](#version-034---26042025) - "CSRF Implementation"

---

## [Version 0.3.4] - 26.04.2025

### Added
- CSRF token validation in form submissions
- Basic input sanitization functions

## [Version 0.3.3] - 25.04.2025

### Fixed
- Database connection timeout handling
- Error catching in database operations

## [Version 0.3.2] - 24.04.2025

### Fixed
- Timezone inconsistencies in logger timestamps
- DateTime format standardization across application

## [Version 0.3.1] - 23.04.2025

### Changed
- Standardized code comments and documentation
- Implemented consistent PHPDoc blocks
- Added missing method documentation

## [Version 0.3] - 21.04.2025

### Added
- User class with registry pattern
- Activity tracking system
- Role-based authorization
- Session state management
- Standardized CSS classes for navigation

### Changed
- Link handling in header template
- Form submission tracking

## [Version 0.2.6] - 20.04.2025

### Fixed
- User role validation edge cases
- Authentication workflow error handling

## [Version 0.2.5] - 20.04.2025

### Added
- Bootstrap class for application initialization
- Centralized configuration management

## [Version 0.2.4] - 19.04.2025

### Changed
- Auth class documentation improvements
- Login/register method documentation

## [Version 0.2.3] - 19.04.2025

### Changed
- Moved core classes to appropriate namespaces
- Restructured project directories (php → src)
- Updated composer configuration

## [Version 0.2.2] - 18.04.2025

### Added
- Console logger with ANSI color support
- Comprehensive logging system
- Route logging functionality

## [Version 0.2.1] - 15.04.2025

### Added
- Custom exception handling system
- Strict type declarations
- Validation system for form inputs

## [Version 0.2] - 08.04.2025

### Added
- Database configuration management
- Table rendering system
- Autoloading setup

### Changed
- Converted procedural code to OOP
- Implemented proper namespacing
- Created database abstraction layer

## [Version 0.1.1] - 05.04.2025

### Fixed
- School admin page form submission
- Data persistence in admin interface

## [Version 0.1] - 03.04.2025

### Added
- Basic routing system
- Authentication system (login/register)
- Admin interface
- School admin page with AJAX
- Theme system (light/dark)
- Header and footer components
- Environment configuration

## [Version 0.0.1] - 06.03.2025

### Added
- Initial repository setup
- Project structure
- Basic documentation
- Git configuration