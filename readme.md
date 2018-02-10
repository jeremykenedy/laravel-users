# Laravel-Users | A Laravel Users CRUD Management [Package](https://packagist.org/packages/jeremykenedy/laravel-users)

[![Latest Stable Version](https://poser.pugx.org/jeremykenedy/laravel-users/v/stable.svg)](https://packagist.org/packages/jeremykenedy/laravel-users)
[![Total Downloads](https://poser.pugx.org/jeremykenedy/laravel-users/d/total.svg)](https://packagist.org/packages/jeremykenedy/laravel-users)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

- [About](#about)
- [Features](#features)
- [Requirements](#requirements)
- [Installation Instructions](#installation-instructions)
- [Configuration](#configuration)
- [Routes](#routes)
- [Screenshots](#screenshots)
- [File Tree](#file-tree)
- [License](#license)

### About
A Users Management CRUD [Package](https://packagist.org/packages/jeremykenedy/laravel-users) that includes all necessary routes, views, models, and controllers for a user management dashboard and associated pages for managing Laravels built in user scaffolding.
Easily start creating, updating, editing, and deleting users in minutes with minimal setup required.
Built for Laravel 5.2, 5.3, 5.4, and 5.5+. This package is easily configurable and customizable.

Laravel users can work out the box with or without the following roles packages:
* [jeremykenedy/laravel-roles](https://github.com/jeremykenedy/laravel-roles)
* [spatie/laravel-permission](https://github.com/spatie/laravel-permission)
* [Zizaco/entrust](https://github.com/Zizaco/entrust)
* [romanbican/roles](https://github.com/romanbican/roles)
* [ultraware/roles](https://github.com/ultraware/roles)

### Features
| Laravel Users Features  |
| :------------ |
|Full CRUD of Laravel Users|
|Works with built in [auth scaffolding](https://laravel.com/docs/5.5/authentication)|
|Works with various [Roles/ACL Packages](https://github.com/jeremykenedy/laravel-roles)|
|Uses [Language localization](https://laravel.com/docs/5.5/localization) File System|
|Uses [font awesome](https://fontawesome.com/icons), cdn can be optionally called in config|
|Can use built in [pagination](https://laravel.com/docs/5.5/pagination) and/or [datatables.js](https://datatables.net/)|
|Lots of [configuration](#configuration) options|

### Requirements
* [Laravel 5.2, 5.3, 5.4, and 5.5+](https://laravel.com/docs/installation)

### Installation Instructions
1. From your projects root folder in terminal run:

    Laravel 5.5+ use:

    ```
        composer require jeremykenedy/laravel-users
    ```

    Laravel 5.4 use:

    ```
        composer require jeremykenedy/laravel-users:1.4.0
    ```

    Laravel 5.3 use:

    ```
        composer require jeremykenedy/laravel-users:1.3.0
    ```

    Laravel 5.2 use:
    ```
        composer require jeremykenedy/laravel-users:1.2.0
    ```

2. Register Package
* Laravel 5.5 and up
Uses package auto discovery feature, no need to edit the `config/app.php` file.

* Laravel 5.4 and below
Register the package with laravel in `config/app.php` under `providers` with the following:

   ```
      Collective\Html\HtmlServiceProvider::class,
      jeremykenedy\laravelusers\LaravelUsersServiceProvider::class,
   ```

3. Register the dependencies aliases
* Laravel 5.5 and up
Uses package auto discovery feature, no need to edit the `config/app.php` file.

* Laravel 5.4 and below
In `config/app.php` section under `aliases` with the following:

    ```
        'Form' => Collective\Html\FormFacade::class,
        'Html' => Collective\Html\HtmlFacade::class,
    ```

4. Publish the packages language files by running the following from your projects root folder:

    ```
        php artisan vendor:publish --tag=laravelusers
    ```

### Configuration
Laravel Users can be configured directly in [`/config/laravelusers.php`](https://github.com/jeremykenedy/laravel-users/blob/master/src/config/laravelusers.php) once you publish the assets.

```php
    /*
    |--------------------------------------------------------------------------
    | Laravel-users setting
    |--------------------------------------------------------------------------
    */

    // The parent blade file
    'laravelUsersBladeExtended'     => 'laravelusers::layouts.app',     // 'layouts.app'

    // Enable `auth` middleware
    'authEnabled'                   => true,

    // Enable Optional Roles Middleware
    'rolesEnabled'                  => true,

    // Optional Roles Middleware
    'rolesMiddlware'                => 'role:admin',

    // Optional Role Model
    'roleModel'                     => 'jeremykenedy\LaravelRoles\Models\Role',

    // Enable Soft Deletes
    'softDeletedEnabled'            => false,

    // Laravel Default User Model
    'defaultUserModel'              => 'App\User',

    // Users List Pagination
    'enablePagination'              => true,
    'paginateListSize'              => 25,

    // Users List JS DataTables - not recommended use with pagination
    'enabledDatatablesJs'           => false,
    'datatablesJsStartCount'        => 25,
    'datatablesCssCDN'              => 'https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css',
    'datatablesJsCDN'               => 'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
    'datatablesJsPresetCDN'         => 'https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js',

    // Bootstrap Tooltips
    'tooltipsEnabled'               => true,

    // Icons
    'fontAwesomeEnabled'            => true,
    'fontAwesomeCdn'                => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',

    // Extended blade options for packages app.blade.php
    'enableBootstrapCssCdn'         => true,
    'bootstrapCssCdn'               => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',

    'enableAppCss'                  => true,
    'appCssPublicFile'              => 'css/app.css',

    'enableBootstrapJsCdn'          => true,
    'bootstrapJsCdn'                => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js',

    'enableAppJs'                   => false,
    'appJsPublicFile'               => 'js/app.js',

    'enablejQueryCdn'               => true,
    'jQueryCdn'                     => 'http://code.jquery.com/jquery-3.3.1.min.js',
```

### Routes
* ```/users```
* ```/users/{id}```
* ```/users/create```
* ```/users/{id}/edit```

###### Routes In-depth
| Method    | URI                    | Name             | Action                                                                            | Middleware  |
| :-------- | :--------------------- | :--------------- | :-------------------------------------------------------------------------------- | :---------- |
| GET/HEAD  | users                  | users            | jeremykenedy\laravelusers\app\Http\Controllers\UsersManagementController@index    | web,auth    |
| POST      | users                  | users.store      | jeremykenedy\laravelusers\app\Http\Controllers\UsersManagementController@store    | web,auth    |
| GET/HEAD  | users/create           | users.create     | jeremykenedy\laravelusers\app\Http\Controllers\UsersManagementController@create   | web,auth    |
| GET/HEAD  | users/{user}           | users.show       | jeremykenedy\laravelusers\app\Http\Controllers\UsersManagementController@show     | web,auth    |
| DELETE    | users/{user}           | user.destroy     | jeremykenedy\laravelusers\app\Http\Controllers\UsersManagementController@destroy  | web,auth    |
| PUT/PATCH | users/{user}           | users.update     | jeremykenedy\laravelusers\app\Http\Controllers\UsersManagementController@update   | web,auth    |
| GET/HEAD  | users/{user}/edit      | users.edit       | jeremykenedy\laravelusers\app\Http\Controllers\UsersManagementController@edit     | web,auth    |

### Required Packages
(included in this package)

* [laravelcollective/html](https://packagist.org/packages/laravelcollective/html)

### Screenshots

![Show Users](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/show-users.jpg)
![Show User](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/show-user.jpg)
![Edit User](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/edit-user.jpg)
![Edit User Password](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/edit-user-pw.jpg)
![Create User](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/create-user.jpg)
![Create User Modal](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/save-user-modal.jpg)
![Delete User Modal](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/delete-user-modal.jpg)
![Error Create](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/error-create.jpg)
![Error Update](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/error-update.jpg)
![Error Delete](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/error-delete.jpg)

### File Tree

```bash
laravel-users/
├── .gitignore
├── LICENSE
├── composer.json
├── readme.md
└── src
    ├── LaravelUsersServiceProvider.php
    ├── app
    │   └── Http
    │       └── Controllers
    │           └── UsersManagementController.php
    ├── config
    │   └── laravelusers.php
    ├── resources
    │   ├── lang
    │   │   └── en
    │   │       ├── app.php
    │   │       ├── forms.php
    │   │       ├── laravelusers.php
    │   │       └── modals.php
    │   └── views
    │       ├── layouts
    │       │   └── app.blade.php
    │       ├── modals
    │       │   ├── modal-delete.blade.php
    │       │   └── modal-save.blade.php
    │       ├── partials
    │       │   ├── form-status.blade.php
    │       │   └── styles.blade.php
    │       ├── scripts
    │       │   ├── check-changed.blade.php
    │       │   ├── datatables.blade.php
    │       │   ├── delete-modal-script.blade.php
    │       │   ├── save-modal-script.blade.php
    │       │   ├── toggleText.blade.php
    │       │   └── tooltips.blade.php
    │       └── usersmanagement
    │           ├── create-user.blade.php
    │           ├── edit-user.blade.php
    │           ├── show-user.blade.php
    │           └── show-users.blade.php
    └── routes
        └── web.php
```

* Tree command can be installed using brew: `brew install tree`
* File tree generated using command `tree -a -I '.git|node_modules|vendor|storage|tests`

### License
Laravel-Users | A Laravel Users Management Package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT). Enjoy!
