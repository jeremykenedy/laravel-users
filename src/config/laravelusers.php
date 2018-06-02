<?php

return [

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

];
