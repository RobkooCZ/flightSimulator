## 📄 PHP Documentation Standards

This file outlines general documentation practices for PHP files, classes, and methods.

---

### File-Level DocBlock (PHP)

```php
/**
 * [Short one-line summary of the file’s purpose]
 *
 * [Optional: brief longer description if needed]
 *
 * @file [filename.php]
 * 
 * @since Which version did this file appear in
 * @package [logical grouping, e.g., Auth, Logger, FlightSimWeb]
 * @author Robkoo
 * @license TBD - decide before public release
 * @version [e.g., 0.3.6]
 * @see [related files or classes] (optional)
 * @todo [future tasks] (optional)
 */
```

---

### Class-Level DocBlock (PHP)

```php
/**
 * [What the class does in one line]
 *
 * [Optional: usage notes or design intentions]
 *
 * @package [same package as file]
 * @since [version introduced]
 * @see [related classes or traits] (optional)
 * @todo [planned additions or fixes] (optional)
 * @throws [ExceptionType] when XYZ (optional)
 */
class YourClass {
    // ...
}
```

---

### Method-Level DocBlock (PHP)

```php
/**
 * [Description of what this method does]
 *
 * @param [type] $paramName [description of the parameter]
 * @return [type] [description of what it returns, with structure if needed]
 * @throws [ExceptionType] if [condition] (only if the method itself, functions it uses or methods it uses throw exceptions)
 *
 * ### Example
 * 
 * ```php
 * $result = $yourClass->yourMethod($param);
 * // Example structure (if returning an array, object, etc.):
 * // For arrays:
 * // [
 * //     ['key' => 'value', 'key2' => 'value2'],
 * //     ['key' => 'value3', 'key2' => 'value4'],
 * //     ...
 * // ]
 * // For objects:
 * // $object->property // Accessing object properties
 * echo $result[0]['key']; // Example output: value
 * ```
 */
public function yourMethod([type] $paramName): [returnType] {
    // ...
}
```

---

### @package Guidelines (PHP)

Use `@package` to organize code into logical groups. Examples:

- `FlightSimWeb` – for general web-related code  
- `FlightSimGame` – for C engine bindings  
- `Logger`, `Auth`, `API`, etc. – for subsystems  

---

### General Guidelines (PHP)

- Keep it brief but informative  
- Skip tags that aren’t relevant  
- Use `@todo` generously for tracking ideas  
- Stick to this format unless there's a solid reason not to  

---

## 📜 JavaScript Documentation Standards (JSDoc)

Document JS functions, classes, and files using this JSDoc format:

---

### File-Level JSDoc (JS)

```js
/**
 * [Short summary of what this file does]
 *
 * @file [filename.js]
 * @since [version (e.g. 0.7.3)]
 * @package [logical grouping, e.g., Constants, AJAX, e.g.]
 * @author Robkoo
 * @license TBD
 * @version [current version the file is on]
 * @see [related files/modules] (optional)
 * @todo [future improvements or tasks] (optional)
 */
```

---

### Function-Level JSDoc (JS)

```js
/**
 * [What the function does]
 *
 * @param {Type} paramName - [Description of the parameter]
 * @param {Type} [optionalParam] - [Optional param, include square brackets]
 * @returns {Type} [What the function returns]
 * @throws {ErrorType} [Condition that causes it] (if applicable)
 *
 * @example
 * const result = yourFunction(arg);
 * console.log(result); // expected output
 */
function yourFunction(paramName) {
    // ...
}
```

---

### Class-Level JSDoc (JS)

```js
/**
 * [What the class does]
 *
 * @class
 * @classdesc [Optional longer description of the class behavior or purpose]
 * @example
 * const obj = new YourClass();
 */
class YourClass {
    /**
     * @param {Type} param - [Constructor param]
     */
    constructor(param) {
        // ...
    }
}
```

---

### General Guidelines (JS)

- Be consistent with types and structure  
- Always use `@example` if logic is non-trivial  
- `@typedef` or `@callback` can be used for complex structures