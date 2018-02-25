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

// Route::group(['middleware' => ['web', 'auth'],'namespace' => 'jeremykenedy\laravelusers\app\Http\Controllers'], function() {

// //    Route::post('search-users', ['uses' => 'UsersManagementController@search'])->name('search-users');

//     // Route::post('search-users', 'UsersManagementController@search')->name('search-users');
//     Route::post('search-users', function () {
//         return 'jeremy';
//     })->name('search-users');

// });
