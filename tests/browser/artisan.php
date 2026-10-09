<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$_SERVER['SERVER_PORT'] ??= 19849;
$app = require __DIR__.'/application.php';
$kernel = $app->make(Kernel::class);
$input = new ArgvInput();
$output = new ConsoleOutput();
$status = $kernel->handle($input, $output);
$kernel->terminate($input, $status);
exit($status);
