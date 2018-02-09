<?php

return [

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

];
