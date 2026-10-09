<?php

$access = json_decode((string) env('LARAVEL_USERS_ACCESS', '{}'), true);
$middlewareGates = json_decode((string) env('LARAVEL_USERS_AUTHORIZATION_MIDDLEWARE_GATES', '{}'), true);

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel-users setting
    |--------------------------------------------------------------------------
    */

    // Frontend choices are opt-in; composer update never changes these settings.
    'frontend' => env('LARAVEL_USERS_FRONTEND', 'bootstrap4'),
    'runtime'  => env('LARAVEL_USERS_RUNTIME', 'blade'),
    'theme'    => env('LARAVEL_USERS_THEME', 'light'),
    // Enable the light, dark, and system icon button; false keeps the configured theme.
    'themeToggle'      => env('LARAVEL_USERS_THEME_TOGGLE', false),
    'bootstrap5CssCdn' => env('LARAVEL_USERS_BOOTSTRAP5_CSS_CDN', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'),

    // Hide package navigation when the parent layout provides its own dashboard header.
    'showHeader'      => env('LARAVEL_USERS_SHOW_HEADER', true),
    'showBreadcrumbs' => env('LARAVEL_USERS_SHOW_BREADCRUMBS', false),
    'showLogout'      => env('LARAVEL_USERS_SHOW_LOGOUT', true),
    'headerView'      => env('LARAVEL_USERS_HEADER_VIEW', null),
    'footerView'      => env('LARAVEL_USERS_FOOTER_VIEW', null),
    'fullWidth'       => env('LARAVEL_USERS_FULL_WIDTH', false),
    'iconsEnabled'    => env('LARAVEL_USERS_ICONS_ENABLED', true),

    'tableButtonsIconOnly' => env('LARAVEL_USERS_TABLE_BUTTONS_ICON_ONLY', false),
    'responsiveButtons'    => env('LARAVEL_USERS_RESPONSIVE_BUTTONS', false),

    'localizeDates'   => env('LARAVEL_USERS_LOCALIZE_DATES', false),
    'dateStyle'       => env('LARAVEL_USERS_DATE_STYLE', 'short'),
    'timeStyle'       => env('LARAVEL_USERS_TIME_STYLE', 'short'),
    'displayTimezone' => env('LARAVEL_USERS_DISPLAY_TIMEZONE', null),

    'bulkActions'      => env('LARAVEL_USERS_BULK_ACTIONS', false),
    'bulkLimit'        => env('LARAVEL_USERS_BULK_LIMIT', 100),
    'tableViewToggle'  => env('LARAVEL_USERS_TABLE_VIEW_TOGGLE', false),
    'responsiveTable'  => env('LARAVEL_USERS_RESPONSIVE_TABLE', false),
    'columnVisibility' => env('LARAVEL_USERS_COLUMN_VISIBILITY', false),

    'cardColumns' => [
        'mobile'  => env('LARAVEL_USERS_CARD_COLUMNS_MOBILE', 1),
        'tablet'  => env('LARAVEL_USERS_CARD_COLUMNS_TABLET', 2),
        'desktop' => env('LARAVEL_USERS_CARD_COLUMNS_DESKTOP', 3),
        'wide'    => env('LARAVEL_USERS_CARD_COLUMNS_WIDE', 4),
    ],

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
        'enabled'        => env('LARAVEL_USERS_AVATAR_ENABLED', false),
        'per_user'       => env('LARAVEL_USERS_AVATAR_PER_USER', false),
        'source'         => env('LARAVEL_USERS_AVATAR_SOURCE', 'initials'),
        'attribute'      => env('LARAVEL_USERS_AVATAR_ATTRIBUTE', 'avatar'),
        'fallback'       => env('LARAVEL_USERS_AVATAR_FALLBACK', 'icon'),
        'size'           => env('LARAVEL_USERS_AVATAR_SIZE', 40),
        'image_size'     => env('LARAVEL_USERS_AVATAR_IMAGE_SIZE', 256),
        'remote_enabled' => env('LARAVEL_USERS_AVATAR_REMOTE_ENABLED', true),
        'dicebear'       => [
            'driver' => env('LARAVEL_USERS_AVATAR_DICEBEAR_DRIVER', 'local'),
            'style'  => env('LARAVEL_USERS_AVATAR_DICEBEAR_STYLE', 'identicon'),
            'url'    => env('LARAVEL_USERS_AVATAR_DICEBEAR_URL', null),
        ],
        'ui_avatars' => [
            'driver'     => env('LARAVEL_USERS_AVATAR_UI_DRIVER', 'local'),
            'url'        => env('LARAVEL_USERS_AVATAR_UI_URL', 'https://ui-avatars.com/api/'),
            'background' => env('LARAVEL_USERS_AVATAR_UI_BACKGROUND', 'e7eef8'),
            'color'      => env('LARAVEL_USERS_AVATAR_UI_COLOR', '344760'),
        ],
    ],

    // Settings require an explicit opt-in, a migration and a host authorization gate.
    'settings' => [
        'enabled'    => env('LARAVEL_USERS_SETTINGS_ENABLED', false),
        'connection' => env('LARAVEL_USERS_SETTINGS_CONNECTION', null),
        'gate'       => env('LARAVEL_USERS_SETTINGS_GATE', 'manage-laravelusers-settings'),
        'packages'   => [
            'cache'         => env('LARAVEL_USERS_SETTINGS_PACKAGES_CACHE', null),
            'enabled'       => env('LARAVEL_USERS_SETTINGS_PACKAGES_ENABLED', false),
            'gate'          => env('LARAVEL_USERS_SETTINGS_PACKAGES_GATE', 'manage-laravelusers-packages'),
            'queue'         => env('LARAVEL_USERS_SETTINGS_PACKAGES_QUEUE', 'default'),
            'connection'    => env('LARAVEL_USERS_SETTINGS_PACKAGES_CONNECTION', null),
            'start_timeout' => env('LARAVEL_USERS_SETTINGS_PACKAGES_START_TIMEOUT', 120),
        ],
    ],
    'access' => is_array($access) ? $access : [],
    // Map custom middleware to actor-aware gates for impersonation and queued package changes.
    'authorization' => ['middleware_gates' => is_array($middlewareGates) ? $middlewareGates : []],
    'impersonation' => [
        'enabled'    => env('LARAVEL_USERS_IMPERSONATION_ENABLED', false),
        'timeout'    => env('LARAVEL_USERS_IMPERSONATION_TIMEOUT', 60),
        'middleware' => array_filter(array_map('trim', explode(',', env('LARAVEL_USERS_IMPERSONATION_MIDDLEWARE', '')))),
    ],
    'notifications' => [
        'driver'      => env('LARAVEL_USERS_NOTIFICATIONS_DRIVER', 'alert'),
        'dismissible' => env('LARAVEL_USERS_NOTIFICATIONS_DISMISSIBLE', true),
    ],
    'appearance' => [
        'per_user' => env('LARAVEL_USERS_APPEARANCE_PER_USER', false),
    ],
    'profileCardGradient'               => env('LARAVEL_USERS_PROFILE_CARD_GRADIENT', true),
    'profileCardGradientStrength'       => env('LARAVEL_USERS_PROFILE_CARD_GRADIENT_STRENGTH', 50),
    'profileCardGradientHighlightColor' => env('LARAVEL_USERS_PROFILE_CARD_GRADIENT_HIGHLIGHT_COLOR', '#ffffff'),
    'editCardGradient'                  => env('LARAVEL_USERS_EDIT_CARD_GRADIENT', true),
    'editCardGradientStrength'          => env('LARAVEL_USERS_EDIT_CARD_GRADIENT_STRENGTH', 50),
    'editCardGradientHighlightColor'    => env('LARAVEL_USERS_EDIT_CARD_GRADIENT_HIGHLIGHT_COLOR', '#ffffff'),
    // Null dark-mode settings inherit the existing light-mode appearance.
    'profileCardDarkColor'                  => env('LARAVEL_USERS_PROFILE_CARD_DARK_COLOR', null),
    'profileCardDarkGradient'               => env('LARAVEL_USERS_PROFILE_CARD_DARK_GRADIENT', null),
    'profileCardDarkGradientStrength'       => env('LARAVEL_USERS_PROFILE_CARD_DARK_GRADIENT_STRENGTH', null),
    'profileCardDarkGradientHighlightColor' => env('LARAVEL_USERS_PROFILE_CARD_DARK_GRADIENT_HIGHLIGHT_COLOR', null),
    'editCardDarkColor'                     => env('LARAVEL_USERS_EDIT_CARD_DARK_COLOR', null),
    'editCardDarkGradient'                  => env('LARAVEL_USERS_EDIT_CARD_DARK_GRADIENT', null),
    'editCardDarkGradientStrength'          => env('LARAVEL_USERS_EDIT_CARD_DARK_GRADIENT_STRENGTH', null),
    'editCardDarkGradientHighlightColor'    => env('LARAVEL_USERS_EDIT_CARD_DARK_GRADIENT_HIGHLIGHT_COLOR', null),
    'tableTextMaxWidth'                     => env('LARAVEL_USERS_TABLE_TEXT_MAX_WIDTH', 240),

    // Welcome emails are sent only when selected on the create form.
    'welcome' => [
        'enabled'              => env('LARAVEL_USERS_WELCOME_ENABLED', false),
        'force_password_reset' => env('LARAVEL_USERS_WELCOME_FORCE_PASSWORD_RESET', true),
        'password_broker'      => env('LARAVEL_USERS_WELCOME_PASSWORD_BROKER', null),
    ],

    // Editing keeps the existing password limits unless these rules are changed.
    'password' => [
        'confirmation_feedback' => env('LARAVEL_USERS_PASSWORD_CONFIRMATION_FEEDBACK', false),
        'confirmation_debounce' => env('LARAVEL_USERS_PASSWORD_CONFIRMATION_DEBOUNCE', 2000),
        'meter'                 => env('LARAVEL_USERS_PASSWORD_METER', false),
        'min'                   => env('LARAVEL_USERS_PASSWORD_MIN', 6),
        'max'                   => env('LARAVEL_USERS_PASSWORD_MAX', 20),
        'create_max'            => env('LARAVEL_USERS_PASSWORD_CREATE_MAX', null),
        'mixed_case'            => env('LARAVEL_USERS_PASSWORD_MIXED_CASE', false),
        'numbers'               => env('LARAVEL_USERS_PASSWORD_NUMBERS', false),
        'symbols'               => env('LARAVEL_USERS_PASSWORD_SYMBOLS', false),
    ],

    // Email actions use the host application's mailer, queue, and password broker.
    'emails' => [
        'enabled'              => env('LARAVEL_USERS_EMAILS_ENABLED', false),
        'bulk'                 => env('LARAVEL_USERS_EMAIL_BULK', true),
        'preview'              => env('LARAVEL_USERS_EMAIL_PREVIEW', true),
        'deleted'              => env('LARAVEL_USERS_EMAIL_DELETED', true),
        'message'              => env('LARAVEL_USERS_EMAIL_MESSAGE', true),
        'reset'                => env('LARAVEL_USERS_EMAIL_RESET', true),
        'welcome'              => env('LARAVEL_USERS_EMAIL_WELCOME', true),
        'goodbye'              => env('LARAVEL_USERS_EMAIL_GOODBYE', false),
        'goodbye_on_delete'    => env('LARAVEL_USERS_EMAIL_GOODBYE_ON_DELETE', false),
        'goodbye_auto_send'    => env('LARAVEL_USERS_EMAIL_GOODBYE_AUTO_SEND', false),
        'goodbye_restore'      => env('LARAVEL_USERS_EMAIL_GOODBYE_RESTORE', false),
        'goodbye_force_delete' => env('LARAVEL_USERS_EMAIL_GOODBYE_FORCE_DELETE', false),
        'goodbye_retention'    => env('LARAVEL_USERS_EMAIL_GOODBYE_RETENTION', false),
        'goodbye_show_expiry'  => env('LARAVEL_USERS_EMAIL_GOODBYE_SHOW_EXPIRY', true),
        'goodbye_expiry_mode'  => env('LARAVEL_USERS_EMAIL_GOODBYE_EXPIRY_MODE', 'custom'),
        'goodbye_duration'     => env('LARAVEL_USERS_EMAIL_GOODBYE_DURATION', 60),
        'goodbye_unit'         => env('LARAVEL_USERS_EMAIL_GOODBYE_UNIT', 'minutes'),
        'date_format'          => env('LARAVEL_USERS_EMAIL_DATE_FORMAT', 'M j, Y g:i A T'),
        'goodbye_subject'      => env('LARAVEL_USERS_EMAIL_GOODBYE_SUBJECT', null),
        'goodbye_message'      => env('LARAVEL_USERS_EMAIL_GOODBYE_MESSAGE', null),
        'edit_welcome'         => env('LARAVEL_USERS_EMAIL_EDIT_WELCOME', true),
        'edit_reset'           => env('LARAVEL_USERS_EMAIL_EDIT_RESET', true),
        'welcome_subject'      => env('LARAVEL_USERS_EMAIL_WELCOME_SUBJECT', null),
        'welcome_message'      => env('LARAVEL_USERS_EMAIL_WELCOME_MESSAGE', null),
        'reset_subject'        => env('LARAVEL_USERS_EMAIL_RESET_SUBJECT', null),
        'reset_message'        => env('LARAVEL_USERS_EMAIL_RESET_MESSAGE', null),
        'restore_subject'      => env('LARAVEL_USERS_EMAIL_RESTORE_SUBJECT', null),
        'restore_message'      => env('LARAVEL_USERS_EMAIL_RESTORE_MESSAGE', null),
        'force_delete_subject' => env('LARAVEL_USERS_EMAIL_FORCE_DELETE_SUBJECT', null),
        'force_delete_message' => env('LARAVEL_USERS_EMAIL_FORCE_DELETE_MESSAGE', null),
        'gate'                 => env('LARAVEL_USERS_EMAIL_GATE', null),
        'throttle'             => env('LARAVEL_USERS_EMAIL_THROTTLE', '10,1'),
        'password_broker'      => env('LARAVEL_USERS_EMAIL_PASSWORD_BROKER', null),
        // Minutes; null preserves the selected host broker's expiry for all reset flows.
        'reset_expire'             => env('LARAVEL_USERS_EMAIL_RESET_EXPIRE', null),
        'reset_duration'           => env('LARAVEL_USERS_EMAIL_RESET_DURATION', true),
        'reset_allow_never_expire' => env('LARAVEL_USERS_EMAIL_RESET_ALLOW_NEVER_EXPIRE', true),
        'reset_max_expire'         => env('LARAVEL_USERS_EMAIL_RESET_MAX_EXPIRE', 43200),
        'recipient_height'         => env('LARAVEL_USERS_EMAIL_RECIPIENT_HEIGHT', 96),
        'max_length'               => env('LARAVEL_USERS_EMAIL_MAX_LENGTH', 10000),
        'greeting'                 => env('LARAVEL_USERS_EMAIL_GREETING', 'Hi'),
        'use_greeting'             => env('LARAVEL_USERS_EMAIL_USE_GREETING', true),
        'include_name'             => env('LARAVEL_USERS_EMAIL_INCLUDE_NAME', true),
        'signoff'                  => env('LARAVEL_USERS_EMAIL_SIGNOFF', 'Thanks'),
        'use_signoff'              => env('LARAVEL_USERS_EMAIL_USE_SIGNOFF', true),
        'signoff_name'             => env('LARAVEL_USERS_EMAIL_SIGNOFF_NAME', ''),
    ],

    // Individual account settings require their optional migrations and explicit opt-in.
    'account' => [
        'enabled'          => env('LARAVEL_USERS_ACCOUNT_ENABLED', false),
        'settings_enabled' => env('LARAVEL_USERS_ACCOUNT_SETTINGS_ENABLED', false),
        'username_column'  => env('LARAVEL_USERS_ACCOUNT_USERNAME_COLUMN', 'name'),
        'name_column'      => env('LARAVEL_USERS_ACCOUNT_NAME_COLUMN', null),
        'profile'          => env('LARAVEL_USERS_ACCOUNT_PROFILE', true),
        'avatar'           => env('LARAVEL_USERS_ACCOUNT_AVATAR', true),
        'appearance'       => env('LARAVEL_USERS_ACCOUNT_APPEARANCE', true),
        'email'            => env('LARAVEL_USERS_ACCOUNT_EMAIL', true),
        'password'         => env('LARAVEL_USERS_ACCOUNT_PASSWORD', true),
        'delete'           => env('LARAVEL_USERS_ACCOUNT_DELETE', true),
        'email_expire'     => env('LARAVEL_USERS_ACCOUNT_EMAIL_EXPIRE', 1440),
        'throttle'         => env('LARAVEL_USERS_ACCOUNT_THROTTLE', '10,1'),
        'redirect'         => env('LARAVEL_USERS_ACCOUNT_DELETED_REDIRECT', '/'),
    ],

    // Cleanup permanently deletes old soft-deleted accounts. The host scheduler must be running.
    'cleanup' => [
        'enabled' => env('LARAVEL_USERS_CLEANUP_ENABLED', false),
        'amount'  => env('LARAVEL_USERS_CLEANUP_AMOUNT', 180),
        'unit'    => env('LARAVEL_USERS_CLEANUP_UNIT', 'days'),
    ],

    // Deleted-account links require the separately published account-links migration. Expiry is in minutes.
    'account_links' => [
        'enabled'            => env('LARAVEL_USERS_ACCOUNT_LINKS_ENABLED', false),
        'restore'            => env('LARAVEL_USERS_ACCOUNT_LINKS_RESTORE', true),
        'force_delete'       => env('LARAVEL_USERS_ACCOUNT_LINKS_FORCE_DELETE', true),
        'expire'             => env('LARAVEL_USERS_ACCOUNT_LINKS_EXPIRE', 60),
        'max_expire'         => env('LARAVEL_USERS_ACCOUNT_LINKS_MAX_EXPIRE', 43200),
        'allow_never_expire' => env('LARAVEL_USERS_ACCOUNT_LINKS_ALLOW_NEVER_EXPIRE', true),
        'throttle'           => env('LARAVEL_USERS_ACCOUNT_LINKS_THROTTLE', '20,1'),
    ],

    // The parent blade file
    'laravelUsersBladeExtended' => env('LARAVEL_USERS_LARAVEL_USERS_BLADE_EXTENDED', 'laravelusers::layouts.app'), // 'layouts.app'

    // Additional middleware protects every management route, including search and email.
    'middleware' => array_filter(array_map('trim', explode(',', env('LARAVEL_USERS_MIDDLEWARE', '')))),

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
    'roleModel'          => env('LARAVEL_USERS_ROLE_MODEL', 'jeremykenedy\LaravelRoles\Models\Role'),
    'permissionsEnabled' => env('LARAVEL_USERS_PERMISSIONS_ENABLED', false),
    'permissionModel'    => env('LARAVEL_USERS_PERMISSION_MODEL', null),
    'permissionsGate'    => env('LARAVEL_USERS_PERMISSIONS_GATE', null),
    'showRoleLevels'     => env('LARAVEL_USERS_SHOW_ROLE_LEVELS', true),

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
    'searchDebounceEnabled'      => env('LARAVEL_USERS_SEARCH_DEBOUNCE_ENABLED', false),
    'searchDebounce'             => env('LARAVEL_USERS_SEARCH_DEBOUNCE', 2000),
    'tableSorting'               => env('LARAVEL_USERS_TABLE_SORTING', false),
    'tableFiltering'             => env('LARAVEL_USERS_TABLE_FILTERING', false),
    'emailLinks'                 => env('LARAVEL_USERS_EMAIL_LINKS', false),
    'showCreatedColumn'          => env('LARAVEL_USERS_SHOW_CREATED_COLUMN', true),
    'showUpdatedColumn'          => env('LARAVEL_USERS_SHOW_UPDATED_COLUMN', true),
    'showOnlineColumn'           => env('LARAVEL_USERS_SHOW_ONLINE_COLUMN', false),
    'showLastLoginDetailsColumn' => env('LARAVEL_USERS_SHOW_LAST_LOGIN_DETAILS_COLUMN', false),
    'showProfileAvatar'          => env('LARAVEL_USERS_SHOW_PROFILE_AVATAR', true),
    'profileCardColor'           => env('LARAVEL_USERS_PROFILE_CARD_COLOR', '#2458b7'),
    'editCardColor'              => env('LARAVEL_USERS_EDIT_CARD_COLOR', '#705000'),
    'showLastLoginColumn'        => env('LARAVEL_USERS_SHOW_LAST_LOGIN_COLUMN', false),
    'showUserCount'              => env('LARAVEL_USERS_SHOW_USER_COUNT', false),
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
