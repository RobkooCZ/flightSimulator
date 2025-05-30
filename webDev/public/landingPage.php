<?php
/**
 * Landing Page
 *
 * Displays the landing page for the flight simulator web application.
 * Shows a welcome message and user information if logged in.
 *
 * @file index.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.8
 * @see templates/header.php, templates/footer.php
 * @todo Add more landing page features
 */

use WebDev\Auth\User;
use WebDev\Bootstrap;

Bootstrap::init();

/**
 * Flag to start the session (or not).
 * @var bool
 */
$startSession = false;
session_start();

$title = 'Landing Page';
$stylesheet = 'landingPage';

/**
 * Flag to show the navbar.
 * @var bool
 */
$showHeader = false;

/**
 * Flag to show the footer.
 * @var bool
 */
$showFooter = false;

// include header
include __DIR__ . '/../templates/header.php';
?>

<div id="bannerImg">
    <h2 id="title">RCFS</h2>
    <h3 id="description">A cockpit flight simulator built in raw C.</h3>
    <!-- dummy button LOL -->
    <button id="wishlist">WISHLIST NOW!</button>
</div>

<div id="socialProof">
    <div class="statsContainer">
        <div class="stat">
            <span class="statNumber">2</span>
            <span class="statLabel">Registered Pilots</span>
        </div>
        <div class="stat">
            <span class="statNumber">2025</span>
            <span class="statLabel">Development Started</span>
        </div>
        <div class="stat">
            <span class="statNumber">100%</span>
            <span class="statLabel">Built in C</span>
        </div>
        <div class="stat">
            <span class="statNumber">Alpha</span>
            <span class="statLabel">Current Phase</span>
        </div>
    </div>
</div>

<div id="featuresBox">
    <div class="feature">
        <h3>Realistic Physics</h3>
        <div class="progressBar">
            <div class="progressFill" data-progress="30"></div>
        </div>
        <span class="progressText">30% Complete</span>
        <p>Built with authentic flight physics and aerodynamics for an immersive cockpit experience.</p>
    </div>
    
    <div class="feature">
        <h3>Raw C Performance</h3>
        <div class="progressBar">
            <div class="progressFill" data-progress="75"></div>
        </div>
        <span class="progressText">75% Complete</span>
        <p>Developed in pure C for maximum performance and responsiveness during flight operations.</p>
    </div>
    
    <div class="feature">
        <h3>Precision Gauges</h3>
        <div class="progressBar">
            <div class="progressFill" data-progress="5"></div>
        </div>
        <span class="progressText">5% Complete</span>
        <div class="gaugeContainer">
            <div class="gauge">
                <span class="gaugeLabel">Altitude</span>
                <div class="gaugeDisplay">4,500 m</div>
            </div>
            <div class="gauge">
                <span class="gaugeLabel">Speed</span>
                <div class="gaugeDisplay">900 km/h</div>
            </div>
        </div>
        <p>Monitor critical flight data with precision gauges and real-time updates for an authentic cockpit experience.</p>
    </div>
</div>

<div id="faqSection">
    <h2>Frequently Asked Questions</h2>
    <div class="faqContainer">
        <div class="faqItem">
            <div class="faqQuestion">When will the game be available?</div>
            <div class="faqAnswer">Currently in very early alpha development. It won't be out for a while.</div>
        </div>
        <div class="faqItem">
            <div class="faqQuestion">What platforms will be supported?</div>
            <div class="faqAnswer">Developed on Linux, Windows is a secondary goal. So far both are supported.</div>
        </div>
        <div class="faqItem">
            <div class="faqQuestion">Will it require a flight controller?</div>
            <div class="faqAnswer">As of right now, flight controller support isn't even planned, so no.</div>
        </div>
        <div class="faqItem">
            <div class="faqQuestion">How realistic are the physics?</div>
            <div class="faqAnswer">Based on real aerodynamic principles with arcade-style adjustments for fun gameplay.</div>
        </div>
    </div>
</div>

<div id="routeButtons">
    <!-- toggle different buttons based on whether the user is logged in or not -->
    <?php if (isset($_SESSION[User::SESSION_ID_KEY])): ?>
        <button onclick="window.location.href='/home'" id="buttonToHome">Home</button>
    <?php else: ?>
        <button onclick="window.location.href='/register'" id="registerButton">Register</button>
        <button onclick="window.location.href='/login'" id="loginButton">Login</button>
    <?php endif; ?>
</div>

<?php
// include footer
include __DIR__ . '/../templates/footer.php';
?>