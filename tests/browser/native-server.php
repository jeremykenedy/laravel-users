<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use jeremykenedy\laravelusers\Test\Fixtures\NativeRoleBrowser;

if (PHP_SAPI !== 'cli-server') {
    exit(1);
}

$app = require __DIR__.'/application.php';
$integration = $_COOKIE['lu-native-roles'] ?? null;
if (in_array($integration, ['laravel-roles', 'spatie'], true)) {
    (new NativeRoleBrowser())->boot($app, $integration);
}
$request = Request::capture();
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
