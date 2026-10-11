<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// PHP 8.4 polyfill: Symfony HttpFoundation calls this for DELETE/PUT/PATCH body parsing
if (!function_exists('request_parse_body')) {
    function request_parse_body(int $flags = 0): array {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = file_get_contents('php://input');
        if ($input !== '' && stripos($ct, 'application/x-www-form-urlencoded') !== false) {
            parse_str($input, $data);
            return [$data, []];
        }
        return [$_POST, $_FILES];
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
