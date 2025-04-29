/**
 * Utility for standardized AJAX requests and validation.
 *
 * Provides a static class for sending AJAX requests with built-in validation for data, HTTP methods, and headers.
 * Ensures all requests and responses follow a consistent structure for easier debugging and integration with PHP APIs.
 *
 * @file ajaxHandler.js
 * @since 0.7.2
 * @author Robkoo
 * @license TBD
 * @version 0.7.2.1
 * @see /webDev/assets/js/utils/ajaxHandler.js, /webDev/src/API/ApiResponse.php
 * @todo Add support for custom response types (e.g., text, blob), request timeouts, and progress events.
 */

/**
 * A utility class for handling AJAX requests.
 *
 * @class
 * @classdesc Static-only class for sending AJAX requests with validation and unified error handling.
 * @example
 * import ajaxHandler from './utils/ajaxHandler.js';
 * const result = await ajaxHandler.send('/api/endpoint.php', { foo: 'bar' });
 */
export default class ajaxHandler {
    /**
     * Validate the data object.
     *
     * @param {Object} data - Data to be sent in the request body.
     * @returns {{success: boolean, message: string}} Validation result.
     * @example
     * const valid = ajaxHandler.#validateData({foo: 1});
     */
    static #validateData(data){
        /**
         * return structure for the validation
         * @type {{success: boolean, message: string}}
         */
        let returnData = {
            success: true,
            message: ""
        };

        // if the data is null, NaN or anything
        // else js considers ! to account for
        if (!data){
            returnData.success = false;
            returnData.message += "Data is invalid.\n";
        }
        // if the data isn't an object
        if (!(typeof data === 'object')){
            returnData.success = false;
            returnData.message += "Data is not an object.\n";
        }
        // if the data is an array (js considers an array as an object, that's why the need for this check)
        if (Array.isArray(data)){
            returnData.success = false;
            returnData.message += "Data is an array (invalid type).\n";
        }

        // if the length of the provided data is zero
        if (Object.keys(data).length === 0){
            returnData.success = false;
            returnData.message += "Data is empty. Please provide data to send.\n";
        }

