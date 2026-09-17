<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;

require dirname(__DIR__, 2).'/vendor/autoload.php';

if (PHP_SAPI !== 'cli-server') {
    exit(1);
}

$runtime = __DIR__.'/runtime';
foreach (['sessions', 'views'] as $directory) {
    if (!is_dir($runtime.'/'.$directory)) {
        mkdir($runtime.'/'.$directory, 0755, true);
    }
}
if (!file_exists($runtime.'/database.sqlite')) {
    touch($runtime.'/database.sqlite');
}

if (!file_exists($runtime.'/app-key')) {
    file_put_contents($runtime.'/app-key', 'base64:'.base64_encode(random_bytes(32)));
}

$app = (new TestCase('browser'))->createApplication();
$app->instance('env', 'local');
config([
    'app.key'                               => file_get_contents($runtime.'/app-key'),
    'app.name'                              => 'Laravel Users',
    'app.debug'                             => true,
    'database.connections.testing.database' => $runtime.'/database.sqlite',
    'session.driver'                        => 'file',
    'session.files'                         => $runtime.'/sessions',
    'view.compiled'                         => $runtime.'/views',
    'laravelusers.frontend'                 => in_array($_COOKIE['lu-framework'] ?? '', ['bootstrap4', 'bootstrap5', 'tailwind'], true) ? $_COOKIE['lu-framework'] : 'bootstrap4',
    'laravelusers.theme'                    => 'system',
    'laravelusers.themeToggle'              => true,
    'laravelusers.paginateListSize'         => 2,
]);

if (!Schema::hasTable('users')) {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->string('email')->unique();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
    foreach (['Morgan Hayes', 'Alex Rivers', 'Sam Parker', '<img src=x onerror=alert(1)>'] as $index => $name) {
        User::create(['name' => $name, 'email' => 'user'.$index.'@example.com', 'password' => bcrypt('password')]);
    }
}
Route::middleware('web')->get('/__browser/{framework}', function ($framework) {
    abort_unless(in_array($framework, ['bootstrap4', 'bootstrap5', 'tailwind'], true), 404);
    Auth::login(User::findOrFail(1));

    return redirect('/users')->withCookie(cookie('lu-framework', $framework, 60, '/', null, false, false, false));
});
EncryptCookies::except(['lu-framework']);
Route::post('/logout', function () {
    Auth::logout();

    return redirect('/login');
})->middleware('web')->name('logout');
$app['router']->getRoutes()->refreshNameLookups();

$request = Request::capture();
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
