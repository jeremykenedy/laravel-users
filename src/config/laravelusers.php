<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel-users setting
    |--------------------------------------------------------------------------
    */

    // The parent blade file
    'laravelUsersBladeExtended'     => 'laravelusers::layouts.template',     // 'layouts.app'

    // Enable `auth` middleware
    'authEnabled'                   => true,

    // Enable Optional Roles Middleware
    'rolesEnabled'                  => false,

    // Optional Roles Middleware
    'rolesMiddlware'                => 'role:admin',

    // Optional Role Model
    'roleModel'                     => 'Spatie\Permission\Models\Role',

    // Enable Soft Deletes - Not yet setup - on the roadmap.
    'softDeletedEnabled'            => true,

    // Laravel Default User Model
    'defaultUserModel'              => 'App\User',

    // Users List Pagination
    'enablePagination'              => true,
    'paginateListSize'              => 25,

    // Users List JS DataTables - not recommended use with pagination
    'enabledDatatablesJs'           => true,
    'datatablesJsStartCount'        => 25,
    'datatablesCssCDN'              => 'https://cdn.datatables.net/2.2.1/css/dataTables.bootstrap.min.css',
    'datatablesJsCDN'               => 'https://cdn.datatables.net/2.2.1/js/jquery.dataTables.min.js',
    'datatablesJsPresetCDN'         => 'https://cdn.datatables.net/2.2.1/js/dataTables.bootstrap.min.js',

    // Bootstrap Tooltips
    'tooltipsEnabled'               => true,

    // Icons
    'fontAwesomeEnabled'            => true,
    'fontAwesomeCdn'                => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',

    // Extended blade options for packages app.blade.php
    'enableBootstrapCssCdn'         => true,
    'bootstrapCssCdn'               => 'https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css',

    'enableAppCss'                  => true,
    'appCssPublicFile'              => 'css/app.css',

    'enableBootstrapJsCdn'          => true,
    'bootstrapJsCdn'                => 'https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css',

    'enableAppJs'                   => true,
    'appJsPublicFile'               => 'js/app.js',

    'enablejQueryCdn'               => true,
    'jQueryCdn'                     => 'http://code.jquery.com/jquery-3.3.1.min.js',

];