        // return the validation structure after validation
        return returnData;
    }

    /**
     * Validate the HTTP method.
     *
     * @param {string} method - HTTP method (e.g., 'POST', 'GET').
     * @returns {{success: boolean, message: string}} Validation result.
     * @example
     * const valid = ajaxHandler.#validateMethod('POST');
     */
    static #validateMethod(method){
        switch (method){
            // if the method is POST or GET (valid methods for now), return success
            case 'POST':
            case 'GET':
                return {
                    success: true,
                    message: `Valid method specified: ${method}`
                };
            // otherwise return failure
            default:
                return {
                    success: false,
                    message: "Invalid method specified."
                };
        }
    }

    /**
     * Validate the headers object.
     *
     * @param {Object} header - Headers to be sent with the request.
     * @returns {{success: boolean, message: string}} Validation result.
     * @example
     * const valid = ajaxHandler.#validateHeader({'Content-Type': 'application/json'});
     */
    static #validateHeader(header){
        /**
         * return structure for the validation
         * @type {{success: boolean, message: string}}
         */
        let returnData = {
            success: true,
            message: ""
        };

        // if the provided header's contents is zero (empty)
        if (Object.keys(header).length === 0){
            returnData.success = false;
            returnData.message += "Empty header provided. Please provide header data.\n";
        }

        // if the header is an array (js considers arrays to be objects)
        if (Array.isArray(header)){
            returnData.success = false;
            returnData.message += "Invalid type. Provided header is an array.\n";
        }

        // if the header is not an object
        if (!(typeof header === 'object')){
            returnData.success = false;
            returnData.message += "Invalid type. Provided header isn't an object.\n";
        }

        // validate header keys (anonymous function for structuring)
        const validateKeys = (header) => {
            /**
             * return structure for the validation
             * @type {{success: boolean, message: string}}
             */
            let returnData = {
                success: true,
                message: ""
            };

            // define all the valid header keys
            const validHeaderKeys = [
                "Accept", "Accept-Encoding", "Accept-Language", "Content-Type", "Cache-Control", "Pragma",
                "Authorization", "X-Requested-With", "X-CSRF-Token", "X-App-Version", "X-Client-ID",
                "If-Modified-Since", "If-None-Match"
            ];

            // define browser blocked keys (if you try to pass these in the headers, browser will NOT let you)
            const browserBlockedKeys = [
                "User-Agent", "Host", "Origin", "Referer", "Content-Length", "Connection", "Date", "Set-Cookie", "Cookie"
            ];
            
            // regex to verify if a header is provided with the following structure: 'X-az-12' where az, 12 can be any alphanumeric character
            const regex = /^X(\-[a-zA-Z0-9]+)+$/;
            
            // store the provided header keys inside a const
            const providedKeys = Object.keys(header);
            
            // define an empty array for found invalid keys
            const invalidKeys = [];
            
            // define an empty array for found blocked keys
            const blockedKeys = [];
            
            // loop through each provided key and validate it
            providedKeys.forEach(key => {
                // if the current key isn't in the validHeaderKeys
                if (!validHeaderKeys.includes(key)){
                    // check if it matches the custom key structure, if not, it's an invalid key
                    if (!regex.test(key)) invalidKeys.push(key);
                }
                // if the provided key is in the blockedKeys, push it to the array
                if (browserBlockedKeys.includes(key)) blockedKeys.push(key);
            });
            
            // if either the arrays' lengths are non-zero, indicate failure and provide an error message
            if (invalidKeys.length !== 0 || blockedKeys.length !== 0){
                returnData.message = `Provided invalid keys: ${invalidKeys.join(', ')}\nProvided blocked keys: ${blockedKeys.join(', ')}`;
                returnData.success = false;
            }
            else { // otherwise indicate success
                returnData.message = "All keys are valid!";
                returnData.success = true;
            }

            // return the validation outcome
            return returnData;
        };

        // store the result in a const
        const keysResult = validateKeys(header);

        // if the validation did not succeed, set the whole function's success indicator to fals and append the provided message 
        if (keysResult.success === false){
            returnData.success = false;
            returnData.message += `${keysResult.message}\n`;
        }

        /**
         * validate header values are strings
         * @param {object} header 
         */
        const validateHeaderData = (header) => {
            /**
             * return structure for the validation
             * @type {{success: boolean, message: string}}
             */
            let returnData = {
                success: true,
                message: ""
            };

            // loop through each entry of the provided header
            Object.entries(header).forEach(([key, value]) => {
                // get a boolean on whether the current key or value are strings
                const isKeyString = Object.prototype.toString.call(key) === "[object String]";
                const isValueString = Object.prototype.toString.call(value) === "[object String]";

                // if the key isn't a string, indicate failure and append a message
                if (!isKeyString){
                    returnData.success = false;
                    returnData.message += `\tKey ${key} isn't a string. (Type: ${typeof(key)})\n`;
                }

                // if the value isn't a string, indicate failure and append a message
                if (!isValueString){
                    returnData.success = false;
                    returnData.message += `\tValue ${value} isn't a string. (Type: ${typeof(value)})\n`;
                }
            });
            
            // return the validation outcome
            return returnData;
        };

        // store the result in a const
        const stringResult = validateHeaderData(header);

        // if the validation failed, indicate the whole function's failure and append the provided message
        if (stringResult.success === false){
            returnData.success = false;
            returnData.message += `Invalid types in provided argument:\n${stringResult.message}`;
        }

        // return the validation outcome 
        return returnData;
    }

    /**
     * Send an AJAX request.
     *
     * @param {string} url - The endpoint URL.
     * @param {object} [data={}] - Data to send (for POST, will be JSON or form-encoded).
     * @param {string} [method='POST'] - HTTP method ('POST' or 'GET').
     * @param {object} [headers] - Headers to send with the request.
     * @returns {Promise<any>} Resolves with the parsed JSON response if successful.
     * @throws {Error} If validation fails, fetch fails, or the response indicates failure.
     *
     * @example
     * const result = await ajaxHandler.send('/api/endpoint.php', { foo: 'bar' });
     * // result.success === true
     */
    static async send(
        url, 
        data = {},
        method = 'POST', // default method (POST)
        headers = { // default headers
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    ){
        // Validate the provided data using private static methods defined above
        const dataResult = ajaxHandler.#validateData(data);
        if (!dataResult.success) throw new Error(dataResult.message);

        const methodResult = ajaxHandler.#validateMethod(method);
        if (!methodResult.success) throw new Error(methodResult.message);

        const headerResult = ajaxHandler.#validateHeader(headers);
        if (!headerResult.success) throw new Error(headerResult.message);

        // make a const object holding the options (method, headers and others)
        const options = {
            method,
            headers,
            credentials: 'same-origin'
        };

        // if the method is POST
        if (method === 'POST'){
            // if the header for content type is form data, do not convert the data to json
            if (headers['Content-Type'] === 'application/x-www-form-urlencoded'){
                options.body = new URLSearchParams(data).toString();
            }
            else { // if it's anything else, convert the data to json
                options.body = JSON.stringify(data);
            }
        }

        // send the data and wait for the response
        const response = await fetch(url, options);

        // if the response wasn't okay, throw an error
        if (!response.ok){
            throw new Error('Failed to send data using fetch().');
        }

        // response was okay, wait for the response data
        // it WILL be always json, as long as the PHP backend always uses the standardised `ApiResponse` class
        const result = await response.json();

        // if the response data transfer failed, throw an error 
        if (!result.success){
            throw new Error(result.message || 'Request failed.');
        }

        // return the result data
        return result;
    }
}