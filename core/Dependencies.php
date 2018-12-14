<?php
$container = $app->getContainer();
// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));

    return $logger;
};

// http cache
$container['cache'] = function () {
    $cache = new \Slim\HttpCache\CacheProvider();

    return $cache;
};

//xls editor


$container['db'] = function ($container) {

    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['db'][MODE]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
};





$container['view'] = function ($container) {
    $loader = new Twig_Loader_Filesystem('app/templates/email/');

    $twig = new Twig_Environment($loader, array(
        'cache' => 'app/templates/cache',
        'auto_reload' => true,
        'debug' => true
    ));
    $twig->addExtension(new Twig_Extension_Debug());
    return $twig;
};

$container['twig'] = function ($container) {
    $loader = new Twig_Loader_Filesystem('app/dashboard/views/templates');

    $twig = new Twig_Environment($loader, array(
        'cache' => 'app/dashboard/views/cache',
        'auto_reload' => true,
        'debug' => true
    ));
    $twig->addGlobal('session', $_SESSION);
    $twig->addGlobal('get', $_GET);
    if(!empty($_SERVER['QUERY_STRING'])) {
        $twig->addGlobal('url', $_SERVER['REQUEST_URI']);
    } else {
        $twig->addGlobal('url', $_SERVER['REQUEST_URI'].'?');
    }
    $twig->addExtension(new Twig_Extension_Debug());

    // an anonymous function
    $filterJsonDecode = new Twig_Filter('jsonDecode', function ($string) {
        return (array)json_decode($string);
    });
    $arrayPrint = new Twig_Filter('arrayPrint', function ($array) {
        return print_r($array);
    });

    $twig->addFilter($filterJsonDecode);
    $twig->addFilter($arrayPrint);

    //return $view;
    return $twig;
};

$container['cookie'] = function($c){
    $request = $c->get('request');
    return new \Slim\Http\Cookies($request->getCookieParams());
};
