/**
 * School Admin Page JS logic for dynamic table and action form loading.
 *
 * Handles AJAX requests for table and action selection, input sanitization, form submission,
 * and filtering functionality on the school admin page. Integrates with constants and the 
 * unified ajaxHandler utility.
 *
 * @file adminSchool.js
 * @since 0.7.2
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.9
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
 * console.log(safe); // "scriptalert(1)/script"
 */
function sanitizeInput(input){
    return input.replace(/[<>"'`]/g, "");
}

(async () => {
    /**
     * @type {Object} - Application constants loaded from constants.js
     */
    const consts = await constsPromise;

    /* ===== STATIC TABLE SECTION FUNCTIONS ===== */

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
            /**
             * @type {Object} - AJAX response containing table HTML data
             */
            const result = await ajaxHandler.send(
                '/api/adminSchoolAjax',
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
            
            // Update filter columns when table changes
            await updateFilterColumns(value);
        }
        catch (error){
            console.error(error);
        }
    }

    /* ===== FILTER SECTION FUNCTIONS ===== */

    /**
     * Update filter column options based on selected table.
     *
     * @param {string} tableName - The selected table name
     * @returns {Promise<void>}
     * @throws {Error} If the AJAX request fails.
     *
     * @example
     * await updateFilterColumns('users');
     */
    async function updateFilterColumns(tableName){
        try {
            /**
             * @type {Object} - AJAX response containing table column data
             */
            const result = await ajaxHandler.send(
                '/api/adminSchoolAjax',
                {
                    action: 'getTableColumns',
                    tableName: tableName
                },
                'POST',
                {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            );

            /**
             * @type {HTMLSelectElement} - Filter column dropdown element
             */
            const filterColumnSelect = document.getElementById('filterColumn');
            filterColumnSelect.innerHTML = '<option value="" disabled selected>Select column</option>';
            
            result.data.forEach(column => {
                /**
                 * @type {HTMLOptionElement} - Column option element
                 */
                const option = document.createElement('option');
                option.value = column.value;
                option.textContent = column.label;
                filterColumnSelect.appendChild(option);
            });

        }
        catch (error){
            console.error('Error loading table columns:', error);
        }
    }

    /**
     * Apply filter to the static table display.
     *
     * @returns {Promise<void>}
     * @throws {Error} If the filter request fails.
     *
     * @example
     * await applyFilter(); // Applies current filter settings
     */
    async function applyFilter(){
        /**
         * @type {string} - Currently selected table name
         */
        const tableName = document.getElementById(consts.adminSchool.content.staticTable.select).value;
        
        /**
         * @type {string} - Selected filter column
         */
        const filterColumn = document.getElementById('filterColumn').value;
        
        /**
         * @type {string} - Selected filter operator
         */
        const filterOperator = document.getElementById('filterOperator').value;
        
        /**
         * @type {string} - Filter value entered by user
         */
        const filterValue = document.getElementById('filterValue').value;

        if (!filterColumn || !filterValue.trim()){
            alert('Please select a column and enter a value to filter by');
            return;
        }

        try {
            /**
             * @type {Object} - AJAX response containing filtered table data
             */
            const result = await ajaxHandler.send(
                '/api/adminSchoolAjax',
                {
                    action: 'getValue',
                    value: tableName,
                    filterColumn: filterColumn,
                    filterOperator: filterOperator,
                    filterValue: filterValue.trim()
                },
                'POST',
                {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            );

            document.getElementById(consts.adminSchool.content.staticTable.display).innerHTML = result.data;
            
            /**
             * @type {string} - Status message showing current filter
             */
            const statusText = `Filtered by ${filterColumn} ${filterOperator} "${filterValue}"`;
            document.getElementById('filterStatusText').textContent = statusText;
            document.getElementById('filterStatusText').className = 'filter-active';

        }
        catch (error){
            console.error('Error applying filter:', error);
            alert('Failed to apply filter');
        }
    }

    /**
     * Clear the current filter and show all table data.
     *
     * @returns {Promise<void>}
     * @throws {Error} If the table reload fails.
     *
     * @example
     * await clearFilter(); // Removes filter and shows all data
     */
    async function clearFilter(){
        // Clear filter inputs
        document.getElementById('filterColumn').selectedIndex = 0;
        document.getElementById('filterOperator').selectedIndex = 0;
        document.getElementById('filterValue').value = '';

        /**
         * @type {string} - Currently selected table name
         */
        const tableName = document.getElementById(consts.adminSchool.content.staticTable.select).value;
        await tableSelect(tableName);

        // Update filter status
        document.getElementById('filterStatusText').textContent = 'No filter applied';
        document.getElementById('filterStatusText').className = '';
    }

    /* ===== ACTION TABLE SECTION FUNCTIONS ===== */

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
            /**
             * @type {string} - Currently selected table name from the static table dropdown
             */
            const tableName = document.getElementById(consts.adminSchool.content.staticTable.select).value;
            
            /**
             * @type {Object} - AJAX response containing action form HTML data
             */
            const result = await ajaxHandler.send(
                '/api/adminSchoolAjax',
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
        
            // Inject buttons after content loads
            setTimeout(() => {
                injectRowActionButtons();
            }, 100);
        }
        catch (error){
            console.error(error);
        }
    }

    /**
     * Inject edit and delete buttons into action table rows.
     * Only shows buttons for edit and delete actions.
     *
     * @returns {void}
     *
     * @example
     * injectRowActionButtons(); // Adds edit/delete buttons to table rows
     */
    function injectRowActionButtons(){
        /**
         * @type {HTMLSelectElement} - Action dropdown select element
         */
        const actionSelect = document.getElementById(consts.adminSchool.content.actionTable.select);
        
        /**
         * @type {string} - Currently selected action (add, edit, delete)
         */
        const currentAction = actionSelect ? actionSelect.value : '';
        
        // Only show buttons for edit and delete actions
        if (currentAction !== 'edit' && currentAction !== 'delete'){
            return;
        }
        
        /**
         * @type {HTMLTableElement} - Action table containing data rows
         */
        const actionTable = document.querySelector('#actionDisplay table');
        if (!actionTable) return;
        
        /**
         * @type {NodeList} - Collection of table body rows
         */
        const rows = actionTable.querySelectorAll('tbody tr');
        
        rows.forEach((row, index) => {
            // Skip if buttons already exist
            if (row.querySelector('.row-actions')) return;
            
            /**
             * @type {HTMLTableCellElement} - Last cell in the current row
             */
            const lastCell = row.querySelector('td:last-child');
            if (!lastCell) return;
            
            /**
             * @type {HTMLDivElement} - Container for action buttons
             */
            const actionContainer = document.createElement('div');
            actionContainer.className = 'row-actions';
            
            /**
             * @type {HTMLButtonElement} - Edit button for the current row
             */
            const editBtn = document.createElement('button');
            editBtn.className = 'row-action-btn edit-btn';
            editBtn.textContent = 'Edit';
            editBtn.title = 'Edit this record';
            editBtn.dataset.rowIndex = index;
            editBtn.addEventListener('click', (e) => handleRowAction(e, 'edit', row));
            
            /**
             * @type {HTMLButtonElement} - Delete button for the current row
             */
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'row-action-btn delete-btn';
            deleteBtn.textContent = 'Del';
            deleteBtn.title = 'Delete this record';
            deleteBtn.dataset.rowIndex = index;
            deleteBtn.addEventListener('click', (e) => handleRowAction(e, 'delete', row));
            
            actionContainer.appendChild(editBtn);
            actionContainer.appendChild(deleteBtn);
            lastCell.appendChild(actionContainer);
        });
    }

    /**
     * Handle edit/delete button clicks on table rows.
     *
     * @param {Event} event - The click event
     * @param {string} action - The action type ('edit' or 'delete')
     * @param {HTMLElement} row - The table row element
     * @returns {void}
     *
     * @example
     * handleRowAction(event, 'edit', rowElement);
     */
    function handleRowAction(event, action, row){
        event.preventDefault();
        event.stopPropagation();
        
        /**
         * @type {NodeList} - Collection of table cells in the clicked row
         */
        const cells = row.querySelectorAll('td');
        
        /**
         * @type {Object} - Object containing row data extracted from cells
         */
        const rowData = {};
        
        // Extract data from cells
        cells.forEach((cell, index) => {
            /**
             * @type {HTMLElement|false} - Table header corresponding to current cell
             */
            const header = document.querySelector(`#actionDisplay th:nth-child(${index + 1})`);
            if (header){
                /**
                 * @type {string} - Field name derived from header text
                 */
                const fieldName = header.textContent.trim().toLowerCase();
                rowData[fieldName] = cell.textContent.trim();
            }
        });
        
        if (action === 'edit'){
            /**
             * @type {HTMLSelectElement} - Action dropdown select element
             */
            const actionSelect = document.getElementById(consts.adminSchool.content.actionTable.select);
            actionSelect.value = 'edit';
            actionSelect.dispatchEvent(new Event('change'));
            
            // Wait for form to load, then populate
            setTimeout(() => {
                populateFormFields(rowData);
            }, 300);
        }
        else if (action === 'delete'){
            confirmAndDelete(rowData);
        }
    }

    /**
     * Populate form fields with row data.
     *
     * @param {Object} rowData - The row data to populate
     * @returns {void}
     *
     * @example
     * populateFormFields({ id: '1', username: 'john' });
     */
    function populateFormFields(rowData){
        Object.keys(rowData).forEach(fieldName => {
            // Handle the ID field specially since it might be hidden
            if (fieldName === 'id'){
                /**
                 * @type {HTMLInputElement} - Hidden ID input field
                 */
                const idInput = document.getElementById('id');
                if (idInput){
                    idInput.value = rowData[fieldName];
                }
                return;
            }
            
            /**
             * @type {HTMLInputElement} - Form input field corresponding to current field name
             */
            const input = document.getElementById(fieldName);
            if (input && !input.disabled){
                input.value = rowData[fieldName];
            }
        });
    }

    /**
     * Confirm and delete a record.
     *
     * @param {Object} rowData - The row data to delete
     * @returns {void}
     *
     * @example
     * confirmAndDelete({ id: '1', username: 'john' });
     */
    function confirmAndDelete(rowData){
        /**
         * @type {string} - Record identifier for confirmation message
         */
        const recordId = rowData.id || rowData.username || 'this record';
        
        /**
         * @type {string} - Confirmation message shown to user
         */
        const confirmMessage = `Are you sure you want to delete ${recordId}?\nThis action cannot be undone.`;
        
        if (confirm(confirmMessage)){
            /**
             * @type {HTMLSelectElement} - Action dropdown select element
             */
            const actionSelect = document.getElementById(consts.adminSchool.content.actionTable.select);
            actionSelect.value = 'delete';
            actionSelect.dispatchEvent(new Event('change'));
            
            // Wait for form to load, then populate and submit
            setTimeout(() => {
                populateFormFields(rowData);
                document.getElementById("submitActionForm").click();
            }, 300);
        }
    }

    /* ===== TWO TABLES SECTION FUNCTIONS ===== */

    /**
     * Handle loading and displaying two connected tables.
     *
     * @returns {Promise<void>}
     * @throws {Error} If the AJAX request fails.
     *
     * @example
     * await loadTwoTables(); // Loads connected tables based on form selections
     */
    async function loadTwoTables(){
        /**
         * @type {string} - Selected primary table name
         */
        const primaryTable = document.getElementById('primaryTableSelect').value;
        
        /**
         * @type {string} - Selected secondary table name
         */
        const secondaryTable = document.getElementById('secondaryTableSelect').value;
        
        /**
         * @type {string} - Selected join column for table relationship
         */
        const joinColumn = document.getElementById('joinColumnSelect').value;

        if (!primaryTable || !secondaryTable || !joinColumn){
            alert('Please select both tables and a join column');
            return;
        }

        try {
            /**
             * @type {Object} - AJAX response containing connected tables data
             */
            const result = await ajaxHandler.send(
                '/api/adminSchoolAjax',
                {
                    action: 'loadTwoTables',
                    primaryTable: primaryTable,
                    secondaryTable: secondaryTable,
                    joinColumn: joinColumn
                },
                'POST',
                {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            );

            // Update the displays
            document.getElementById('primaryTableDisplay').innerHTML = result.data.primary;
            document.getElementById('secondaryTableDisplay').innerHTML = result.data.secondary;
            document.getElementById('joinedTableDisplay').innerHTML = result.data.joined;
            
            // Update titles
            document.getElementById('primaryTableTitle').textContent = result.data.primaryTitle;
            document.getElementById('secondaryTableTitle').textContent = result.data.secondaryTitle;

        }catch (error){
            console.error('Error loading two tables:', error);
            alert('Failed to load connected tables');
        }
    }

    /**
     * Update join column options based on selected tables.
     *
     * @returns {void}
     *
     * @example
     * updateJoinColumnOptions(); // Updates dropdown based on table selections
     */
    function updateJoinColumnOptions(){
        /**
         * @type {string} - Selected primary table name
         */
        const primaryTable = document.getElementById('primaryTableSelect').value;
        
        /**
         * @type {string} - Selected secondary table name
         */
        const secondaryTable = document.getElementById('secondaryTableSelect').value;
        
        /**
         * @type {HTMLSelectElement} - Join column dropdown select element
         */
        const joinColumnSelect = document.getElementById('joinColumnSelect');

        // Clear existing options
        joinColumnSelect.innerHTML = '<option value="" disabled selected>Select join column</option>';

        // Add relevant options based on table combination
        if (primaryTable === 'users' && secondaryTable === 'userPreferences'){
            joinColumnSelect.innerHTML += '<option value="id-uid">User ID (users.id = userPreferences.uid)</option>';
        }
        else if (primaryTable === 'users' && secondaryTable === 'userAgents'){
            joinColumnSelect.innerHTML += '<option value="id-userId">User ID (users.id = userAgents.userId)</option>';
        }
        else if (primaryTable && secondaryTable){
            // Generic options
            joinColumnSelect.innerHTML += '<option value="id">ID</option>';
            joinColumnSelect.innerHTML += '<option value="userId">User ID</option>';
            joinColumnSelect.innerHTML += '<option value="uid">UID</option>';
        }
    }

    /* ===== EVENT LISTENERS ===== */

    // Static table select change listener
    document.getElementById(consts.adminSchool.content.staticTable.select).addEventListener("change", function(){
        tableSelect(this.value);
        actionSelect(this.value);
    });

    // Filter event listeners
    document.getElementById('applyFilter').addEventListener('click', applyFilter);
    document.getElementById('clearFilter').addEventListener('click', clearFilter);
    
    // Allow Enter key to apply filter
    document.getElementById('filterValue').addEventListener('keypress', function(e){
        if (e.key === 'Enter'){
            applyFilter();
        }
    });

    // Action table select change listener
    document.getElementById(consts.adminSchool.content.actionTable.select).addEventListener("change", function(){
        actionSelect(this.value);
    });

    // Two tables event listeners
    document.getElementById('loadTwoTablesBtn').addEventListener('click', loadTwoTables);
    document.getElementById('primaryTableSelect').addEventListener('change', updateJoinColumnOptions);
    document.getElementById('secondaryTableSelect').addEventListener('change', updateJoinColumnOptions);

    // Submit action form button listener
    document.getElementById("submitActionForm").addEventListener("click", async function(){
        /**
         * @type {string} - Sanitized ID value from form input
         */
        const id = sanitizeInput(document.getElementById("id")?.value || "");
        
        /**
         * @type {string} - Sanitized username value from form input
         */
        const username = sanitizeInput(document.getElementById("username")?.value || "");
        
        /**
         * @type {string} - Sanitized bio value from form input
         */
        const bio = sanitizeInput(document.getElementById("bio")?.value || "");
        
        /**
         * @type {string} - Sanitized IP address value from form input
         */
        const ipAddress = sanitizeInput(document.getElementById("ipAddress")?.value || "");
        
        /**
         * @type {string} - Sanitized password value from form input
         */
        const password = sanitizeInput(document.getElementById("password")?.value || "");
        
        /**
         * @type {string} - Sanitized role value from form input
         */
        const role = sanitizeInput(document.getElementById("role")?.value || "");

        /**
         * @type {HTMLSelectElement} - Action dropdown select element
         */
        const actionSelectElement = document.getElementById(consts.adminSchool.content.actionTable.select);
        
        /**
         * @type {string} - Currently selected table name
         */
        const tableName = document.getElementById(consts.adminSchool.content.staticTable.select).value;

        try {
            /**
             * @type {Object} - AJAX response from action script
             */
            const result = await ajaxHandler.send(
                "/actionScript",
                {
                    action: actionSelectElement.value,
                    id,
                    username,
                    bio,
                    ipAddress,
                    password,
                    role,
                    tableName
                },
                'POST',
                {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            );
            
            /**
             * @type {Object} - Parsed JSON response from server
             */
            let response;
            try {
                response = JSON.parse(result.data);
            }
            catch (e){
                console.error('Failed to parse response:', result.data);
                alert('Action completed, but response format was unexpected');
                response = { success: true, message: 'Action completed' };
            }
            
            // Show success/error message
            if (response.success){
                alert(response.message || 'Action completed successfully');
                
                // Clear form fields after successful action
                document.querySelectorAll('#actionDisplay input').forEach(input => {
                    if (!input.disabled){
                        input.value = '';
                    }
                });
                
                /**
                 * @type {string} - Current table name for refresh
                 */
                const currentTable = document.getElementById(consts.adminSchool.content.staticTable.select).value;
                await tableSelect(currentTable);
                
                /**
                 * @type {string} - Current action for refresh
                 */
                const currentAction = actionSelectElement.value;
                await actionSelect(currentAction);
            }
            else {
                alert('Error: ' + (response.message || 'Action failed'));
            }
            
        }
        catch (error){
            console.error('Action failed:', error);
            alert('Error: Failed to perform action. Please try again.');
        }
    });

    /* ===== INITIALIZATION ===== */

    /**
     * @type {string} - Default table value for initial load
     */
    const defaultValue = document.getElementById(consts.adminSchool.content.staticTable.select).value;
    tableSelect(defaultValue);

    /**
     * @type {string} - Default action value for initial load
     */
    const defaultAction = document.getElementById(consts.adminSchool.content.actionTable.select).value;
    actionSelect(defaultAction);
})();