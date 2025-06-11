<?php
/**
 * 403 Forbidden Error Page
 *
 * @file 403.php
 * @since 0.7.10
 * @package FlightSimWeb  
 * @author Robkoo
 */

// Set the http response code
http_response_code(403);

/**
 * The code to tailor the error page to.
 * @var int
 */
$errorCode = '403';
include __DIR__ . '/../../templates/errorPage.php';
?>