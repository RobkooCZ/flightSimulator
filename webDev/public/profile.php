<?php
/**
 * Profile page
 *
 * Users can add/change their pfps here, some of their info, preview statistics and the user info.
 *
 * @file profile.php
 * @since 0.7.7
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.10
 */
declare(strict_types=1);

use WebDev\Auth\AccessControl;
use WebDev\Auth\User;

/**
 * Flag to start the session (or not).
 * @var bool
 */
$startSession = false;

session_start();

// immediatelly check whether the user trying to access the site is logged in or not
AccessControl::requireAuth();

// for loading the pfp
use WebDev\Utilities\FileHandler;

// initialize bootstrap
use WebDev\Bootstrap;
Bootstrap::init();

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

// include header and the stylesheet for the current page
$stylesheet = 'profile';

/**
 * Title of the website.
 * @var string
 */
$title = 'Profile';

// include the header
include __DIR__ . '/../templates/header.php';

// user id
$uid = $_SESSION[User::SESSION_ID_KEY];

// get the profile picture path
$handler = new FileHandler($uid); // could be linkHandler, both share the same load method from the Handler parent class
$path = $handler->load();

if ($path === false) $path = ''; // set empty path if it isn't in the db yet
?>

<!-- Modal for the profile picture input -->
<div id="modal" class="hidden">
    <div id="modalContent">
        <!-- title -->
        <h2 id="title">Upload a profile picture</h2>

        <!-- input the image/link -->
        <div id="sourceInput">
            <!-- top "navbar" to change between image or link -->
            <div id="imageNavbar">
                <button id="fileNav" class="navSelected">File</button>
                <button id="linkNav">Link</button>
            </div>
            <div id="fileInputDiv">
                <label for="fileInput">Enter a file: </label>
                <input type="file" name="fileInput" id="fileInput" accept="image/jpeg, image/png, image/webp">
            </div>
            <div id="linkInputDiv" class="inputDivHide">
                <label for="linkInput">Enter a link to the image: </label>
                <input type="text" name="linkInput" id="linkInput">
            </div>
            <button id="inputButton">Select</button>
        </div>

        <!-- field for messages -->
        <h3 id="message"></h3>

        <!-- description -->
        <p id="description"><span class="italicFade">Only JPEG, JPG, PNG and WEBP are supported. Max 5MiB filesize.</span></p>
        
        <!-- close "button" -->
        <img src="../assets/images/icons/close.png" id="close">
    </div>
</div>

<!-- main content of the page -->
<div id="content">
    <aside id="left">
        <div id="pfpContainer">
            <img src="<?= htmlspecialchars($path) ?>" id="pfp" alt="userPfp">
            <img src="../assets/images/pfps/default/onhover.png" id="hoverImg" alt="hover">
        </div>
        <h3 id="username"><span class="italicFade">Username:</span> <?= htmlspecialchars($_SESSION[User::SESSION_USERNAME_KEY]) ?></h3>
        <p id="id"><span class="italicFade">ID:</span> #<?= htmlspecialchars((string)$_SESSION[User::SESSION_ID_KEY]) ?></p>
        <p id="createdAt"><span class="italicFade">Created at:</span> <?= htmlspecialchars((string)$_SESSION[User::SESSION_CREATED_AT_KEY]) ?></span>
        <div id="bioDiv">
                <span class="italicFade">Bio:</span>
                <p id="bio"><?= User::fetchBio($_SESSION[User::SESSION_ID_KEY]) ?></p>
        </div>

        <section id="settings">
            <h2>Settings</h2>
            
            <div id="themeSettingDiv">
                <label for="themeSelect">Theme: </label>
                <select name="themeSelect" id="themeSelect">
                    <option value="light">Light</option>
                    <option value="dark-theme" selected="selected">Dark</option>
                    <!-- only show the custom theme if the user is either me or elll -->
                    <?php if (!empty($_SESSION[User::SESSION_ID_KEY]) && in_array($_SESSION[User::SESSION_ID_KEY], [1, 2])): ?>
                        <option value="custom-theme">CGT</option>
                    <?php endif; ?>
                </select>
            </div>
        </section>
    </aside>

    <main id="right">
        <section id="top">
            <h2 id="updateTitle">Update profile</h2>

            <!-- Username -->
            <div id="usernameChangeDiv">
                <label for="usernameChange">New Username: </label>
                <input name="usernameChange" id="usernameChange" type="text">
            </div>

            <!-- Bio -->
            <div id="bioChangeDiv">
                <label for="bioChange">New Bio: </label>
                <textarea name="bioChange" id="bioChange" type="text"></textarea>
            </div>

            <!-- password change coming soonTM -->

            <!-- field for message -->
            <h3 id="changeMessage"></h3>

            <!-- submit button -->
            <div id="submitChangeDiv">
                <button id="submitChange">Save changes</button>
            </div>
        </section>
        
        <!-- not done because the api bridge isn't done, the game isn't either -->
        <section id="bottom">
            <div id="iconNavBar">
                <a class="selected">1</a>
                <a>2</a>
                <a>3</a>
                <a>4</a>
                <a>5</a>
                <a>6</a>
            </div>
            <div id="statisticsContent">
                <div id="firstStats" class="visible">
                    <h2>Km flown: <span>0</span></h2>
                </div>
                <div id="secondStats">
                    <h2>Top speed: <span>0</span> <span>km/h</span></h2>
                </div>
                <div id="thirdStats">
                    <h2>Favorite plane: <span>None</span></h2>
                </div>
                <div id="fourthStats">
                    <h2>Fuel burnt: <span>0</span></h2>
                </div>
                <div id="fifthStats">
                    <h2>Enemy planes killed: <span>0</span></h2>
                </div>
                <div id="sixthStats">
                    <h2>Died: <span>0</span></h2>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- include the js script -->
<script src="../assets/js/profile.js" type="module"></script>

<?php
include __DIR__ . '/../templates/footer.php';