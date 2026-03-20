<?php
/**
 * PHPUnit bootstrap file
 */

// Define the test directory
define('TEST_DIR', __DIR__);
define('ROOT_DIR', dirname(TEST_DIR));

// Include Composer autoloader
$autoloader = require ROOT_DIR . '/vendor/autoload.php';

// Ensure timezone is set
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}
