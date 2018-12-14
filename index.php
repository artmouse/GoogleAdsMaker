<?php
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Pragma: no-cache');
//date_default_timezone_set('UTC');
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}
require __DIR__ . '/vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__ . '/core/Config.php';

$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/core/Dependencies.php';
// Register middleware
require __DIR__ . '/core/Middleware.php';
// Register routes
require __DIR__ . '/core/Router.php';
// Run app


$app->run();

