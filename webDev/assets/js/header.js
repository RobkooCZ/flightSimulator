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