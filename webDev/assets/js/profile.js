/**
 * Profile Page javascript file. Contains logic for the operation of the profile page.
 *
 * Handles modal logic, AJAX file upload, and UI interactivity for the profile page.
 * Handles navbar and showing game statistics.
 * Handles changing **some** user info.
 *
 * @file profile.js
 * @since 0.7.7
 * @package ProfilePage
 * @author Robkoo
 * @license TBD
 * @version 0.7.10
 * @see profile.php
 */

// ajax handler to be able to send ajax responses to the php backend
import ajaxHandler from './utils/ajaxHandler.js';

// import validation functions from the validation file
import { validateUserName, validateBio } from './utils/inputValidator.js';


// only execute the script after DOM is loaded
/**
 * Listen to DOMContentLoaded event
 *
 * @type {HTMLElement} - the target of the event
 * @listens document:DOMContentLoaded - When the content loads.
 */
document.addEventListener("DOMContentLoaded", () => {
    /*
     * DOM References
     */

    /**
     * @type {HTMLElement|false} pfp
     */
    const pfp = document.getElementById("pfp");

    /**
     * @type {HTMLElement|false} modal
     */
    const modal = document.getElementById("modal");

    /**
     * @type {HTMLElement|false} closeImg
     */
    const closeImg = document.getElementById("close");

    /**
     * @type {HTMLElement|false} hoverImg
     */
    const hoverImg = document.getElementById("hoverImg");

    /**
     * @type {HTMLElement|false} fileNav
     */
    const fileNav = document.getElementById("fileNav");

    /**
     * @type {HTMLElement|false} linkNav
     */
    const linkNav = document.getElementById("linkNav");

    /**
     * @type {HTMLElement|false} fileInputDiv
     */
    const fileInputDiv = document.getElementById("fileInputDiv");

    /**
     * @type {HTMLElement|false} linkInputDiv
     */
    const linkInputDiv = document.getElementById("linkInputDiv");

    /**
     * @type {HTMLElement|false} inputButton
     */
    const inputButton = document.getElementById("inputButton");

    /**
     * @type {HTMLElement|false} messageElement
     */
    const messageElement = document.getElementById("message");

    /*
     * Game statistics HTMLElements 
     */

    /**
     * @type {array<int,HTMLElement|false>} gsNavs
     */
    const gsNavs = Array.from(document.querySelectorAll('#iconNavBar a'));

    /**
     * @type {array<int,HTMLElement|false>} gsDivs
     */
    const gsDivs = Array.from(document.querySelectorAll('#statisticsContent div'));
    
    /**
     * @type {int} lastActive
     */
    let lastActive = 0; // the first one is selected initially

    /*
     * Modal Logic
     */

    /**
     * Removes the "hidden" class from the modal, thus "opening" it.
     */
    function openModal(){
        modal.classList.remove("hidden");
    }

    /**
     * Adds the "hidden" class to the modal, thus "closing" it.
     */
    function closeModal(){
        modal.classList.add("hidden");
    }

    // add event listeners
    if (hoverImg) hoverImg.addEventListener("click", openModal);
    if (closeImg) closeImg.addEventListener("click", closeModal);

    // if the user presses the escape key, close the modal
    /**
     * Close the modal after `esc` keypress.
     * @type {Window} - the window
     * @listens window:keydown - Any keydown while the user is on page
     */
    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal();
    });

    /*
     * Navbar Switching Logic
     */

    /**
     * Navbar switching logic after pressing the file button on the nav.
     * @type {HTMLElement} - the file button on the nav
     * @listens document:click - waits for user click
     */
    fileNav.addEventListener('click', () => {
        fileNav.classList.add("navSelected");
        linkNav.classList.remove("navSelected");
        fileInputDiv.classList.remove("inputDivHide");
        linkInputDiv.classList.add("inputDivHide");
    });

    /**
     * Navbar switching logic after pressing the link button on the nav.
     * @type {HTMLElement} - the link button on the nav
     * @listens document:click - waits for user click
     */
    linkNav.addEventListener('click', () => {
        fileNav.classList.remove("navSelected");
        linkNav.classList.add("navSelected");
        fileInputDiv.classList.add("inputDivHide");
        linkInputDiv.classList.remove("inputDivHide");
    });

    /**
     * Async due to how the ajaxHandler works.
     * 
     * On submit button click attempt to send the image or link to the PHP backend (endpoint: /api/profile?type=(link|file))
     * 
     * @type {HTMLElement} - the input button
     * @listens document:click - On button click
     */
    inputButton.addEventListener('click', async () => { // the file option was selected
        if (fileNav.classList.contains("navSelected")){
            // get the file input
            const fileInput = document.getElementById("fileInput");

            // if there was no file selected
            if (!fileInput || !fileInput.files.length){
                console.error("No file selected");
                return;
            }

            /**
             * @type {array} file
             */
            const file = fileInput.files[0];

            /**
             * @type {array<int,string>} validTypes
             */
            const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];

            /**
             * @type {int} validTypes
             */
            const maxSize = 5 * 1024 * 1024;

            // if the uploaded image isn't in the validTypes
            if (!validTypes.includes(file.type)){
                console.error('Invalid file type. Only JPEG, PNG, and WebP are allowed');
                return;
            }

            // if the file is too large
            if (file.size > maxSize){
                console.error('File is too large. Max size is 5MiB');
                return;
            }

            /**
             * @type {FormData} formData
             */
            const formData = new FormData();

            // append the image to the formdata
            formData.append('img', file);

            try { // attempt to send it to the endpoint
                const result = await ajaxHandler.send(
                    '/api/profile?action=nav&type=file', 
                    {
                        body: formData
                    }, 
                    'POST'
                );

                if (result.success === true){ // if it succeeded
                    // show the success message to the user
                    messageElement.innerHTML = result.message;

                    // change the pfp
                    pfp.src = result.data;
                }
            }
            catch (error){ // if it failed
                // show the generic error message to the user
                messageElement.innerHTML = error.message;

                // log the actual error message (for now, logging coming soonTM)
                console.error("Error response from `ProfileAjax.php`: ", error.backendMessage);
            }

        }
        else if (linkNav.classList.contains("navSelected")){ // the link option was selected
            // get the dom element
            const linkInput = document.getElementById("linkInput");

            /**
             * @type {FormData} formData
             */
            const formData = new FormData();

            // append the link to the formdata
            formData.append('link', linkInput.value);

            try { // attempt to send it to the endpoint
                const result = await ajaxHandler.send(
                    '/api/profile?action=nav&type=link', 
                    {
                        body: formData
                    }, 
                    'POST'
                );
                if (result.success === true){ // if it succeeded
                    // show the success message to the user
                    messageElement.innerHTML = result.message;

                    // change the pfp
                    pfp.src = result.data;
                }
            }
            catch (error){ // if it failed
                // show the generic error message to the user
                messageElement.innerHTML = error.message;
                
                // log the actual error message (for now, logging coming soonTM)
                console.error("Error response from `ProfileAjax.php`: ", error.backendMessage);
            }
        }
    });

    /**
     * Make the nav element with index `i` inside `gsNavs` selected.
     * Show the div with index `i` inside `gsDivs`.
     * 
     * @param {int} i Which game statistics div to make visible.
     * @returns {void}
     */
    function changeChosenGs(i){
        gsDivs[i].classList.add("visible");
        gsDivs[lastActive].classList.remove("visible");

        gsNavs[i].classList.add("selected");
        gsNavs[lastActive].classList.remove("selected");

        lastActive = i;
    }

    /*
     * Game statistics navbar event listener
     */
    gsNavs.forEach((nav, i) => {
        /**
         * Add an event listener to all the nav elements.
         * 
         * @type {HTMLElement}
         * @listens document:click
         */
        nav.addEventListener('click', () => changeChosenGs(i));
    });

    // Update profile user input
    const submitChangeBtn = document.getElementById("submitChange");

    // Update profile message
    const changeMessage = document.getElementById("changeMessage");

    submitChangeBtn.addEventListener('click', () => {
        // get the contents of username and bio
        const usernameInput = document.getElementById("usernameChange");
        const bioInput = document.getElementById("bioChange");

        // check if atleast one is inputted
        if (!usernameInput.value.trim() && !bioInput.value.trim()){
            // both empty, return to prevent execution
            return;
        }

        /**
         * Data to be sent to the backend
         * @type {Object} - data
         * ### Structure example:
         * @example
         * [
         *  {
         *       "type": username,
         *       "data": data
         *  },
         *  {
         *      "type": bio,
         *      "data": data
         *  }
         * ]
         */
        let data = [];

        /**
         * Count of values inputted
         * 
         * @type {int} - dataCount
         */
        let dataCount = 0;

        // username inputed
        if (usernameInput.value.trim() !== ""){
            // validate the provided username
            const unameValidationResult = validateUserName(usernameInput.value);

            // validation failed
            if (unameValidationResult.success === false){
                // show the user message to the user
                changeMessage.innerHTML = unameValidationResult.message;

                // log the backend msg
                console.error(unameValidationResult.backendMessage);

                // exit
                return;
            }
            else { // validation succeeded
                // increment count
                dataCount++;

                // push the data to the array
                data.push({
                    'type': "username",
                    'data': usernameInput.value.trim()
                });
            };
        };

        // bio inputed
        if (bioInput.value.trim() !== ""){
            // validate the provided bio
            const bioValidationResult = validateBio(bioInput.value);

            // validation failed
            if (bioValidationResult.success === false){
                // show the user message to the user
                changeMessage.innerHTML = bioValidationResult.message;

                // log the backend msg
                console.error(bioValidationResult.backendMessage);

                // exit
                return;
            }
            else { // validation succeeded
                // increment count
                dataCount++;

                // push the data to the array
                data.push({
                    'type': "bio",
                    'data': bioInput.value.trim()
                });
            };
        };
        
        // send data to the backend
        (async () => {
            const changeData = new FormData();

            changeData.append("changeData", JSON.stringify(data));
            changeData.append("dataCount", dataCount);

            try { // attempt to send the data to the endpoint
                const result = await ajaxHandler.send(
                    "/api/profile?action=update",
                    {
                        body: changeData
                    },
                    'POST'
                )

                // if everything is all right
                if (result.success === true){
                    // show the message to the user
                    changeMessage.innerHTML = result.message;

                    // reload after a second
                    setTimeout(() => window.location.reload(), 1000);
                }
            }
            catch (error){ // something failed
                // show the user error message to the user
                changeMessage.innerHTML = error.message;
                // error log the message
                console.error("Error response from `profileAjax.php`: ", error.backendMessage);
            }
        })();
    });

    // settings
    /**
     * @type {HTMLElement|false} - The select containing theme options.
     */
    const themeSelect = document.getElementById("themeSelect");

    themeSelect.addEventListener('change', async () => {
        /**
         * @type {string} Selected value. Empty if `themeSelect` is false.
         */
        const selectedValue = themeSelect ? themeSelect.value : "";

        if (selectedValue === "light"){
            document.body.className = ""; // Removes all classes from the body
        }
        else {
            document.body.className = selectedValue; // Sets the class to the selected theme
        }

        /**
         * @type {FormData} The data to send
         */
        const data = new FormData();
        data.append('theme', selectedValue);

        // send the user chosen theme to the backend
        try {
            // no need for result
            await ajaxHandler.send(
                "/api/profile?action=themeChoice&themeAction=save",
                {
                    body: data
                }
                // Method (POST) and headers default
            );
        }
        catch (error){
            console.error("Error response from `profileAjax.php` (Theme sending): ", error.backendMessage);
        }
    });

    // load the user prefered theme from the db to correctly set the select
    (async () => {
        try {
            const result = await ajaxHandler.send(
                "/api/profile?action=themeChoice&themeAction=load",
                {},
                'GET'
            );
            
            // set the value and default to dark-theme if the HTMLElement wasn't selected properly
            if (themeSelect){
                themeSelect.value = result.data || 'dark-theme';
                
                // Also apply the theme to the body immediately
                if (result.data === "light"){
                    document.body.className = "";
                }
                else {
                    document.body.className = result.data || 'dark-theme';
                }
            }
        }
        catch (error){
            console.error("Error response from `profileAjax.php` (Theme loading):", {
                fullError: error,
                message: error.message || 'Unknown error',
                backendMessage: error.backendMessage || 'No backend message available',
                errorString: error.toString()
            });
            
            // Set default theme on error
            if (themeSelect) {
                themeSelect.value = 'dark-theme';
                document.body.className = 'dark-theme';
            }
        }
    })();
});