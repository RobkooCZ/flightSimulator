/**
 * School Admin Page JS logic for dynamic table and action form loading.
 *
 * Handles AJAX requests for table and action selection, input sanitization, and form submission
 * on the school admin page. Integrates with constants and the unified ajaxHandler utility.
 *
 * @file adminSchool.js
 * @version 0.7.2
 * @author Robkoo
 * @license 0.7.2.1
 * @see ../constants/constants.js, ./utils/ajaxHandler.js, /webDev/api/adminSchoolAjax.php
 * @todo Add user feedback for errors, loading indicators, and support for more actions.
 */

import constsPromise from '../constants/constants.js';
import ajaxHandler from './utils/ajaxHandler.js';

/**
 * Sanitize user input to prevent injection of special characters.
 *
 * @param {string} input - The input string to sanitize.
 * @returns {string} The sanitized string.
 *
 * @example
 * const safe = sanitizeInput('<script>alert(1)</script>');
 * // safe === 'scriptalert(1)/script'
 */
function sanitizeInput(input){
    return input.replace(/[<>"'`]/g, "");
}

(async () => {
    // await constants
    const consts = await constsPromise;

    /**
     * Send AJAX request to update the static table display.
     *
     * @param {string} value - The selected table name.
     * @returns {Promise<void>}
     * @throws {Error} If the AJAX request fails.
     *
     * @example
     * await tableSelect('users');
     */
    async function tableSelect(value){
        try {
            const result = await ajaxHandler.send(
                '/api/adminSchoolAjax.php',
                {
                    action: 'getValue',
                    value: value
                },
                'POST',
                {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            );
            document.getElementById(consts.adminSchool.content.staticTable.display).innerHTML = result.data;
        }
        catch (error){
            console.error(error);
        }
    }

    /**
     * Send AJAX request to update the action form display.
     *
     * @param {string} value - The selected action (add, edit, delete).
     * @returns {Promise<void>}
     * @throws {Error} If the AJAX request fails.
     *
     * @example
     * await actionSelect('add');
     */
    async function actionSelect(value){
        try {
            const tableName = document.getElementById(consts.adminSchool.content.staticTable.select).value;
            const result = await ajaxHandler.send(
                '/api/adminSchoolAjax.php',
                {
                    action: 'tableActionChoice',
                    value: value,
                    tableName: tableName
                },
                'POST',
                {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            );
            document.getElementById(consts.adminSchool.content.actionTable.display).innerHTML = result.data;
        }
        catch (error){
            console.error(error);
        }
    }

    // Event listeners for select changes
    document.getElementById(consts.adminSchool.content.staticTable.select).addEventListener("change", function (){
        tableSelect(this.value);
    });

    document.getElementById(consts.adminSchool.content.actionTable.select).addEventListener("change", function (){
        actionSelect(this.value);
    });

    // On window load, get default values and trigger AJAX
    const defaultValue = document.getElementById(consts.adminSchool.content.staticTable.select).value;
    tableSelect(defaultValue);

    const defaultAction = document.getElementById(consts.adminSchool.content.actionTable.select).value;
    actionSelect(defaultAction);

    /**
     * Handle submit button click for the action form.
     *
     * @returns {Promise<void>}
     * @throws {Error} If the AJAX request fails.
     *
     * @example
     * // Triggered by button click
     */
    document.getElementById("submitActionForm").addEventListener("click", async function (){
        // Example: get input values
        const username = sanitizeInput(document.getElementById("username")?.value || "");
        const password = sanitizeInput(document.getElementById("password")?.value || "");
        const role = sanitizeInput(document.getElementById("role")?.value || "");

        const actionSelectElement = document.getElementById(consts.adminSchool.content.actionTable.select);
        const tableName = document.getElementById(consts.adminSchool.content.staticTable.select).value;

        try {
            await ajaxHandler.send(
                "/actionScript",
                {
                    action: actionSelectElement.value,
                    username,
                    password,
                    role,
                    tableName
                },
                'POST',
                {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            );
        }
        catch (error){
            console.error(error);
        }
    });
})();