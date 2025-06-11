<?php
/**
 * 500 Internal Server Error Page
 *
 * @file 500.php
 * @since 0.7.10
 * @package FlightSimWeb
 * @author Robkoo
 */

//  Set response code
http_response_code(500);

/**
 * The error code to tailor the errorPage to.
 * @var int
 */
$errorCode = '500';
include __DIR__ . '/../../templates/errorPage.php';
?>