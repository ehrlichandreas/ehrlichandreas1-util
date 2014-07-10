<?php

$microtime = microtime(true);

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

//change path or set it to null for tests
$path = 'http://local.de/nl/13/blblabla';

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
    'view'  => array
    (
        'scriptPath'    => dirname(__FILE__) . '/views',
        'fileExtension' => 'php',
    ),
);

$mvc = EhrlichAndreas_Util_Mvc::getInstance();

$mvc->addRouterConfig($config, 'router');

$mvc->addViewConfig($config, 'view');

//$response = $mvc->runByParameter(array());

$response = $mvc->dispatch($path);

echo $response;

echo "\n\n";
echo microtime(true) - $microtime;
echo "\n\n";
die();
