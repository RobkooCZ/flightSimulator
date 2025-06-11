<?php
/**
 * 404 Not Found Error Page
 *
 * @file 404.php
 * @since 0.7.10
 * @package FlightSimWeb
 * @author Robkoo
 */

//  Set response code
http_response_code(404);

/**
 * The error code to tailor the errorPage to.
 * @var int
 */
$errorCode = '404';
include __DIR__ . '/../../templates/errorPage.php';
?>