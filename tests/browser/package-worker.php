<?php

use Illuminate\Contracts\Console\Kernel;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use Symfony\Component\Console\Output\ConsoleOutput;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$_SERVER['SERVER_PORT'] = (int) ($argv[1] ?? 19847);
$_COOKIE['lu-settings'] = '1';
$_COOKIE['lu-packages'] = '1';
$app = require __DIR__.'/application.php';
$app->make(PackageRequirements::class)->configure();
fwrite(STDOUT, "Package worker ready.\n");
exit($app->make(Kernel::class)->call('queue:work', ['connection' => 'laravelusers-packages', '--queue' => 'default', '--timeout' => 360, '--sleep' => 1, '--tries' => 1], new ConsoleOutput()));
