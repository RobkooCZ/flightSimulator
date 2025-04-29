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
 * @version 0.7.4
 * @see templates/header.php
 * @todo Add more footer content and links
 */

declare(strict_types=1);

// show the header if requested
if ($showFooter === true) {
    echo '
        <footer>
            Made by Robkoo
            <span class="footerVersion">Alpha v0.7.4</span>
        </footer>
        <style>
            
        </style>
    ';
}
?>

<!-- end the tags -->
</body>
</html>