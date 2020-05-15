![Laravel Users](https://github-project-images.s3-us-west-2.amazonaws.com/laravel-blocker/laravel-users-logo.png)

# Laravel Users | A Laravel Users CRUD Management [Package](https://packagist.org/packages/jeremykenedy/laravel-users)
A Users Management CRUD [Package](https://packagist.org/packages/jeremykenedy/laravel-users) that includes all necessary routes, views, models, and controllers for a user management dashboard and associated pages for managing Laravels built in user scaffolding.
Easily start creating, updating, editing, and deleting users in minutes with minimal setup required; Easily search all users, helpful for large user bases.

[![Latest Stable Version](https://poser.pugx.org/jeremykenedy/laravel-users/v/stable.svg)](https://packagist.org/packages/jeremykenedy/laravel-users)
[![Total Downloads](https://poser.pugx.org/jeremykenedy/laravel-users/d/total.svg)](https://packagist.org/packages/jeremykenedy/laravel-users)
[![Travis-CI Build](https://travis-ci.org/jeremykenedy/laravel-users.svg?branch=master)](https://travis-ci.org/jeremykenedy/laravel-users)
[![StyleCI](https://styleci.io/repos/83162309/shield?branch=master)](https://styleci.io/repos/83162309)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jeremykenedy/laravel-users/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jeremykenedy/laravel-users/?branch=master)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
<a href="https://www.patreon.com/bePatron?u=10119959" title="Become a Patreon">
    <img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" alt="Become a Patreon" width="85px" > 
</a>

#### Table of contents
- [About](#about)
- [Features](#features)
- [Requirements](#requirements)
- [Integrations](#integrations)
- [Installation Instructions](#installation-instructions)
- [Configuration](#configuration)
- [Routes](#routes)
- [Screenshots](#screenshots)
- [File Tree](#file-tree)
- [Opening an Issue](#opening-an-issue)
- [License](#license)

### Features
| Laravel Users Features  |
| :------------ |
|Full CRUD of Laravel Users|
|Works with built in [auth scaffolding](https://laravel.com/docs/5.7/authentication)|
|Works with various [Roles/ACL Packages](https://github.com/jeremykenedy/laravel-roles)|
|Uses [Language localization](https://laravel.com/docs/5.7/localization) File System|
|Uses [font awesome](https://fontawesome.com/icons), cdn can be optionally called in config|
|Can use built in [pagination](https://laravel.com/docs/5.7/pagination) and/or [datatables.js](https://datatables.net/)|
|Can search all users by name, id, or email|
|Lots of [configuration](#configuration) options|


### Requirements
* [Laravel 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 6, or 7+](https://laravel.com/docs/installation)

### Integrations
Laravel users can work out the box with or without the following roles packages:
* [jeremykenedy/laravel-roles](https://github.com/jeremykenedy/laravel-roles)
* [Zizaco/entrust](https://github.com/Zizaco/entrust)
* [romanbican/roles](https://github.com/romanbican/roles)
* [ultraware/roles](https://github.com/ultraware/roles)

### Installation Instructions
1. From your projects root folder in terminal run:

    Laravel 5.6, 5.7, and 5.8+ use:

    ```
        composer require jeremykenedy/laravel-users
    ```

    Laravel 5.5 use:

    ```
        composer require jeremykenedy/laravel-users:2.0.2
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
* Laravel 5.5, 5.6, and 5.7+
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

4. Publish the package config and language files by running the following from your projects root folder:

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
    'laravelUsersBladeExtended'     => 'laravelusers::layouts.app', // 'layouts.app'

    // Enable `auth` middleware
    'authEnabled'                   => true,

    // Enable Optional Roles Middleware on the users assignments
    'rolesEnabled'                  => false,

    /*
     | Enable Roles Middlware on the usability of this package.
     | This requires the middleware from the roles package to be registered in `App\Http\Kernel.php`
     | An Example: of roles middleware entry in protected `$routeMiddleware` array would be:
     | 'role' => \jeremykenedy\LaravelRoles\Middleware\VerifyRole::class,
     */

    'rolesMiddlwareEnabled'         => true,

    // Optional Roles Middleware
    'rolesMiddlware'                => 'role:admin',

    // Optional Role Model
    'roleModel'                     => 'jeremykenedy\LaravelRoles\Models\Role',

    // Enable Soft Deletes - Not yet setup - on the roadmap.
    'softDeletedEnabled'            => false,

    // Laravel Default User Model
    'defaultUserModel'              => 'App\User',

    // Use the provided blade templates or extend to your own templates.
    'showUsersBlade'                => 'laravelusers::usersmanagement.show-users',
    'createUserBlade'               => 'laravelusers::usersmanagement.create-user',
    'showIndividualUserBlade'       => 'laravelusers::usersmanagement.show-user',
    'editIndividualUserBlade'       => 'laravelusers::usersmanagement.edit-user',

    // Use Package Bootstrap Flash Alerts
    'enablePackageBootstapAlerts'   => true,

    // Users List Pagination
    'enablePagination'              => true,
    'paginateListSize'              => 25,

    // Enable Search Users- Uses jQuery Ajax
    'enableSearchUsers'             => true,

    // Users List JS DataTables - not recommended use with pagination
    'enabledDatatablesJs'           => false,
    'datatablesJsStartCount'        => 25,
    'datatablesCssCDN'              => 'https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css',
    'datatablesJsCDN'               => 'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
    'datatablesJsPresetCDN'         => 'https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js',

    // Bootstrap Tooltips
    'tooltipsEnabled'               => true,
    'enableBootstrapPopperJsCdn'    => true,
    'bootstrapPopperJsCdn'          => 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js',

    // Icons
    'fontAwesomeEnabled'            => true,
    'fontAwesomeCdn'                => 'https://use.fontawesome.com/releases/v5.0.6/css/all.css',

    // Extended blade options for packages app.blade.php
    'enableBootstrapCssCdn'         => true,
    'bootstrapCssCdn'               => 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css',

    'enableAppCss'                  => true,
    'appCssPublicFile'              => 'css/app.css',

    'enableBootstrapJsCdn'          => true,
    'bootstrapJsCdn'                => 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js',

    'enableAppJs'                   => true,
    'appJsPublicFile'               => 'js/app.js',

    'enablejQueryCdn'               => true,
    'jQueryCdn'                     => 'https://code.jquery.com/jquery-3.3.1.min.js',
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
├── .env.travis
├── .gitignore
├── .travis.yml
├── LICENSE
├── composer.json
├── phpunit.xml
├── readme.md
└── src
    ├── App
    │   └── Http
    │       └── Controllers
    │           └── UsersManagementController.php
    ├── LaravelUsersFacade.php
    ├── LaravelUsersServiceProvider.php
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
    │       │   ├── bs-visibility-css.blade.php
    │       │   ├── form-status.blade.php
    │       │   ├── search-users-form.blade.php
    │       │   └── styles.blade.php
    │       ├── scripts
    │       │   ├── check-changed.blade.php
    │       │   ├── datatables.blade.php
    │       │   ├── delete-modal-script.blade.php
    │       │   ├── save-modal-script.blade.php
    │       │   ├── search-users.blade.php
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
* File tree generated using command `tree -a -I '.git|node_modules|vendor|storage|tests'`

### Opening an Issue
Before opening an issue there are a couple of considerations:
* A **star** on this project shows support and is way to say thank you to all the contributors. If you open an issue without a star, *your issue may be closed without consideration.* Thank you for understanding and the support. You are all awesome!
* **Read the instructions** and make sure all steps were *followed correctly*.
* **Check** that the issue is not *specific to your development environment* setup.
* **Provide** *duplication steps*.
* **Attempt to look into the issue**, and if you *have a solution, make a pull request*.
* **Show that you have made an attempt** to *look into the issue*.
* **Check** to see if the issue you are *reporting is a duplicate* of a previous reported issue.
* **Following these instructions show me that you have tried.**
* If you have a questions send me an email to jeremykenedy@gmail.com
* Need some help, I can do my best on Slack: https://opensourcehelpgroup.slack.com
* Please be considerate that this is an open source project that I provide to the community for FREE when opening an issue. 

### License
Laravel Users | A Laravel Users Management Package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT). Enjoy!
