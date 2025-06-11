<?php
/**
 * Error Page Template
 *
 * Generic template for displaying HTTP error pages with flight simulator theming.
 *
 * @file errorPage.php  
 * @since 0.7.10
 * @package FlightSimWeb
 * @author Robkoo
 * @license TBD
 * @version 0.7.10
 */

use WebDev\Bootstrap;
Bootstrap::init();

// Error page configuration
/**
 * @var array<int,array<string,string>>
 */
$errorConfig = [
    '404' => [
        'title' => 'Flight Path Not Found',
        'icon' => '🛩️',
        'heading' => '404 - Navigation Error',
        'message' => 'The flight path you requested has gone off radar. The page may have been moved or doesn\'t exist.',
        'technical' => 'HTTP 404 Not Found'
    ],
    '403' => [
        'title' => 'Restricted Airspace',
        'icon' => '🚫',
        'heading' => '403 - Access Denied',
        'message' => 'You don\'t have clearance to access this restricted airspace. Please check your authorization.',
        'technical' => 'HTTP 403 Forbidden'
    ],
    '500' => [
        'title' => 'System Malfunction',
        'icon' => '⚠️',
        'heading' => '500 - Server Error',
        'message' => 'We\'re experiencing technical difficulties. Our ground crew is working to fix the issue.',
        'technical' => 'HTTP 500 Internal Server Error'
    ]
];

$currentError = $errorConfig[$errorCode] ?? $errorConfig['404'];

$title = $currentError['title'];
$stylesheet = 'error';
$showHeader = false;
$showFooter = false;
$startSession = false;

include __DIR__ . '/header.php';
?>

<div class="errorContainer">
    <div class="errorContent">
        <div class="errorIcon"><?= $currentError['icon'] ?></div>
        <h1 class="errorHeading"><?= $currentError['heading'] ?></h1>
        <p class="errorMessage"><?= $currentError['message'] ?></p>
        <p class="errorTechnical"><?= $currentError['technical'] ?></p>
        
        <div class="errorActions">
            <a href="/" class="btnPrimary">
                <span class="btnIcon">🏠</span>
                Return to Base
            </a>
            <a href="javascript:history.back()" class="btnSecondary">
                <span class="btnIcon">↩️</span>
                Go Back
            </a>
        </div>
        
        <div class="errorHelp">
            <p>If you believe this is an error, please contact ground control:</p>
            <p><strong>Support:</strong> admin@rfs.wuaze.com</p>
        </div>
    </div>
    
    <div class="errorBackground">
        <div class="radarSweep"></div>
        <div class="radarBlip"></div>
        <div class="radarBlip"></div>
        <div class="radarBlip"></div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>