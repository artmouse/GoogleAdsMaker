<?php
define('MODE', 'dev');
/* localhost cron settings */
/*
 *
 * %progdir%\modules\wget\bin\wget.exe -q --no-cache http://anuka/makeAds -O %progdir%\userdata\temp\temp.txt
 * */
$logVersion = date('Y-m-d');
return [
    'settings' => [
        //ENV settings
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => true, // Allow the web server to send the content-length header
        'debug' => true,

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app'.$logVersion.'.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'access' => [
            'name' => 'SLIM',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/access'.$logVersion.'.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        //http settings
        'httpVersion' => '2.0',

        //db config
        'db' => [
            'dev' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'xxx',
                'username' => 'xxx',
                'engine' => 'InnoDB',
                'password' => '',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
            ],
            'local' => [
                'driver' => 'mysql',
                'host' => 'xxx',
                'database' => 'xxx',
                'username' => 'xxx',
                'engine' => 'InnoDB',
                'password' => 'xxx',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
            ],

        ],
    ],
];