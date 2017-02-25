# Laravel-Users | A Laravel Users Management Package | v0.0.0

## Introduction

A Users Management Package that includes all necessary routes, views, models, and controllers for a user management dashboard and associated pages for managing Laravels built in user scaffolding.
Built for Laravel 5.2, 5.3, and 5.4+.

## Requirements

* [Laravel 5.2, 5.3, or 5.4 or newer](https://laravel.com/docs/installation)

   Example new project creation command:

   ```laravel new laravel-users-example```

* [Laravel Authentication Scaffolding](https://laravel.com/docs/authentication)

   Authentication installation command:

   ```php artisan make:auth```


## Installation

1. From your projects root folder in terminal run:

   ```
      composer require jeremykenedy/laravel-users
   ```

2. Register the package with laravel in `config/app.php` under `providers` with the following:

   ```
      Collective\Html\HtmlServiceProvider::class,

      jeremykenedy\laravelusers\LaravelUsersServiceProvider::class,

   ```


3. Register the dependencies aliases with laravel in `config/app.php` section under `aliases` with the following:

   ```
      'Form' => Collective\Html\FormFacade::class,
      'Html' => Collective\Html\HtmlFacade::class,
   ```

4. Publish the packages assets by running the following from your projects root folder:

   ```
      php artisan vendor:publish
   ```

## Routes

* ```/users```
* ```/users/{id}```
* ```/users/create```
* ```/users/{id}/edit```

## Required Packages
(included in this package)

* [laravelcollective/html](https://packagist.org/packages/laravelcollective/html)

## Screenshots

![Show Users]()
![Show User]()
![Edit User]()
![Edit User Password]()
![Create User]()

![Create User Modal]()
![Edit User Modal]()
![Delete User Modal]()

## License

Laravel-Users | A Laravel Users Management Package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)


