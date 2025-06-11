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
 * @version 0.7.10
 * @see /utils/ajaxHandler.js, /webDev/api/headerAjax.php
 * @todo ---
 */

import ajaxHandler from './utils/ajaxHandler.js';

// get the elements with class links
const links = document.getElementsByClassName("links");

// loop throuch each link and add an event listener to it
// [...links] unravels the HTML object so you can foreach it
[...links].forEach(link => {
    // Skip external links or links that should open in new tabs
    if (link.getAttribute('target') === '_blank' || 
        (link.getAttribute('href') && link.getAttribute('href').indexOf('http') === 0)){
        return; // Skip this iteration - don't add event listener
    }

    link.addEventListener('click', async e => {
        e.preventDefault();
        const href = link.getAttribute('href');
        
        // validation to ensure href is not null or empty
        if (!href || href === '#' || href === 'null'){
            console.warn('Invalid href detected:', href);
            return;
        }
        
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

/**
 * @type {boolean} True if the element exists, false if it doesn't
 */
const headerExists = !!document.querySelector("header");

// if the header exists, add the event listeners
if (headerExists === true){
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
        e.preventDefault(); // prevent default link behaviour
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
}