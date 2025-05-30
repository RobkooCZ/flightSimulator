<?php
/**
 * Footer Template
 *
 * Displays the footer for the flight simulator web application.
 * Only shown if $showFooter is true.
 *
 * @file footer.php
 * @since 0.1
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.9
 * @see templates/header.php
 * @todo Add more footer content and links
 */

declare(strict_types=1);
?>

<!-- show the footer if requested -->
<?php if ($showFooter === true): ?>
    <footer>
        <div class="footerContainer">
            <!-- Left section - Branding -->
            <div class="footerSection brand">
                <span class="footerLogo">Flight Simulator</span>
                
                <div class="versionInfo">
                    <span class="versionLabel">Website:</span>
                    <span class="footerVersion website">Alpha v0.7.9</span>
                </div>
                
                <div class="versionInfo">
                    <span class="versionLabel">Game:</span>
                    <span class="footerVersion game">Alpha v0.3.3</span>
                </div>
                
                <p class="copyright">© 2025 Robkoo. All rights reserved.</p>
            </div>
            
            <!-- Middle section - Navigation -->
            <div class="footerSection footerLinks">
                <h4>Quick Links</h4>
                <a href="/about" class="links">About</a>
                <a href="/contact" class="links">Contact</a>
                <a href="https://github.com/RobkooCZ/flightSimulator" target="_blank" class="links">GitHub Repository</a>
                <a href="https://github.com/RobkooCZ/flightSimulator/blob/main/DOCUMENTATION.md" target="_blank" class="links">Documentation</a>
            </div>
            
            <!-- Right section - Additional Info -->
            <div class="footerSection info">
                <h4>About Project</h4>
                <p>A realistic flight simulator built with passion for Swedish aviation.</p>
                <div class="techStack">
                    <span class="tech">C</span>
                    <span class="tech">PHP</span>
                    <span class="tech">JavaScript</span>
                </div>
            </div>
        </div>
        
        <!-- Bottom bar -->
        <div class="footerBottom">
            <p>Built with ❤️ for aviation enthusiasts</p>
        </div>
    </footer>
<?php endif; ?>

<!-- end the tags -->
</body>
</html>