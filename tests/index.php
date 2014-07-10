<?php

ini_set('display_startup_errors', true);

ini_set('display_errors', true);

ini_set('error_reporting', -1);

error_reporting(-1);

date_default_timezone_set('UTC');

ini_set('log_errors', 1);

ini_set('error_log', dirname(__FILE__) . '/_errorlog/' . date('Y-m-d') . '.php.log');

if (! file_exists(dirname(__FILE__) . '/_errorlog/') || ! is_dir(dirname(__FILE__) . '/_errorlog/'))
{
    mkdir(dirname(__FILE__) . '/_errorlog/', 0777, true);
}

require_once dirname(dirname(__FILE__)) . '/vendor/autoload_52.php';

require_once dirname(__FILE__) . '/controllers/include.php';


$path = 'nl/13/blblabla';

$config = array
(
    'router'    => array
    (
        'article'    => array
        (
            'route'     => 'ar\/(.*)|nl',
            'defaults'  => array
            (
                'module'        => 'mainmodule',
                'submodule'     => 'default',
                'controller'    => 'article',
                'action'        => 'newsletter',
                'title'         => '',
            ),
            'map'       => array
            (
                'title'         => '1',
            ),
            'reverse'   => 'ar/%1$s',
            'callbacks' => array(),
        ),
        'newsletter'    => array
        (
            'route'     => 'nl\/(\\d+)\/(.*)|nl',
            'defaults'  => array
            (
                'module'        => 'mainmodule',
                'submodule'     => 'default',
                'controller'    => 'article',
                'action'        => 'newsletter',
                'newsletter_id' => '-1',
                'title'         => '',
            ),
            'map'       => array
            (
                'newsletter_id' => '1',
                'title'         => '2',
            ),
            'reverse'   => 'nl/%1$s/%2$s',
            'callbacks' => array(),
        ),
    ),
);

$mvc = EhrlichAndreas_Util_Mvc::getInstance();

$mvc->addRouterConfig($config, 'router');

//$response = $mvc->runByParameter(array());

$response = $mvc->dispatch();

var_dump($response);
die();

//$request = new EhrlichAndreas_Util_Mvc_Request('http://local.de/' . $path);
$request = new EhrlichAndreas_Util_Mvc_Request();

$router = new EhrlichAndreas_Util_Mvc_Router();

$router->addConfig($config);

$matched = $router->route($request);

var_dump($matched);

die();

echo $route->assemble($map, true, true);

echo "\n\n";