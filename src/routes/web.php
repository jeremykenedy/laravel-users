<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

Route::resource('users', 'UsersManagementController', [
	'names' => [
	    'index' => 'users',
	    'destroy' => 'user.destroy'
	]
]);