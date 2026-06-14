<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (PHP_VERSION_ID >= 80400) {
    $originalErrorReporting = error_reporting();
    error_reporting($originalErrorReporting & ~E_DEPRECATED);
}

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

if (PHP_VERSION_ID >= 80400 && isset($originalErrorReporting)) {
    error_reporting($originalErrorReporting);
}

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
