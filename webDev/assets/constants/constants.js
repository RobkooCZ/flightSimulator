/**
 * Loads and provides access to application constants from a JSON file.
 *
 * This module asynchronously loads constants from a JSON file and exposes
 * them via static methods and a top-level `consts` promise for convenient use
 * throughout the frontend. Designed for use with dynamic configuration and
 * integration with backend PHP constants.
 *
 * @file constants.js
 * @since 0.7.2
 * @package Constants
 * @author Robkoo
 * @license TBD
 * @version 0.7.2.1
 * @see ./constants.json, ../js/utils/ajaxHandler.js, /webDev/assets/constants/constants.php
 * @todo Add caching strategies, support for environment-specific constants, and validation.
 */

/**
 * Utility class for loading and accessing constants from a JSON file.
 *
 * @class
 * @classdesc Loads constants asynchronously and provides static accessors for specific sections.
 * @example
 * const adminSchool = await ConstantsLoader.getAdminSchool();
 * const apiAjax = await ConstantsLoader.getApiAjax();
 */
class ConstantsLoader {
    static CONSTANTS_FILE_PATH = '/assets/constants/constants.json';
    static data = null;

    /**
     * Loads the constants JSON file if not already loaded.
     * @returns {Promise<void>}
     * @throws {Error} If the configuration file cannot be loaded or parsed.
     *
     * @example
     * await ConstantsLoader.loadJson();
     */
    static async loadJson(){
        if (this.data === null){
            const response = await fetch(this.CONSTANTS_FILE_PATH);
            const rawData = await response.json();
            if (!rawData){
                throw new Error('Error decoding configuration file.');
            }
            this.data = rawData;
        }
    }

    /**
     * Retrieves a value from the constants using a dot-separated key.
     * @param {string} key - Dot-separated key (e.g., "adminSchool.content.name").
     * @returns {Promise<any>} The value at the specified key.
     * @throws {Error} If the key is not found.
     *
     * @example
     * const value = await ConstantsLoader.get('adminSchool.content.name');
     */
    static async get(key){
        await this.loadJson();
        const keys = key.split('.');
        let value = this.data;
        for (const keyPart of keys){
            if (!(keyPart in value)){
                throw new Error(`Config key not found: ${key}`);
            }
            value = value[keyPart];
        }
        return value;
    }

    /**
     * Retrieves the adminSchool section of the constants.
     * @returns {Promise<Object>} The adminSchool constants object.
     * @example
     * const adminSchool = await ConstantsLoader.getAdminSchool();
     */
    static async getAdminSchool(){
        await this.loadJson();
        return this.data.adminSchool;
    }

    /**
     * Retrieves the apiAjax section of the constants.
     * @returns {Promise<Object>} The apiAjax constants object.
     * @example
     * const apiAjax = await ConstantsLoader.getApiAjax();
     */
    static async getApiAjax(){
        await this.loadJson();
        return this.data.api.ajaxApi;
    }
}

/**
 * Asynchronously loads and exports the main constants object.
 *
 * @type {Promise<{adminSchool: Object, apiAjax: Object}>}
 * @example
 * const consts = await import('./constants.js').then(m => m.consts);
 * const adminSchool = (await consts).adminSchool;
 */
const consts = (async () => {
    const adminSchool = await ConstantsLoader.getAdminSchool();
    const apiAjax = await ConstantsLoader.getApiAjax();
    return {
        adminSchool,
        apiAjax
    };
})();

// export constants
export { consts };
export default consts;