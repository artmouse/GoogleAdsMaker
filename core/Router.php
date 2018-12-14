<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Cookies;
use Slim\Http\Headers;

ini_set('display_errors', true);
error_reporting(E_ALL);

/* Google API V1 */
$app->get('/feed', \Adson\Model\Google\v1\Account::class . ':prepareFile');
$app->get('/makeAds', \Adson\Model\Google\v1\Account::class . ':checkProducts');


$app->get('/', function ($request, $response, $args) {
    // Use app HTTP cookie service
    echo "<ul>
        <li><a target='_blank' href='/feed'>prepare file </a></li>
        <li><a target='_blank'href='/makeAds'>Make ads</a></li>
    </ul>";
    $dir    = $_SERVER['DOCUMENT_ROOT'].'/logs';
    $files = scandir($dir);
    echo "<h2>LOG FILES</h2>";
    foreach ($files as $file) {
        echo "<a target='_blank' href='/file/$file'>$file</a><br>";
    }
});


$app->get('/file/{file}', function ($request, $response, $args) {
    // Use app HTTP cookie service
    $dir    = $_SERVER['DOCUMENT_ROOT'].'/logs';
    $file = $args['file'];
    $filename = $dir.'/'.$file;

    $trimmed = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $logData = array_reverse($trimmed);

    foreach ($logData as $data) {
        echo '<div style="width: 100%">';
        echo $data;
        echo '</div><br>';
    }

});