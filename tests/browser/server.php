<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

if (PHP_SAPI !== 'cli-server') {
    exit(1);
}

$app = require __DIR__.'/application.php';
$request = Request::capture();
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
