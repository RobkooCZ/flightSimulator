/**
 * inputValidator JS file.
 * 
 * Contains functions to validate user input.
 * 
 * Common examples include: Usernames, passwords, bios.
 *
 * @file inputValidator.js
 * @since 0.7.7
 * @package Utils
 * @author Robkoo
 * @license TBD
 * @version 0.7.7
 */

/**
 * Validates the provided username to ensure no harmful input is sent to the backend.
 *
 * @param {string} username - The username to be validated.
 * @returns {object} Object containing a success flag, a message intended to show to the user, and a backend message.
 * @throws {TypeError} Throws if the input is not a string.
 */
export function validateUserName(username){
    // check for correct type of argument
    if (typeof username !== 'string') throw new TypeError("Provided argument isn't a string.");

    // trim the whitespace
    username = username.trim();

    // check length
    if (username.length < 3 || username.length > 20){
        return {
            success: false,
            message: "Username has invalid length. Has to be 3 to 20 characters long.",
            backendMessage: "Username has invalid length."
        }
    }

    // check contents using a regex
    const regex = /^[a-zA-Z0-9_]+$/;
    if (!regex.test(username)){
        return {
            success: false,
            message: "Invalid username provided.",
            backendMessage: "Username didn't pass the regex validation test. Regex: /^[a-zA-Z0-9_]+$/"
        }
    }

    // all good, return success
    return {
        success: true,
        message: "Username is valid.",
        backendMessage: "Sanitization passed."
    }
}

/**
 * Validates a bio by checking for length and basic dangerous patterns.
 *
 * @param {string} bio - The bio string to validate.
 * @returns {object} Object with success flag, user message, and backend message.
 * @throws {TypeError} If input is not a string.
 */
export function validateBio(bio) {
    if (typeof bio !== 'string') throw new TypeError("Provided argument isn't a string.");

    // trim the whitespace
    bio = bio.trim();

    // length check 
    if (bio.length > 514) {
        return {
            success: false,
            message: "Bio is too long. Max is 512 characters.",
            backendMessage: "Bio length exceeds maximum allowed."
        }
    }

    // basic xss prevention
    const regex = /<script.*?>/i;
    if (regex.test(bio)){
        return {
            success: false,
            message: "Bio contains forbidden content.",
            backendMessage: "Bio didn't pass the regex validation test. Regex: /<script.*?>/i"
        }
    }

    // everything passed, return success
    return {
        success: true,
        message: "Bio is valid.",
        backendMessage: "Sanitization passed."
    }
}