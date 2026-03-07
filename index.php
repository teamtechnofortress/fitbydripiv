<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode
if (file_exists($maintenance = __DIR__.'/backend/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader
require __DIR__.'/backend/vendor/autoload.php';

// Bootstrap
$app = require_once __DIR__.'/backend/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);