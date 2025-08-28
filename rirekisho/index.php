<?php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$maintenance = __DIR__ . '/../rirekisho_core/storage/framework/maintenance.php';
if (file_exists($maintenance)) { require $maintenance; }

require __DIR__ . '/../rirekisho_core/vendor/autoload.php';
$app = require_once __DIR__ . '/../rirekisho_core/bootstrap/app.php';

/** @var Application $app */
$app->handleRequest(Request::capture());
