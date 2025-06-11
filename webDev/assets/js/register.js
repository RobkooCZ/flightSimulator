/**
 * Register JS functionality.
 *
 * @file register.js
 * @since 0.7.10
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.10
 * @see register.php
 */

/**
 * Style elements in register modal based on the provided `password` variable strength.
 * 
 * @param {string} password The password to check the strength of
 * @returns {void}
 */
function checkPasswordStrength(password){
    const strengthInfo = document.getElementById('passwordStrengthInfo');
    const hasUpperCase = /[A-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    if (password.length < 8){
        strengthInfo.textContent = 'Password is too weak (minimum 8 characters).';
        strengthInfo.style.color = 'red';
    }
    else if (!hasUpperCase || !hasNumber || !hasSpecialChar){
        strengthInfo.textContent = 'Password must include at least one uppercase letter, one number, and one special character.';
        strengthInfo.style.color = 'orange';
    }
    else {
        strengthInfo.textContent = 'Password strength: Strong.';
        strengthInfo.style.color = 'green';
    }
}

/**
 * Listen to DOMContentLoaded event
 *
 * @type {HTMLElement} - the target of the event
 * @listens document:DOMContentLoaded - When the content loads.
 */
document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.querySelector('input[name="password"]');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', (e) => {
            checkPasswordStrength(e.target.value);
        });
    }
});