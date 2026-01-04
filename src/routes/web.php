<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use jeremykenedy\laravelusers\App\Http\Controllers\UsersManagementController;

/*
||--------------------------------------------------------------------------
|| Web Routes
||--------------------------------------------------------------------------
||
*/

// APP Routes Below
Route::middleware('web')->group(function () {
    Route::resource('users', UsersManagementController::class)
        ->names([
            'index'   => 'users',
            'destroy' => 'user.destroy',
        ]);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('search-users', [UsersManagementController::class, 'search'])->name('search-users');
});
