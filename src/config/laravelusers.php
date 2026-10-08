<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel-users setting
    |--------------------------------------------------------------------------
    */

    // Frontend choices are opt-in; composer update never changes these settings.
    'frontend' => env('LARAVEL_USERS_FRONTEND', 'bootstrap4'),
    'theme'    => env('LARAVEL_USERS_THEME', 'light'),
    // Enable the light, dark, and system icon button; false keeps the configured theme.
    'themeToggle'      => env('LARAVEL_USERS_THEME_TOGGLE', false),
    'bootstrap5CssCdn' => env('LARAVEL_USERS_BOOTSTRAP5_CSS_CDN', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'),

    // Hide package navigation when the parent layout provides its own dashboard header.
    'showHeader'   => env('LARAVEL_USERS_SHOW_HEADER', true),
    'showLogout'   => env('LARAVEL_USERS_SHOW_LOGOUT', true),
    'headerView'   => env('LARAVEL_USERS_HEADER_VIEW', null),
    'footerView'   => env('LARAVEL_USERS_FOOTER_VIEW', null),
    'fullWidth'    => env('LARAVEL_USERS_FULL_WIDTH', false),
    'iconsEnabled' => env('LARAVEL_USERS_ICONS_ENABLED', true),

    'tableButtonsIconOnly' => env('LARAVEL_USERS_TABLE_BUTTONS_ICON_ONLY', false),
    'responsiveButtons'    => env('LARAVEL_USERS_RESPONSIVE_BUTTONS', true),

    'localizeDates'   => env('LARAVEL_USERS_LOCALIZE_DATES', true),
    'dateStyle'       => env('LARAVEL_USERS_DATE_STYLE', 'short'),
    'timeStyle'       => env('LARAVEL_USERS_TIME_STYLE', 'short'),
    'displayTimezone' => env('LARAVEL_USERS_DISPLAY_TIMEZONE', null),

    'bulkActions'      => env('LARAVEL_USERS_BULK_ACTIONS', false),
    'bulkLimit'        => env('LARAVEL_USERS_BULK_LIMIT', 100),
    'responsiveTable'  => env('LARAVEL_USERS_RESPONSIVE_TABLE', false),
    'columnVisibility' => env('LARAVEL_USERS_COLUMN_VISIBILITY', false),

    // Login tracking requires the separately published activity migration.
    // Online status uses the host cache and does not require database changes.
    'activity' => [
        'login'          => env('LARAVEL_USERS_ACTIVITY_LOGIN', false),
        'online'         => env('LARAVEL_USERS_ACTIVITY_ONLINE', false),
        'guard'          => env('LARAVEL_USERS_ACTIVITY_GUARD', 'web'),
        'connection'     => env('LARAVEL_USERS_ACTIVITY_CONNECTION', null),
        'cache_store'    => env('LARAVEL_USERS_ACTIVITY_CACHE_STORE', null),
        'online_seconds' => env('LARAVEL_USERS_ACTIVITY_ONLINE_SECONDS', 300),
    ],

    // Avatars are optional; initials do not contact an external service.
    'avatar' => [
        'enabled'   => env('LARAVEL_USERS_AVATAR_ENABLED', false),
        'source'    => env('LARAVEL_USERS_AVATAR_SOURCE', 'initials'),
        'attribute' => env('LARAVEL_USERS_AVATAR_ATTRIBUTE', 'avatar'),
        'fallback'  => env('LARAVEL_USERS_AVATAR_FALLBACK', 'icon'),
        'size'      => env('LARAVEL_USERS_AVATAR_SIZE', 40),
    ],

    // Welcome emails are sent only when selected on the create form.
    'welcome' => [
        'enabled'              => env('LARAVEL_USERS_WELCOME_ENABLED', true),
        'force_password_reset' => env('LARAVEL_USERS_WELCOME_FORCE_PASSWORD_RESET', true),
        'password_broker'      => env('LARAVEL_USERS_WELCOME_PASSWORD_BROKER', null),
    ],

    // The parent blade file
    'laravelUsersBladeExtended' => env('LARAVEL_USERS_LARAVEL_USERS_BLADE_EXTENDED', 'laravelusers::layouts.app'), // 'layouts.app'

    // Enable `auth` middleware
    'authEnabled' => env('LARAVEL_USERS_AUTH_ENABLED', true),

    // Enable Optional Roles Middleware on the users assignments
    'rolesEnabled' => env('LARAVEL_USERS_ROLES_ENABLED', false),

    /*
     | Enable Roles Middlware on the usability of this package.
     | This requires the middleware from the roles package to be registered in `App\Http\Kernel.php`
     | An Example: of roles middleware entry in protected `$routeMiddleware` array would be:
     | 'role' => \jeremykenedy\LaravelRoles\Middleware\VerifyRole::class,
     */

    'rolesMiddlwareEnabled' => env('LARAVEL_USERS_ROLES_MIDDLWARE_ENABLED', true),

    // Optional Roles Middleware
    'rolesMiddlware' => env('LARAVEL_USERS_ROLES_MIDDLWARE', 'role:admin'),

    // Optional Role Model
    'roleModel' => env('LARAVEL_USERS_ROLE_MODEL', 'jeremykenedy\LaravelRoles\Models\Role'),

    // Requires SoftDeletes and a deleted_at column on the configured user model.
    'softDeletedEnabled' => env('LARAVEL_USERS_SOFT_DELETED_ENABLED', false),

    // Laravel Default User Model
    'defaultUserModel' => env('LARAVEL_USERS_DEFAULT_USER_MODEL', 'App\Models\User'),

    // Use the provided blade templates or extend to your own templates.
    'showUsersBlade'          => env('LARAVEL_USERS_SHOW_USERS_BLADE', 'laravelusers::usersmanagement.show-users'),
    'createUserBlade'         => env('LARAVEL_USERS_CREATE_USER_BLADE', 'laravelusers::usersmanagement.create-user'),
    'showIndividualUserBlade' => env('LARAVEL_USERS_SHOW_INDIVIDUAL_USER_BLADE', 'laravelusers::usersmanagement.show-user'),
    'editIndividualUserBlade' => env('LARAVEL_USERS_EDIT_INDIVIDUAL_USER_BLADE', 'laravelusers::usersmanagement.edit-user'),

    'showDeletedUsersBlade' => env('LARAVEL_USERS_SHOW_DELETED_USERS_BLADE', 'laravelusers::usersmanagement.deleted-users'),

    // Use Package Bootstrap Flash Alerts
    'enablePackageBootstapAlerts' => env('LARAVEL_USERS_ENABLE_PACKAGE_BOOTSTAP_ALERTS', true),

    // Users List Pagination
    'enablePagination' => env('LARAVEL_USERS_ENABLE_PAGINATION', true),
    'paginateListSize' => env('LARAVEL_USERS_PAGINATE_LIST_SIZE', 25),

    // Enable Search Users- Uses jQuery Ajax
    'enableSearchUsers' => env('LARAVEL_USERS_ENABLE_SEARCH_USERS', true),

    // New table controls are optional and filter or sort the rows currently displayed.
    'searchDebounceEnabled'      => env('LARAVEL_USERS_SEARCH_DEBOUNCE_ENABLED', true),
    'searchDebounce'             => env('LARAVEL_USERS_SEARCH_DEBOUNCE', 2000),
    'tableSorting'               => env('LARAVEL_USERS_TABLE_SORTING', false),
    'tableFiltering'             => env('LARAVEL_USERS_TABLE_FILTERING', false),
    'emailLinks'                 => env('LARAVEL_USERS_EMAIL_LINKS', true),
    'showCreatedColumn'          => env('LARAVEL_USERS_SHOW_CREATED_COLUMN', true),
    'showUpdatedColumn'          => env('LARAVEL_USERS_SHOW_UPDATED_COLUMN', true),
    'showOnlineColumn'           => env('LARAVEL_USERS_SHOW_ONLINE_COLUMN', true),
    'showLastLoginDetailsColumn' => env('LARAVEL_USERS_SHOW_LAST_LOGIN_DETAILS_COLUMN', true),
    'showProfileAvatar'          => env('LARAVEL_USERS_SHOW_PROFILE_AVATAR', true),
    'showLastLoginColumn'        => env('LARAVEL_USERS_SHOW_LAST_LOGIN_COLUMN', true),
    'showUserCount'              => env('LARAVEL_USERS_SHOW_USER_COUNT', true),
    'confirmDelete'              => env('LARAVEL_USERS_CONFIRM_DELETE', true),
    'confirmSave'                => env('LARAVEL_USERS_CONFIRM_SAVE', true),

    // Users List JS DataTables - not recommended use with pagination
    'enabledDatatablesJs'    => env('LARAVEL_USERS_ENABLED_DATATABLES_JS', false),
    'datatablesJsStartCount' => env('LARAVEL_USERS_DATATABLES_JS_START_COUNT', 25),
    'datatablesCssCDN'       => env('LARAVEL_USERS_DATATABLES_CSS_CDN', 'https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css'),
    'datatablesJsCDN'        => env('LARAVEL_USERS_DATATABLES_JS_CDN', 'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js'),
    'datatablesJsPresetCDN'  => env('LARAVEL_USERS_DATATABLES_JS_PRESET_CDN', 'https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js'),

    // Bootstrap Tooltips
    'tooltipsEnabled'            => env('LARAVEL_USERS_TOOLTIPS_ENABLED', true),
    'enableBootstrapPopperJsCdn' => env('LARAVEL_USERS_ENABLE_BOOTSTRAP_POPPER_JS_CDN', true),
    'bootstrapPopperJsCdn'       => env('LARAVEL_USERS_BOOTSTRAP_POPPER_JS_CDN', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js'),

    // Icons
    'fontAwesomeEnabled' => env('LARAVEL_USERS_FONT_AWESOME_ENABLED', true),
    'fontAwesomeCdn'     => env('LARAVEL_USERS_FONT_AWESOME_CDN', 'https://use.fontawesome.com/releases/v5.0.6/css/all.css'),

    // Extended blade options for packages app.blade.php
    'enableBootstrapCssCdn' => env('LARAVEL_USERS_ENABLE_BOOTSTRAP_CSS_CDN', true),
    'bootstrapCssCdn'       => env('LARAVEL_USERS_BOOTSTRAP_CSS_CDN', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css'),

    'enableAppCss'     => env('LARAVEL_USERS_ENABLE_APP_CSS', true),
    'appCssPublicFile' => env('LARAVEL_USERS_APP_CSS_PUBLIC_FILE', 'css/app.css'),

    'enableBootstrapJsCdn' => env('LARAVEL_USERS_ENABLE_BOOTSTRAP_JS_CDN', true),
    'bootstrapJsCdn'       => env('LARAVEL_USERS_BOOTSTRAP_JS_CDN', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js'),

    'enableAppJs'     => env('LARAVEL_USERS_ENABLE_APP_JS', true),
    'appJsPublicFile' => env('LARAVEL_USERS_APP_JS_PUBLIC_FILE', 'js/app.js'),

    'enablejQueryCdn' => env('LARAVEL_USERS_ENABLE_JQUERY_CDN', true),
    'jQueryCdn'       => env('LARAVEL_USERS_JQUERY_CDN', 'https://code.jquery.com/jquery-3.3.1.min.js'),

];
