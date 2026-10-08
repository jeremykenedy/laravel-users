<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use jeremykenedy\laravelusers\App\Http\Controllers\AccountAccessController;
use jeremykenedy\laravelusers\App\Http\Controllers\AccountController;
use jeremykenedy\laravelusers\App\Http\Controllers\AccountLinksController;
use jeremykenedy\laravelusers\App\Http\Controllers\CleanupSettingsController;
use jeremykenedy\laravelusers\App\Http\Controllers\EmailTemplatesController;
use jeremykenedy\laravelusers\App\Http\Controllers\ImpersonationController;
use jeremykenedy\laravelusers\App\Http\Controllers\PackageSettingsController;
use jeremykenedy\laravelusers\App\Http\Controllers\UsersManagementController;
use jeremykenedy\laravelusers\App\Http\Middleware\AccountMiddleware;
use jeremykenedy\laravelusers\App\Http\Middleware\UserAccessMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

Route::middleware(['web', 'throttle:'.config('laravelusers.account_links.throttle', '20,1')])->group(function () {
    Route::get('users/account-link/{token}', [AccountLinksController::class, 'show'])->name('users.account-link');
    Route::post('users/account-link/{token}', [AccountLinksController::class, 'confirm'])->name('users.account-link.confirm');
});

Route::middleware(['web', 'auth', AccountMiddleware::class])->group(function () {
    Route::get('users/account', [AccountController::class, 'show'])->name('users.account');
    Route::put('users/account', [AccountController::class, 'update'])->middleware('throttle:'.config('laravelusers.account.throttle', '10,1').',laravelusers-account-write')->name('users.account.update');
    Route::delete('users/account', [AccountController::class, 'destroy'])->middleware('throttle:'.config('laravelusers.account.throttle', '10,1').',laravelusers-account-delete')->name('users.account.delete');
    Route::get('users/account/email/{token}', [AccountController::class, 'confirmEmail'])->middleware('throttle:20,1,laravelusers-account-confirm')->name('users.account.email.confirm');
    Route::post('users/account/email/{token}', [AccountController::class, 'acceptEmail'])->middleware('throttle:10,1,laravelusers-account-confirm-write')->name('users.account.email.accept');
});

// APP Routes Below
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('users/{id}/impersonate', [ImpersonationController::class, 'start'])->where('id', '[0-9]+')->middleware('throttle:10,1,laravelusers-impersonate')->name('users.impersonate');
    Route::post('users/impersonation/stop', [ImpersonationController::class, 'stop'])->middleware('throttle:10,1,laravelusers-impersonate-stop')->name('users.impersonation.stop');
    Route::post('users/settings/impersonation', [ImpersonationController::class, 'update'])->middleware([UserAccessMiddleware::class, 'throttle:5,1,laravelusers-settings-impersonation'])->name('users.settings.impersonation');
});

Route::middleware('web')->group(function () {
    Route::put('users/settings/accounts', [AccountAccessController::class, 'save'])->middleware(['auth', 'throttle:10,1,laravelusers-settings-accounts'])->name('users.settings.accounts');
    Route::get('users/settings', [UsersManagementController::class, 'settings'])->middleware('auth')->name('users.settings');
    Route::put('users/settings/emails', [EmailTemplatesController::class, 'save'])->middleware(['auth', 'throttle:10,1,laravelusers-settings-emails'])->name('users.settings.emails');
    Route::put('users/settings/cleanup', [CleanupSettingsController::class, 'save'])->middleware(['auth', 'throttle:10,1,laravelusers-settings-cleanup'])->name('users.settings.cleanup');
    Route::put('users/settings', [UsersManagementController::class, 'updateSettings'])->middleware(['auth', 'throttle:10,1,laravelusers-settings-write'])->name('users.settings.update');
    Route::post('users/settings/packages', [PackageSettingsController::class, 'store'])->middleware(['auth', 'throttle:3,1,laravelusers-packages-write'])->name('users.settings.packages');
    Route::post('users/settings/packages/verify', [PackageSettingsController::class, 'verify'])->middleware(['auth', 'throttle:30,1,laravelusers-packages-verify'])->name('users.settings.packages.verify');
    Route::get('users/settings/packages/{id}', [PackageSettingsController::class, 'status'])->middleware(['auth', 'throttle:60,1,laravelusers-packages-status'])->where('id', '[a-f0-9-]{36}')->name('users.settings.packages.status');
    Route::post('users/email/preview', [UsersManagementController::class, 'previewEmail'])->middleware(['auth', 'throttle:'.config('laravelusers.emails.throttle', '10,1')])->name('users.email.preview');
    Route::post('users/email', [UsersManagementController::class, 'email'])->middleware(['auth', 'throttle:'.config('laravelusers.emails.throttle', '10,1')])->name('users.email');
    Route::post('users/bulk', [UsersManagementController::class, 'bulk'])->name('users.bulk');
    Route::get('users/deleted', [UsersManagementController::class, 'deleted'])->name('users.deleted');
    Route::get('users/deleted/{id}/edit', [UsersManagementController::class, 'editDeleted'])->name('users.deleted.edit');
    Route::put('users/deleted/{id}', [UsersManagementController::class, 'updateDeleted'])->name('users.deleted.update');
    Route::post('users/{id}/restore', [UsersManagementController::class, 'restore'])->name('users.restore');
    Route::delete('users/{id}/force', [UsersManagementController::class, 'forceDestroy'])->name('users.force-destroy');
    Route::delete('users/{user}', [UsersManagementController::class, 'destroyWithEmail'])->name('user.destroy');
    Route::resource('users', UsersManagementController::class)->except('destroy')
        ->names([
            'index'   => 'users',
            'destroy' => 'user.destroy',
        ]);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('search-users', [UsersManagementController::class, 'search'])->name('search-users');
});
