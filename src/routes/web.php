<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use jeremykenedy\laravelusers\App\Http\Controllers\UsersManagementController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

// APP Routes Below
Route::middleware('web')->group(function () {
    Route::post('users/bulk', [UsersManagementController::class, 'bulk'])->name('users.bulk');
    Route::get('users/deleted', [UsersManagementController::class, 'deleted'])->name('users.deleted');
    Route::post('users/{id}/restore', [UsersManagementController::class, 'restore'])->name('users.restore');
    Route::delete('users/{id}/force', [UsersManagementController::class, 'forceDestroy'])->name('users.force-destroy');
    Route::resource('users', UsersManagementController::class)
        ->names([
            'index'   => 'users',
            'destroy' => 'user.destroy',
        ]);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('search-users', [UsersManagementController::class, 'search'])->name('search-users');
});
