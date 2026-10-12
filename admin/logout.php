<?php
/**
 * SHIVKRUPA ART JEWELLERY — Admin Logout Action
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/middleware/admin-auth.php';

logoutAdmin();

setFlash('info', 'Administrator session closed.');
redirect(ADMIN_URL . '/login.php');
