<?php
use WebDev\Functions\Auth;

// logout
Auth::getInstance()->logout();

// redirect to the main page
header('Location: /');
exit();