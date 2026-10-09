<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use jeremykenedy\laravelusers\Support\PublicAssets;
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
foreach (['sessions', 'views', 'config', 'storage'] as $directory) {
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
$app->useConfigPath($runtime.'/config');
$app->useStoragePath($runtime.'/storage');
$app->instance('env', 'local');
config([
    'app.key'                                     => file_get_contents($runtime.'/app-key'),
    'app.name'                                    => 'Laravel Users',
    'mail.default'                                => 'log',
    'app.debug'                                   => true,
    'database.connections.testing.database'       => $runtime.'/database.sqlite',
    'session.driver'                              => 'file',
    'session.files'                               => $runtime.'/sessions',
    'view.compiled'                               => $runtime.'/views',
    'laravelusers.defaultUserModel'               => SoftUser::class,
    'auth.providers.users.model'                  => SoftUser::class,
    'laravelusers.softDeletedEnabled'             => ($_COOKIE['lu-soft-deletes'] ?? ($preview ? '1' : '0')) === '1',
    'laravelusers.avatar.enabled'                 => ($_COOKIE['lu-avatar'] ?? '1') !== '0',
    'laravelusers.avatar.per_user'                => $preview || ($_COOKIE['lu-avatar-preferences'] ?? '0') === '1',
    'laravelusers.settings.packages.enabled'      => $preview || ($_COOKIE['lu-packages'] ?? '0') === '1',
    'queue.default'                               => ($_COOKIE['lu-packages'] ?? '0') === '1' ? 'database' : 'sync',
    'queue.connections.database.retry_after'      => 600,
    'laravelusers.appearance.per_user'            => $preview || ($_COOKIE['lu-appearance'] ?? '0') === '1',
    'laravelusers.settings.enabled'               => $preview || ($_COOKIE['lu-settings'] ?? '0') === '1',
    'laravelusers.showLogout'                     => true,
    'laravelusers.emails.enabled'                 => true,
    'laravelusers.welcome.enabled'                => true,
    'laravelusers.password.meter'                 => true,
    'laravelusers.password.confirmation_feedback' => true,
    'laravelusers.responsiveButtons'              => true,
    'laravelusers.localizeDates'                  => true,
    'laravelusers.emailLinks'                     => true,
    'laravelusers.showOnlineColumn'               => true,
    'laravelusers.showLastLoginColumn'            => true,
    'laravelusers.showLastLoginDetailsColumn'     => true,
    'laravelusers.showUserCount'                  => true,
    'laravelusers.avatar.source'                  => in_array($_COOKIE['lu-avatar'] ?? '', ['initials', 'gravatar', 'avatar'], true) ? $_COOKIE['lu-avatar'] : 'initials',
    'laravelusers.profileCardColor'               => $_COOKIE['lu-profile-color'] ?? '#2458b7',
    'laravelusers.frontend'                       => in_array($_COOKIE['lu-framework'] ?? '', Frontend::FRAMEWORKS, true) ? $_COOKIE['lu-framework'] : ($preview ? 'bootstrap5' : 'bootstrap4'),
    'laravelusers.tableViewToggle'                => ($_COOKIE['lu-view-toggle'] ?? '1') !== '0',
    'laravelusers.responsiveTable'                => ($_COOKIE['lu-responsive-table'] ?? '1') !== '0',
    'laravelusers.tableButtonsIconOnly'           => ($_COOKIE['lu-icons-only'] ?? '0') === '1',
    'laravelusers.searchDebounceEnabled'          => ($_COOKIE['lu-search-debounce-enabled'] ?? '1') !== '0',
    'laravelusers.searchDebounce'                 => max(0, (int) ($_COOKIE['lu-search-delay'] ?? 2000)),
    'laravelusers.dateStyle'                      => in_array($_COOKIE['lu-date-style'] ?? '', ['full', 'long', 'medium', 'short'], true) ? $_COOKIE['lu-date-style'] : 'short',
    'laravelusers.fullWidth'                      => ($_COOKIE['lu-full-width'] ?? ($preview ? '1' : '0')) === '1',
    'laravelusers.account_links.enabled'          => true,
    'laravelusers.emails.throttle'                => $preview ? '10,1' : '1000,1',
    'laravelusers.emails.goodbye'                 => ($_COOKIE['lu-goodbye'] ?? '0') === '1',
    'laravelusers.bulkActions'                    => true,
    'laravelusers.columnVisibility'               => true,
    'laravelusers.theme'                          => 'system',
    'laravelusers.themeToggle'                    => ($_COOKIE['lu-theme-toggle'] ?? '1') !== '0',
    'laravelusers.tableSorting'                   => ($_COOKIE['lu-table-controls'] ?? '1') !== '0',
    'laravelusers.tableFiltering'                 => ($_COOKIE['lu-table-controls'] ?? '1') !== '0',
    'laravelusers.paginateListSize'               => (int) ($_SERVER['SERVER_PORT'] ?? 0) === 19849 ? 25 : 2,
    'laravelusers.activity.login'                 => true,
    'laravelusers.activity.online'                => true,
    'cache.default'                               => 'file',
    'cache.stores.file.path'                      => $runtime.'/cache',
]);
if (is_file(config_path('laravelusers-packages.php'))) {
    config(['laravelusers-packages' => require config_path('laravelusers-packages.php')]);
}
PackageRequirements::load();
if (($_COOKIE['lu-published-assets'] ?? '0') === '1') {
    $app->make(PublicAssets::class)->publish();
}
Route::get('/vendor/laravelusers/releases/{release}/{asset}', function ($release, $asset) {
    return response()->file(public_path('vendor/laravelusers/releases/'.$release.'/'.$asset), [
        'Content-Type' => str_ends_with($asset, '.css') ? 'text/css' : 'text/javascript',
    ]);
})->where('release', '[a-f0-9]{64}')->where('asset', '[a-z0-9-]+\.(?:css|js)');
View::replaceNamespace('laravelusers', dirname(__DIR__, 2).'/src/resources/views');
Gate::define('manage-laravelusers-settings', fn ($user) => $user->getKey() === 1);
Gate::define('manage-laravelusers-packages', fn ($user) => $user->getKey() === 1);
View::composer('laravelusers::partials.package-settings', function ($view) use ($preview) {
    if (!$preview && ($_COOKIE['lu-packages'] ?? '0') === '1') {
        $view->with('managedPackages', ['toast' => false, 'laravel-roles' => false, 'spatie' => true]);
    }
});
Route::get('/__components', fn () => view('navigation-components'))->middleware('web');

foreach (['appearance' => '2026_10_08_150344_create_laravelusers_appearance_preferences_table.php', 'settings' => '2026_10_08_145311_create_laravelusers_settings_table.php'] as $feature => $migration) {
    $table = $feature === 'settings' ? 'laravelusers_settings' : 'laravelusers_appearance_preferences';
    if (config('laravelusers.'.$feature.'.'.($feature === 'settings' ? 'enabled' : 'per_user')) && !Schema::hasTable($table)) {
        (require dirname(__DIR__, 2).'/src/database/'.$feature.'/'.$migration)->up();
    }
}

if (config('laravelusers.appearance.per_user') && !Schema::hasColumn('laravelusers_appearance_preferences', 'gradient_strength')) {
    (require dirname(__DIR__, 2).'/src/database/appearance/2026_10_08_153657_add_gradient_strength_to_laravelusers_appearance_preferences_table.php')->up();
}
if (config('laravelusers.appearance.per_user') && !Schema::hasColumn('laravelusers_appearance_preferences', 'dark_gradient_strength')) {
    (require dirname(__DIR__, 2).'/src/database/appearance/2026_10_08_164316_add_dark_appearance_to_laravelusers_appearance_preferences_table.php')->up();
}

if ($preview || ($_COOKIE['lu-accounts'] ?? '0') === '1') {
    foreach (['accounts', 'avatar', 'appearance'] as $feature) {
        foreach (glob(dirname(__DIR__, 2).'/src/database/'.$feature.'/*.php') as $path) {
            $table = match ($feature) {
                'accounts'   => str_contains($path, 'email_changes') ? 'laravelusers_email_changes' : 'laravelusers_account_preferences',
                'avatar'     => 'laravelusers_avatar_preferences',
                'appearance' => 'laravelusers_appearance_preferences',
            };
            $column = str_contains($path, 'add_dark_') ? 'dark_gradient_strength' : (str_contains($path, 'add_gradient_') ? 'gradient_strength' : null);
            if (!Schema::hasTable($table) || ($column && !Schema::hasColumn($table, $column))) {
                (require $path)->up();
            }
        }
    }
}

if (!Schema::hasTable(config('auth.passwords.users.table'))) {
    Schema::create(config('auth.passwords.users.table'), function (Blueprint $table) {
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });
}
if (config('laravelusers.avatar.per_user') && !Schema::hasTable('laravelusers_avatar_preferences')) {
    (require dirname(__DIR__, 2).'/src/database/avatar/2026_10_08_095110_create_laravelusers_avatar_preferences_table.php')->up();
}
if (!Schema::hasTable('laravelusers_account_links')) {
    (require dirname(__DIR__, 2).'/src/database/account-links/2026_10_08_000000_create_laravelusers_account_links_table.php')->up();
}
Route::get('/reset-password/{token}', fn () => 'Password reset form')->name('password.reset');

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
    abort_unless(in_array($framework, Frontend::FRAMEWORKS, true), 404);
    Auth::login(SoftUser::findOrFail(1));
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) !== 19849) {
        foreach (['laravelusers-settings-write', 'laravelusers-packages-write', 'laravelusers-packages-verify'] as $limiter) {
            RateLimiter::clear($limiter.sha1('1'));
        }
    }

    return redirect('/users')->withCookie(cookie('lu-framework', $framework, 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-goodbye', request()->query('goodbye', '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-accounts', request()->query('accounts', '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-theme-toggle', request()->query('theme-toggle', '1'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-table-controls', request()->query('table-controls', '1'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-soft-deletes', request()->query('soft-deletes', (int) ($_SERVER['SERVER_PORT'] ?? 0) === 19849 ? '1' : '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-avatar-preferences', request()->query('avatar-preferences', '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-appearance', request()->query('appearance', '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-packages', request()->query('packages', '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-settings', request()->query('settings', '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-avatar', request()->query('avatar', 'initials'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-view-toggle', request()->query('view-toggle', '1'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-full-width', request()->query('full-width', (int) ($_SERVER['SERVER_PORT'] ?? 0) === 19849 ? '1' : '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-profile-color', request()->query('profile-color', '#2458b7'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-icons-only', request()->query('icons-only', '0'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-search-debounce-enabled', request()->query('search-debounce', '1'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-search-delay', request()->query('search-delay', '2000'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-date-style', request()->query('date-style', 'short'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-responsive-table', request()->query('responsive-table', '1'), 60, '/', null, false, false, false))
        ->withCookie(cookie('lu-published-assets', request()->query('published-assets', '0'), 60, '/', null, false, false, false));
});
EncryptCookies::except(['lu-framework', 'lu-goodbye', 'lu-accounts', 'lu-theme-toggle', 'lu-table-controls', 'lu-soft-deletes', 'lu-avatar', 'lu-avatar-preferences', 'lu-appearance', 'lu-settings', 'lu-packages', 'lu-responsive-table', 'lu-icons-only', 'lu-search-debounce-enabled', 'lu-search-delay', 'lu-date-style', 'lu-profile-color', 'lu-view-toggle', 'lu-full-width', 'lu-published-assets']);
Route::post('/logout', function () {
    Auth::logout();

    return redirect('/login');
})->middleware('web')->name('logout');
$app['router']->getRoutes()->refreshNameLookups();
if (!$preview) {
    foreach (['users.email', 'users.email.preview'] as $name) {
        $route = $app['router']->getRoutes()->getByName($name);
        $action = $route->getAction();
        $action['middleware'] = array_map(fn ($middleware) => str_starts_with($middleware, 'throttle:') ? 'throttle:1000,1' : $middleware, $action['middleware']);
        $route->setAction($action);
    }
}

$request = Request::capture();
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
