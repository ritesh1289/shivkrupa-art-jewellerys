<?php
/**
 * SHIVKRUPA ART JEWELLERY
 * Customer Logout Action
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/auth.php';

logoutUser();

setFlash('info', 'You have been signed out of your account successfully.');
redirect(BASE_URL . '/login.php');
