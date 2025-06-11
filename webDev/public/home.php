<?php
/**
 * Home Page
 *
 * Displays the home interface for logged in users.
 *
 * @file home.php
 * @since 0.7.8
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.10
 * @see home.js
 * @todo Add features marked as `TBD`
 */
declare(strict_types=1);

use WebDev\Auth\AccessControl;
use WebDev\Auth\User;
use WebDev\Bootstrap;
Bootstrap::init();

/**
 * Flag to start the session (or not).
 * @var bool
 */
$startSession = false;
session_start();

// prevent not logged in users from accessing
AccessControl::requireAuth();

/**
 * Title of the website.
 * @var string
 */
$title = 'Home';

/**
 * Stylesheet name (without extension) for this page.
 * @var string
 */
$stylesheet = 'home';

/**
 * Flag to show the navbar.
 * @var bool
 */
$showHeader = true;

/**
 * Flag to show the footer.
 * @var bool
 */
$showFooter = true;
// include header
include __DIR__ . '/../templates/header.php';
?>

<!-- right below header, site-wide -->
<div id="systemMessage"></div>

<!-- above the two column content -->
<div id="welcomeMessage">
    <h1>Welcome back, <?= $_SESSION['username'] ?></h1>
</div>

<div id="twoColumnContent">
    <!-- top same height -->
    <div id="left">
        <div class="top">
            <div id="systemStatus">
                <h2>Website Information</h2>
                <table>
                    <tr>
                        <th>Name</th>
                        <th class="statusCell">Status</th>
                        <th>Note</th>
                    </tr>
                    <tr>
                        <td>Server</td>
                        <td class="statusCell">
                            <span class="statusDot online"></span>
                            Online
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Database</td>
                        <td class="statusCell">
                            <span class="statusDot online"></span>
                            Connected
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>API Bridge</td>
                        <td class="statusCell">
                            <span class="statusDot offline"></span>
                            Offline
                        </td>
                        <td>Not implemented yet</td>
                    </tr>
                    <tr>
                        <td>Maintenance</td>
                        <td class="statusCell">
                            <span class="statusDot online"></span>
                            None
                        </td>
                        <td></td>
                    </tr>
                </table>
                <div id="userStats">
                    <span>Users: <?= User::totalUsers() ?> total | <?= User::loggedInUsers() ?> active</span>
                </div>
            </div>
        </div>
        <div id="leaderboard">
            <div class="ranking">
                <h2>Most Experienced Pilots</h2>
                <ol>
                    <li><?= $_SESSION[User::SESSION_USERNAME_KEY]?> - 127.5 hrs</li>
                    <li>AceFlyer - 89.2 hrs</li>
                    <li>SkyKing - 76.8 hrs</li>
                </ol>
            </div>
            <div class="ranking">
                <h2>Most Kills</h2>
                <ol>
                    <li><?= $_SESSION[User::SESSION_USERNAME_KEY]?> - 12 kills</li>
                    <li>SkyKing - 4 kills</li>
                    <li>AceFlyer - 1 kill</li>
                </ol>
            </div>
            <div class="ranking">
                <h2>Highest Speed Reached</h2>
                <ol>
                    <li><?= $_SESSION[User::SESSION_USERNAME_KEY]?> - 2122 km/h</li>
                    <li>SkyKing - 1984 km/h</li>
                    <li>AceFlyer - 1542 km/h</li>
                </ol>
            </div>
        </div>
    </div>
    <div id="right">
        <div class="top">
            <div id="changelog">
                <h2>Changelog:</h2>
                <div id="tab">
                    <button class="active">Web</button>
                    <button>Game</button>
                </div>
                <div id="tabContent">
                    <div id="webLog" class="visible">
                        <h2>Alpha v0.7.10 - 11.06.2025</h2>
                        <h3>Added</h3>
                        <ul>
                            <li>Total registered users and logged in users showing up on home page instead of hard-coded data</li>
                            <li>A dynamic box with info about password strength to the register modal</li>
                            <li>Two dummy features to the homepage marked as "TBD"</li>
                            <li>A template error page, where you define for each HTTP error code some info to put on the page</li>
                            <li>Pages for <code>403</code>, <code>404</code> and <code>500</code> HTTP status codes</li>
                            <li>Changed <code>.htaccess</code> to make it more secure to prevent unauthorized access to <code>.php.bak</code>, <code>.env</code> and <code>.log</code> files</li>
                            <li>New class <code>AccessControl</code>, which has static methods to authorize access</li>
                        </ul>

                        <h3>Changed</h3>
                        <ul>
                            <li>Instead of checks inside specific pages, such as admin or profile, use the new methods of the new class <code>AccessControl</code></li>
                        </ul>

                        <h3>Fixed</h3>
                        <ul>
                            <li>Auto Increment ID in database not updating when deleting rows on the school admin page</li>
                            <li>User chosen theme not saving on the hosted website. This was caused due to hardcoded database name, which didn't match the one on the hosting</li>
                            <li>Backend not fetching user theme properly when loading the profile page to put the correct option into the select dropdown</li>
                            <li>A bug where JS would attempt to add event listeners to non-existent elements in pages without the header (login, register)</li>
                        </ul>

                        <hr>
                        
                        <h2>Alpha v0.7.9 - 30.05.2025</h2>
                        <h3>Added</h3>
                        <ul>
                            <li>Comprehensive filtering to the admin school page</li>
                            <li>Display of two joined tables</li>
                            <li>very simply styled, quickly put together editing and deleted of selected rows from a selected table (will be polished in upcoming updates)</li>
                            <li>styling for all the new features</li>
                        </ul>

                        <hr>

                        <h2>Alpha v0.7.8 - 30.05.2025</h2>
                        <h3>Added</h3>
                        <ul>
                            <li>a logo (finally...)</li>
                            <li>a simple check on the profile page to prevent access when not logged in</li>
                            <li>a home page which only logged in users can access
                                <ul>
                                    <li>as of right now doesn't contain much</li>
                                    <li>only templates/prototypes for when the game will actually be playable and the home page would gain meaning</li>
                                    <li>info about the server (again, dummy for now)</li>
                                </ul>
                            </li>
                            <li>box shadow to profile divs</li>
                            <li>proper, standardized styling for the home page</li>
                            <li>added general styling to the table in the admin page</li>
                            <li>a proper landing page
                                <ul>
                                    <li>hero section</li>
                                    <li>features section</li>
                                    <li>FAQ</li>
                                    <li>buttons for navigation</li>
                                </ul>
                            </li>
                        </ul>

                        <h3>Changed</h3>
                        <ul>
                            <li>Improved the footer to show more info, such as game and website versions, links to other stuff and miscellaneous info</li>
                            <li>Improved <code>theme.css</code>
                                <ul>
                                    <li>Better grouped vars</li>
                                    <li>Some new vars</li>
                                    <li>Different, better colors
                                        <ul>
                                            <li>themes were modified, the dark theme was changed the most for a more darker-blue style</li>
                                        </ul>
                                    </li>
                                    <li>Better general styles</li>
                                </ul>
                            </li>
                            <li>Improved styling across all public pages</li>
                            <li>Tweaked the HTML structure to compliment the new styles</li>
                        </ul>

                        <h3>Fixed</h3>
                        <ul>
                            <li>Fixed an issue where the <code>antiquewhite</code> color would show in the profile picture instead of the div background color</li>
                            <li>Fixed issues with styling in the CGT theme</li>
                            <li>Fixed a bug where <code>AdminSchoolAjax.php</code> couldn't find the constants file.</li>
                        </ul>
                    </div>
                    <div id="gameLog">
                        <h2>Alpha v0.3.3 - 06.03.2025</h2>
                        <h3>Added</h3>
                        <ul>
                            <li>Fuel capacity, fuel burn, mass lowering when fuel burns</li>
                            <li>Visual fuel display</li>
                            <li>A made up "coefficient" to make the physics more arcade-ish in some regards (as of right now, only drag)</li>
                            <li>Engine stops working (0N thrust) if it's out of fuel</li>
                            <li>New aircraft to choose - JA37C "Jaktviggen", added because I'm testing supersonic planes and physics</li>
                        </ul>

                        <h3>Changed</h3>
                        <ul>
                            <li>Improved the debug by adding logging messages to everything, every function is tested if the passed vars or pointers are NaN or NULL, respectively</li>
                            <li>Logging messages have the [] part colored accordingly:
                                <ul>
                                    <li>Red: ERROR</li>
                                    <li>Yellow: WARNING</li>
                                    <li>Green: DEBUG</li>
                                    <li>Blue: INFO</li>
                                </ul>
                            </li>
                            <li>Some constants now vary based on whether the plane is subsonic or supersonic</li>
                        </ul>

                        <hr>

                        <h2>Alpha v0.3.2 - 02.03.2025</h2>
                        <h3>Added</h3>
                        <ul>
                            <li>Logger.c (/.h) for easier debugging and logging of values</li>
                        </ul>

                        <h3>Changed</h3>
                        <ul>
                            <li>Now using RK4 (Runge-Kutta 4th Order) for updating the aircraft's physics, instead of Euler's integration. Some of the improvements of RK4 over Euler's integration include:
                                <ul>
                                    <li>Higher precision if FPS goes down,</li>
                                    <li>Higher precision during manouvers (such as changes in pitch, yaw, etc.),</li>
                                    <li>Higher precision if the simulation exhibits non-linear behaviour.</li>
                                </ul>
                            </li>
                            <li>Optimized the physics.c file in ways like:
                                <ul>
                                    <li>Made a global structure "globalPhysicsData" which holds all physics data that was calculated multiple times in code.</li>
                                    <li>Function to fill this structure with values to avoid unnecessary calculations in functions and sub-functions.</li>
                                    <li>This struct is also used in printing out to the screen, as it has all the data it needs.</li>
                                    <li>Made constants for the whole physics.c file for things like:
                                        <ul>
                                            <li>Air density at sea level,</li>
                                            <li>Temperature at sea level,</li>
                                            <li>Lapse rate,</li>
                                            <li>etc.</li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                        <h3>Fixed</h3>
                        <ul>
                            <li>Fixed some errors in the formulas I've been using (Lift coefficient, flight path angle)</li>
                            <li>Fixed not being able to run executable from just the build folder (it wouldn't find data/aircraftSimulator.txt)</li>
                        </ul>

                        <hr>

                        <h2>Alpha v0.3.1 - 28.02.2025</h2>
                        <h3>Added</h3>
                        <ul>
                            <li>Gauge for speed
                                <ul>
                                    <li>Heavily inspired from the Viggen speedometer</li>
                                    <li>Has the current mach number right above the needle center</li>
                                    <li>Functions for generating a gauge are expandable, you can modify the number of ticks, the biggest speed on the gauge</li>
                                </ul>
                            </li>
                            <li>Changes to the layout in the application</li>
                            <li>You can change between the text and visual print out in the simulation.</li>
                            <li>Extensively commented header files and the code.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div id="pilotDashboard">
            <h2>PILOT DASHBOARD</h2>
            <div class="statusGrid">
                <div class="statusItem">
                    <span class="statusIndicator offline"></span>
                    <span class="statusText">Flight Simulator Status: Offline</span>
                </div>
                <div class="statusItem">
                    <span class="statusIcon">👥</span>
                    <span class="statusText"><span id="activePilots">12</span> Active Pilots</span>
                </div>
                <div class="statusItem">
                    <span class="statusIcon">🎖️</span>
                    <span class="statusText">Rank: <span id="pilotRank">Lieutenant</span></span>
                </div>
                <div class="statusItem">
                    <span class="statusIcon">🎯</span>
                    <span class="statusText">Next: <span id="nextMission">Combat Training</span></span>
                </div>
                <div class="statusItem">
                    <span class="statusIcon">⏱️</span>
                    <span class="statusText">Last Flight: <span id="lastFlight">2 hours ago</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="flightOperations">
    <h2>FLIGHT OPERATIONS</h2>
    <div class="operationsGrid">
        <div class="opsRow">
            <span class="opsLabel">Weather:</span>
            <span class="opsValue" id="weatherStatus">Clear Skies</span>
        </div>
        <div class="opsRow">
            <span class="opsLabel">Recommended:</span>
            <span class="opsValue" id="recommendedAircraft">JA37C Jaktviggen</span>
        </div>
        <div class="opsRow">
            <span class="opsLabel">Aircraft Status:</span>
            <span class="opsValue statusReady" id="aircraftStatus">Ready</span>
        </div>
        <div class="opsRow">
            <span class="opsLabel">Connection:</span>
            <span class="opsValue statusStable" id="connectionStatus">Stable</span>
        </div>
    </div>
    
    <div id="launchSection">
        <button id="launchSimulator" class="launchBtn" disabled>
            <span class="btnIcon">🚁</span>
            <span class="btnText">LAUNCH SIMULATOR</span>
            <span class="btnSubtitle">Coming Soon</span>
        </button>
    </div>
</div>

<!-- include the js script -->
<script type="module" src="../assets/js/home.js"></script>

<?php
// include footer
include __DIR__ . '/../templates/footer.php';