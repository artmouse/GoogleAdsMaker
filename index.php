<?php
if (PHP_SAPI == 'cli-server') {
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

