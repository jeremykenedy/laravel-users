<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;

require dirname(__DIR__, 2).'/vendor/autoload.php';

if (PHP_SAPI !== 'cli-server') {
    exit(1);
}

$preview = (int) ($_SERVER['SERVER_PORT'] ?? 0) === 19849;
$runtime = $preview ? sys_get_temp_dir().'/laravelusers-preview-runtime' : __DIR__.'/runtime';
if (!is_dir($runtime)) {
    mkdir($runtime, 0755, true);
}
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
    'mail.default'                          => 'log',
    'app.debug'                             => true,
    'database.connections.testing.database' => $runtime.'/database.sqlite',
    'session.driver'                        => 'file',
    'session.files'                         => $runtime.'/sessions',
    'view.compiled'                         => $runtime.'/views',
    'laravelusers.defaultUserModel'         => SoftUser::class,
    'auth.providers.users.model'            => SoftUser::class,
    'laravelusers.softDeletedEnabled'       => ($_COOKIE['lu-soft-deletes'] ?? ($preview ? '1' : '0')) === '1',
    'laravelusers.avatar.enabled'           => ($_COOKIE['lu-avatar'] ?? '1') !== '0',
    'laravelusers.avatar.source'            => in_array($_COOKIE['lu-avatar'] ?? '', ['initials', 'gravatar', 'avatar'], true) ? $_COOKIE['lu-avatar'] : 'initials',
    'laravelusers.frontend'                 => in_array($_COOKIE['lu-framework'] ?? '', ['bootstrap4', 'bootstrap5', 'tailwind'], true) ? $_COOKIE['lu-framework'] : 'bootstrap4',
    'laravelusers.responsiveTable'          => ($_COOKIE['lu-responsive-table'] ?? '1') !== '0',
    'laravelusers.bulkActions'              => true,
    'laravelusers.columnVisibility'         => true,
    'laravelusers.theme'                    => 'system',
    'laravelusers.themeToggle'              => ($_COOKIE['lu-theme-toggle'] ?? '1') !== '0',
    'laravelusers.tableSorting'             => ($_COOKIE['lu-table-controls'] ?? '1') !== '0',
    'laravelusers.tableFiltering'           => ($_COOKIE['lu-table-controls'] ?? '1') !== '0',
    'laravelusers.paginateListSize'         => (int) ($_SERVER['SERVER_PORT'] ?? 0) === 19849 ? 25 : 2,
    'laravelusers.activity.login'           => true,
    'laravelusers.activity.online'          => true,
    'cache.default'                         => 'file',
    'cache.stores.file.path'                => $runtime.'/cache',
]);

if (!Schema::hasTable('laravelusers_login_activity')) {
    (require dirname(__DIR__, 2).'/src/database/migrations/2026_10_07_000000_create_laravelusers_login_activity_table.php')->up();
}

if (!Schema::hasTable('users')) {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->string('email')->unique();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
    $names = ['Morgan Hayes', 'Alex Rivers', 'Sam Parker'];
    if (!$preview) {
        $names[] = '<img src=x onerror=alert(1)>';
    }
    foreach ($names as $index => $name) {
        User::create(['name' => $name, 'email' => 'user'.$index.'@example.com', 'password' => bcrypt('password')]);
    }
}
if (!Schema::hasColumn('users', 'deleted_at')) {
    Schema::table('users', function (Blueprint $table) { $table->softDeletes(); });
}
Route::middleware('web')->get('/__browser/{framework}', function ($framework) {
    abort_unless(in_array($framework, ['bootstrap4', 'bootstrap5', 'tailwind'], true), 404);
    Auth::login(SoftUser::findOrFail(1));

    return redirect('/users')->withCookie(cookie('lu-framework', $framework, 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-theme-toggle', request()->query('theme-toggle', '1'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-table-controls', request()->query('table-controls', '1'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-soft-deletes', request()->query('soft-deletes', (int) ($_SERVER['SERVER_PORT'] ?? 0) === 19849 ? '1' : '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-avatar', request()->query('avatar', 'initials'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-responsive-table', request()->query('responsive-table', '1'), 60, '/', null, false, false, false));
});
EncryptCookies::except(['lu-framework', 'lu-theme-toggle', 'lu-table-controls', 'lu-soft-deletes', 'lu-avatar', 'lu-responsive-table']);
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
