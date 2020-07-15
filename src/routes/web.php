<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

// APP Routes Below
Route::group(['middleware' => 'web', 'namespace' => 'fhddev\laravelusers\app\Http\Controllers', 'prefix' => config('laravelusers.routePrefix')], function () {
    Route::resource('users', 'UsersManagementController', [
        'names' => [
            'index'   => 'users',
            'destroy' => 'user.destroy',
        ],
    ]);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('search-users', 'fhddev\laravelusers\app\Http\Controllers\UsersManagementController@search')->name('search-users');
});
