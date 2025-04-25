import constsPromise from '../constants/constants.js';
import ajaxHandler from './utils/ajaxHandler.js';

// Helper to sanitize input
function sanitizeInput(input){
    return input.replace(/[<>"'`]/g, "");
}

// Main async IIFE to use awaited consts
(async () => {
    // await constants
    const consts = await constsPromise;

    // AJAX wrapper for table select
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

    // AJAX wrapper for action select
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

    // Submit button for action form
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