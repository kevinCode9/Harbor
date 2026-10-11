<?php
/**
 * This file goes in public_html/index.php (or public_html of your addon domain).
 * Edit LARAVEL_PATH to the absolute path of your liveled folder on the server.
 *
 * Example:  /home/myuser/liveled
 */
define('LARAVEL_PATH', '/home/ledn/liveled');

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// PHP 8.4 polyfill
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

if (file_exists($maintenance = LARAVEL_PATH.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require LARAVEL_PATH.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once LARAVEL_PATH.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
