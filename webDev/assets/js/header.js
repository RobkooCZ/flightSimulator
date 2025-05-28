/**
 * Handles header link click activity logging via AJAX.
 *
 * Sends a JSON POST request to the backend when a header link is clicked,
 * logging user activity for auditing. Navigates to the link after sending.
 *
 * @file header.js
 * @since 0.7.5
 * @package AJAX
 * @author Robkoo
 * @license TBD
 * @version 0.7.5
 * @see /utils/ajaxHandler.js, /webDev/api/headerAjax.php
 * @todo ---
 */

import ajaxHandler from './utils/ajaxHandler.js';

// get the elements with class links
const links = document.getElementsByClassName("links");

// loop throuch each link and add an event listener to it
// [...links] unravels the HTML object so you can foreach it
[...links].forEach(link => {
    link.addEventListener('click', async e => {
        e.preventDefault();
        const href = link.getAttribute('href');
        try {
            await ajaxHandler.send(
                '/api/headerAjax',
                { linkClicked: true }
                // uses default method (POST) and headers
            );
            window.location.href = href;
        }
        catch (error){
            // todo probably some error logging
            window.location.href = href;
        }
    });
});

// on pfp click, show the dropdown
/**
 * @type {HTMLElement|false} User's profile that functions as a dropdown when clicked.
 */
const profile = document.getElementById("profile");

/**
 * @type {HTMLElement|false} The dropdown content.
 */
const dropdownContent = document.getElementById("dropdownContent");

// For opening the dropdown
profile.addEventListener('click', (e) => {
    e.stopPropagation(); // Prevent closing immediately due to document listener
    dropdownContent.classList.toggle("displayBlock");
});

// Close dropdown when clicking anywhere else on the page
document.addEventListener('click', (e) => {
    // Check if dropdown is open and click wasn't inside the dropdown content
    if (dropdownContent.classList.contains("displayBlock") && // if it is opened
        !dropdownContent.contains(e.target) && // if the user didn't click on the dropdown
        e.target !== profile){ // or on the profile picture
        dropdownContent.classList.remove("displayBlock"); // close it
    }
});