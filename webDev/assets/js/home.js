/**
 * Home page JavaScript file.
 * 
 * Handles styling issues.
 *
 * @file home.js
 * @since 0.7.8
 * @package HomePage
 * @author Robkoo
 * @license TBD
 * @version 0.7.8
 * @see home.php
 */

/**
 * This function selects the two top `<div>` elements, compares their heights and sets both of their heights to the bigger one.
 *
 * @returns void
 */
function equalizeTopSections(){
    // Get the two .top elements
    /**
     * @type {HTMLElement | false} - The left top div.
     */
    const leftTop = document.querySelector('#left .top');

    /**
     * @type {HTMLElement | false} - The right top div.
     */
    const rightTop = document.querySelector('#right .top');

    // check if we got both elements
    if (!leftTop){
        console.error("Failed to select #left .top element.");
        return;
    }
    if (!rightTop){
        console.error("Failed to select #right .top element.");
        return;
    }

    // reset their heights to auto to get their NATURAL heights
    leftTop.style.height = 'auto';
    rightTop.style.height = 'auto';
    
    // get the heights
    /**
     * @type {number} - The height of the left top div in `px`.
     */
    const leftHeight = leftTop.offsetHeight;

    /**
     * @type {number} - The height of the right top div in `px`.
     */
    const rightHeight = rightTop.offsetHeight;

    // get the final height by comparing the left and right heights
    /**
     * @type {number} - The biggest height out of the two in `px`.
     */
    const finalHeight = Math.max(leftHeight, rightHeight);

    // set both the elements to the height
    leftTop.style.height = finalHeight + 'px';
    rightTop.style.height = finalHeight + 'px';
}

// event listeners for resizing the top sections
window.addEventListener('load', equalizeTopSections);
window.addEventListener('resize', equalizeTopSections);

// changelog changing funcionality
/**
 * @type {Array<number,HTMLElement|false>}
 */
const tabs = document.querySelectorAll("#tab button");

// add event listeners to all of them
[...tabs].forEach((tab) => {
    tab.addEventListener('click', () => {
        // Remove 'active' class from ALL buttons
        tabs.forEach(button => button.classList.remove('active'));
        
        // Add 'active' class to the clicked button
        tab.classList.add('active');
        
        // Hide ALL content divs
        document.getElementById('webLog').style.display = 'none';
        document.getElementById('gameLog').style.display = 'none';
        
        // Show the correct content based on button text
        if (tab.textContent === 'Web'){
            document.getElementById('webLog').style.display = 'block';
        }
        else if (tab.textContent === 'Game'){
            document.getElementById('gameLog').style.display = 'block';
        }
    });
});