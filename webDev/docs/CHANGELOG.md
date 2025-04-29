# WebDev branch Changelog

## Description:

This is the changelog for this branch, where every change to the code (so not EVERY commit is listed here) will be added here. The latest update is at the top of the versions, the oldest update at the bottom.

The table of contents is here for quick navigation and organization. It has the oldest version at the top, and newest at the bottom to ensure I can indentate to group updates together, for example `0.x.y` updates with `0.x` .
It also includes a short name for the update.

---

## Template for each version entry:

### [VERSION X.Y.Z] - DD.MM.YYYY

#### Added
- Brief description of new features or additions.

#### Changed
- Brief description of changes or improvements.

#### Fixed
- Brief description of bug fixes.

#### Removed
- Brief description of removals or deprecations.

---

## Table of Contents

- [0.0](#version-00---06032025) – "Initial commit"
- [0.1](#version-01---03042025) – "Styling & Initial features"
- [0.2](#version-02---08042025) – "RoleManager & Documentation"
    - [0.2.1](#version-021---08042025) – "School admin bugfix"
    - [0.2.2](#version-022---08042025) – "Database security & docs"
    - [0.2.3](#version-023---09042025) – "CSRF protection"
- [0.3](#version-03---10042025) – "Auth class & PHPDoc improvements"
    - [0.3.1](#version-031---10042025) – "Auth logic consolidation"
- [0.4](#version-04---15042025) – "Custom exceptions system"
    - [0.4.1](#version-041---15042025) – "Authorization logic update"
    - [0.4.2](#version-042---16042025) – "Bootstrap class introduction"
- [0.5](#version-05---18042025) – "Logger class & Logging system"
- [0.6](#version-06---19042025) – "File structure changes"
    - [0.6.1](#version-061---20042025) – "Database error handling improvements"
- [0.7](#version-07---21042025) – "User class"
    - [0.7.1](#version-071---23042025) – "Documentation standards for PHP"
    - [0.7.2](#version-072---25042025) – "AJAX/Backend separation & Constants"
        - [0.7.2.1](#version-0721---25042025) – "JavaScript documentation standards"
    - [0.7.3](#version-073---28042025) – "Bootstrap & Security overhaul"
    - [0.7.4](#version-074---29042025) - "Changelog & version fix"

---

## ALPHA VERSIONS

### [VERSION 0.7.4] - 29.04.2025

#### Added
- Completed missing documentation for several files.
- Added a correct and detailed CHANGELOG to improve project tracking (*not* a codebase change but significant for context).

#### Fixed
- Corrected missing or incorrect `@since` and `@version` tags across all files.

---

### [VERSION 0.7.3] - 28.04.2025

#### Added
- Introduced new initialization methods to the `Bootstrap` class.
- Added new security methods to the `Bootstrap` class.
- Updated all public endpoints to utilize the `Bootstrap` class.
- Implemented initialization methods for the `Logger` and `Database` classes.
- Added a method to log fatal failures in case the program shuts down.
- Implemented checks to ensure PHP 8.2 or above is used before code execution.

#### Removed
- Migrated the following to the `Bootstrap` class:
    - Global exception handlers in each file.
    - `declare(strict_types=1)` in each file.

---

### [VERSION 0.7.2.1] - 25.04.2025

#### Added
- Established documentation standards for JavaScript.
- Added comments to all JavaScript files according to the new standards.

---

### [VERSION 0.7.2] - 25.04.2025

#### Added
- Separated files for JavaScript (AJAX) and PHP backend to handle requests.
    - Includes AJAX for sending and receiving tables in `adminSchoolPage.php`.
- Developed a standardized JavaScript class for sending AJAX requests.
- Created a standardized PHP response class to ensure modularity.
- Added `constants.json` for shared constants and variables across the codebase.
- Provided `constants.js` and `constants.php` to export loaded constants for use in JavaScript and PHP.

#### Changed
- Refactored backend code (mainly PHP) to send JSON responses instead of plaintext; functionality remains unchanged.
- Renamed IDs and classes in HTML within `adminSchoolPage.php`.
    - Updated the corresponding CSS file to use the new names.
- Updated `adminSchoolPage.php`, `adminSchoolAjax.php`, and related files to use shared constants for simplicity and compatibility.

#### Removed
- Removed some `@since` and `@version` tags due to incorrect changelog references.

---

### [VERSION 0.7.1] - 23.04.2025

#### Added
- Introduced a documentation standards file outlining doc standards for the codebase.

#### Changed
- Updated all files to comply with new documentation standards, including:
    - File PHPDoc blocks.
    - Object PHPDoc blocks.
    - Method PHPDoc blocks.
    - Standardized tags, structure, and language.

---

### [VERSION 0.7] - 21.04.2025

#### Added
- Implemented `User` class with methods for:
    - Recording user activity.
    - Managing status changes and roles.
    - Session management.
    - User data management.
    - User caching to reduce database queries.
    - Validation methods for user data to maximize security.
- Integrated user activity recording in code that updates the database `lastActivityAt` column.
- Added basic JavaScript to detect header link clicks and send AJAX requests to log user activity.

#### Changed
- Made minor logic changes throughout the codebase.

#### Fixed
- Corrected outdated comments referencing the old file structure.
- Fixed PHPDoc inconsistencies and incorrect documentation.

#### Removed
- Removed unnecessary `Logger` calls from `auth.php`.

---

### [VERSION 0.6.1] - 20.04.2025

#### Added
- Added brief comments within methods for clarity.

#### Changed
- Wrapped the contents of `query` and `execute` methods in try-catch blocks to prevent silent failures.

#### Fixed
- Updated old comments referencing the previous file structure.

---

### [VERSION 0.6] - 19.04.2025

#### Added
- Included correct `use` statements in each file according to the new structure.
- Added try-catch for loading `.env` file in the `Database.php` class.

#### Changed
- Updated `composer.json` configuration.
- Performed major file restructuring to follow PSR-4 standards.
- Placed each class and subclass in their own folders under `/src`.
- Separated each subclass, enum, or additional class into individual files.
    - Ensured each file in `/src` contains exactly one class, enum, or similar entity.
    - Achieved PSR-4 compliance.

---

### [VERSION 0.5] - 18.04.2025

#### Added
- Updated `.gitattributes` to ensure correct Linguist interpretation.
    - Now ignores external dependencies such as SDL2 and vendor contents.
- Introduced a central `Logger` class and subclasses for logging.
- Added 11 log levels to indicate log severity.
- Implemented custom colored, clean console output.
- Enabled minimum log level selection to filter logs.
- Added `ConsoleLogger` (with `FileLogger` planned for future updates).
- Enhanced logging in all non-endpoint files for debugging.
    > *Non-endpoint refers to files not publicly accessible, e.g., `Database.php`, `AppException.php`.*
- Added variable comments to some classes.
- Refactored custom exceptions to use new `Logger` class methods.
- Added class-wide PHPDoc comments.

#### Changed
- Updated changelog to include `.env` information.
- Made minimal formatting changes.

#### Removed
- Removed `use` statements as all class files previously shared a single directory.
- Removed logic (such as try-catch) and comments from some methods in `Database.php`.

---

### [VERSION 0.4.2] - 16.04.2025

#### Added
- Introduced a simple `Bootstrap` class to initialize timezone and `.env` variables.
- Added bootstrap initialization method to `router.php`.

#### Changed
- Made minor formatting changes in `router.php`.

---

### [VERSION 0.4.1] - 15.04.2025

#### Changed
- Updated authorization logic on `adminPage` and `adminPageSchool`.

#### Fixed
- Fixed exceptions caused by required parameters following optional ones.
    - Reordered arguments in exception methods.
- Updated documentation to reflect these changes.

---

### [VERSION 0.4] - 15.04.2025

#### Added
- Added `declare(strict_types=1)` to all files for type safety.
- Introduced central `AppException` class for custom exceptions.
- Added 11 custom exceptions:
    - `UserException`: General user-related errors.
    - `ValidationException`: Input validation errors.
    - `AuthenticationException`: Authentication failures.
    - `AuthorizationException`: Authorization failures.
    - `ServerException`: General server-side errors.
    - `DatabaseException`: Database-related errors.
    - `ApiException`: API interaction errors.
    - `ConfigurationException`: Configuration-related errors.
    - `FileException`: File operation errors.
    - `PhpException`: PHP-related errors.
    - `LogicException`: Application logic errors.
- Implemented custom logic for each exception to log necessary debug information.
- Added comprehensive documentation for all methods, classes, and subclasses.
- Provided default implementation for each exception method, overridden by subclasses.
- Wrapped non-PHP exceptions with custom exceptions.
- Added global exception handler at the top of every file.
- Consolidated all custom exception logic into a single file using an `init` method, allowing use of all subclass methods without separate files.

#### Changed
- Refactored logic for improved maintainability; functionality remains unchanged.
- Improved formatting in several files.

#### Fixed
- Fixed authorization block on `adminPage` and `adminSchoolPage`.

#### Changed
- Updated all files to use custom exceptions instead of PHP built-in exceptions.

---

### [VERSION 0.3.1] - 10.04.2025

#### Changed
- Consolidated all authentication functionality into `auth.php`.

#### Removed
- Removed authentication logic from `login.php`, `logout.php`, and `register.php`.
    - These files now contain only HTML and miscellaneous content.

---

### [VERSION 0.3] - 10.04.2025

#### Added
- Added PHPDoc comments to methods in the `Database` class.
- Introduced `Auth` class for login, logout, and registration logic.
    - Includes comprehensive PHPDoc comments.
- Added PHPDoc comment to the `RoleManager` class method.
- Added PHPDoc comments to methods in the `Table` and `TableRenderer` classes.
- Added a message to the login form.

#### Changed
- Made minor structural changes to the `TableRenderer` class.
- Refactored `login.php`, `register.php`, and `logout.php` to use new `Auth` methods.

#### Fixed
- Corrected visibility keywords in the `CSRF` class.
- Standardized formatting in the `CSRF` class.
- Fixed minor formatting issues in the `Table` class.
- Fixed invalid use of `"` in `actionScript.php`.

---

### [VERSION 0.2.3] - 09.04.2025

#### Added
- Introduced `CSRF` class for form security.
- Integrated CSRF protection into login and registration forms.

---

### [VERSION 0.2.2] - 08.04.2025

#### Changed
- Enhanced security and documentation of the `Database` class.

---

### [VERSION 0.2.1] - 08.04.2025

#### Fixed
- Fixed issue with adding a user on the school admin page.

---

### [VERSION 0.2] - 08.04.2025

#### Added
- Added extensive documentation using PHPDoc.
- Introduced `RoleManager` class.

#### Changed
- Restructured backend files, such as `Database.php`, to be class oriented
- Renamed functions to support namespaces and PSR-4 compliance.
- Updated scripts to use new classes and methods.

#### Removed
- Disabled public error display in production.
- Removed `blablabla.php`.

---

### [VERSION 0.1] - 03.04.2025

#### Added
- Implemented styling for all public sites.
- Added CSS variables in `theme.css` for consistent styling.
- Created template footer and header for all public sites.
- Developed file with functions for database connectivity and operations.
- Added `.env` file.
- Created action script to add data to the database on the school admin page.
- Developed `TableFunctions.php` for table/form operations and rendering.
- Added `login.php` with HTML and PHP for user login.
- Created simple `logout.php` script.
- Developed `register.php` with HTML and partial logic (registration not fully implemented).
- Added admin page to display database tables.
- Developed admin school page for school project requirements:
    - Supports table and form display.
    - Includes AJAX for seamless updates.
- Added basic homepage (`index.php`) displaying general information.
- Implemented `router.php` to route URI requests and static files.

---

### [VERSION 0.0] - 06.03.2025

#### Added
- Initial commit.
- Added `README.md` for this branch.

---