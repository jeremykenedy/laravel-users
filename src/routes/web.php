<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

// APP Routes Below
Route::group(['middleware' => 'web', 'namespace' => 'jeremykenedy\laravelusers\app\Http\Controllers'], function () {
    Route::resource('users', 'UsersManagementController', [
        'names' => [
            'index'   => 'users',
            'destroy' => 'user.destroy',
        ],
    ]);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('search-users', 'jeremykenedy\laravelusers\app\Http\Controllers\UsersManagementController@search')->name('search-users');
});
