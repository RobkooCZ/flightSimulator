<?php
// get the session
session_start();

// wipe all session data, logging out the user
session_unset();

session_destroy();

// redirect to the main page
header('Location: /');
exit();