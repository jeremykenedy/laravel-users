# Configuration

Every setting in `config/laravelusers.php` uses `env('NAME', default)`. An environment value overrides the bundled default. A value explicitly set in application config still takes precedence. The package preserves published config during install, update, and switch; merge the environment calls into older published files when needed. Newly generated `laravelusers-ui.php` settings also call the frontend and theme environment helpers, using command selections as their defaults. Older generated files can be refreshed with update. Rebuild Laravel's configuration cache after changing environment values.

```dotenv
LARAVEL_USERS_THEME=system
LARAVEL_USERS_THEME_TOGGLE=true
LARAVEL_USERS_SEARCH_DEBOUNCE_ENABLED=true
LARAVEL_USERS_SEARCH_DEBOUNCE=2000
LARAVEL_USERS_TABLE_BUTTONS_ICON_ONLY=false
LARAVEL_USERS_TABLE_SORTING=true
LARAVEL_USERS_TABLE_FILTERING=true
LARAVEL_USERS_COLUMN_VISIBILITY=true
LARAVEL_USERS_RESPONSIVE_TABLE=true
LARAVEL_USERS_AVATAR_ENABLED=true
LARAVEL_USERS_AVATAR_SOURCE=initials
LARAVEL_USERS_BULK_ACTIONS=true
```

## Layouts

Set `laravelUsersBladeExtended` to your dashboard layout. It should yield `template_title`, `template_linked_css`, `content`, and `template_scripts`. Use `showHeader=false` when your layout supplies its own header or drawer navigation. Alternatively set `headerView` to a Blade include that replaces the package header, and `footerView` to an optional footer include. `showLogout=false` hides the package logout action. `fullWidth=true` removes the package content width limit. The theme button appears after the username and logout in bundled navigation.

## Tables

`searchDebounceEnabled` submits search after typing stops. `searchDebounce` sets the delay in milliseconds after the final keystroke. Disable automatic submission with `searchDebounceEnabled=false`; Search and Enter remain available. Clear appears only when the field has text.

Sorting and column filters apply to displayed rows. Search finds users across the database. Keep server pagination for large user lists, or disable pagination when you want the whole directory available to table controls. `enabledDatatablesJs` and its original CDN settings remain available for Bootstrap 4. Choose one table-control integration in published views.

`columnVisibility` saves column choices in local storage separately for active and deleted users. It does not change server configuration. `responsiveTable` shows labelled entries below 640px; otherwise tables retain horizontal scrolling. `responsiveButtons` uses icons on small screens when icons are enabled. `tableButtonsIconOnly=true` uses icons for table actions at every screen size when icons are enabled. Accessible labels stay available. Tooltips follow `tooltipsEnabled`.

Activity columns require their tracking setting and their column setting. `showLastLoginDetailsColumn` combines the last device, operating system, browser, and IP in one column on both tables. Long details stay on one line with the full value available on hover. Search sends these details only when `include_login_details=1` is requested and the column is enabled. Online badges appear only for users currently online. Dates use the browser's timezone by default, with optional `displayTimezone`, `dateStyle`, and `timeStyle` overrides. `dateStyle` accepts `short` (the default), `medium`, `long`, or `full`; `timeStyle` accepts the same styles. For example, set `LARAVEL_USERS_DATE_STYLE=medium` to include a month name. Missing dates remain blank. Dates retain UTC values in their `datetime` attribute.

## Avatars

The optional avatar column is first. `avatar.source=initials` renders initials locally. `avatar.source=avatar` reads `avatar.attribute` from the host model; use an accessor returning an HTTP, HTTPS, or root-relative image URL. `avatar.source=gravatar` loads images from Gravatar using its [documented SHA-256 email hash and 404 fallback](https://docs.gravatar.com/sdk/images/). Enable that service only when wanted. Unavailable images reveal the configured `icon` or `initials` fallback. No external avatar service is contacted for initials.

`showProfileAvatar` controls the profile card avatar independently of the table column. It uses the same avatar source and fallback.

## Welcome emails

Welcome email and password setup choices are unchecked initially. Disable the choices with `welcome.enabled` or `welcome.force_password_reset`. Normal account creation retains its existing password rules and sends no mail unless selected.

Password setup requires a welcome email, the host `password.reset` route, a password broker for the configured model, and that broker's normal token storage. The new account receives an unusable random password, and the email explains that a password must be set before signing in. Reset links use the host broker's expiry and single-use token behavior. Passwords are never emailed.

Notifications use Laravel's mail channel and queue configuration after the user transaction commits. Run a queue worker when the host uses an asynchronous queue. A dispatch failure reports a warning without removing the created account. Later queue failures belong to the host's failed-job handling; use the host password recovery flow if an email needs to be resent.

## Deleted users and bulk actions

`softDeletedEnabled` requires Laravel's `SoftDeletes` trait and a `deleted_at` column on the configured user model. The package never adds that column automatically. The separate `/users/deleted` table supports restore and permanent deletion. Existing deletion follows the configured model's delete behavior; the opt-in flag controls deleted-user management.

`bulkActions` enables multi-selection and delete on the active table, with restore and permanent delete on the deleted table. The header combines Select all across the avatar and selection columns. The original DataTables integration keeps separate headers for compatibility. Select all selects visible rows only. Bulk controls appear only while rows are selected. The deleted-user link appears only when deleted users exist. Search refreshes clear selection. `bulkLimit` bounds a request, with an upper limit of 1000. Invalid, missing, duplicate, or self-selected records are rejected before changes. Bulk writes share a database transaction and keep model events.

## Environment variables

| Config key | Environment variable | Default |
| --- | --- | --- |
| `frontend` | `LARAVEL_USERS_FRONTEND` | `bootstrap4` |
| `theme` | `LARAVEL_USERS_THEME` | `light` |
| `themeToggle` | `LARAVEL_USERS_THEME_TOGGLE` | `false` |
| `bootstrap5CssCdn` | `LARAVEL_USERS_BOOTSTRAP5_CSS_CDN` | `https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css` |
| `showHeader` | `LARAVEL_USERS_SHOW_HEADER` | `true` |
| `showLogout` | `LARAVEL_USERS_SHOW_LOGOUT` | `true` |
| `headerView` | `LARAVEL_USERS_HEADER_VIEW` | `null` |
| `footerView` | `LARAVEL_USERS_FOOTER_VIEW` | `null` |
| `fullWidth` | `LARAVEL_USERS_FULL_WIDTH` | `false` |
| `iconsEnabled` | `LARAVEL_USERS_ICONS_ENABLED` | `true` |
| `tableButtonsIconOnly` | `LARAVEL_USERS_TABLE_BUTTONS_ICON_ONLY` | `false` |
| `responsiveButtons` | `LARAVEL_USERS_RESPONSIVE_BUTTONS` | `true` |
| `localizeDates` | `LARAVEL_USERS_LOCALIZE_DATES` | `true` |
| `dateStyle` | `LARAVEL_USERS_DATE_STYLE` | `short` |
| `timeStyle` | `LARAVEL_USERS_TIME_STYLE` | `short` |
| `displayTimezone` | `LARAVEL_USERS_DISPLAY_TIMEZONE` | `null` |
| `bulkActions` | `LARAVEL_USERS_BULK_ACTIONS` | `false` |
| `bulkLimit` | `LARAVEL_USERS_BULK_LIMIT` | `100` |
| `responsiveTable` | `LARAVEL_USERS_RESPONSIVE_TABLE` | `false` |
| `columnVisibility` | `LARAVEL_USERS_COLUMN_VISIBILITY` | `false` |
| `activity.login` | `LARAVEL_USERS_ACTIVITY_LOGIN` | `false` |
| `activity.online` | `LARAVEL_USERS_ACTIVITY_ONLINE` | `false` |
| `activity.guard` | `LARAVEL_USERS_ACTIVITY_GUARD` | `web` |
| `activity.connection` | `LARAVEL_USERS_ACTIVITY_CONNECTION` | `null` |
| `activity.cache_store` | `LARAVEL_USERS_ACTIVITY_CACHE_STORE` | `null` |
| `activity.online_seconds` | `LARAVEL_USERS_ACTIVITY_ONLINE_SECONDS` | `300` |
| `avatar.enabled` | `LARAVEL_USERS_AVATAR_ENABLED` | `false` |
| `avatar.source` | `LARAVEL_USERS_AVATAR_SOURCE` | `initials` |
| `avatar.attribute` | `LARAVEL_USERS_AVATAR_ATTRIBUTE` | `avatar` |
| `avatar.fallback` | `LARAVEL_USERS_AVATAR_FALLBACK` | `icon` |
| `avatar.size` | `LARAVEL_USERS_AVATAR_SIZE` | `40` |
| `welcome.enabled` | `LARAVEL_USERS_WELCOME_ENABLED` | `true` |
| `welcome.force_password_reset` | `LARAVEL_USERS_WELCOME_FORCE_PASSWORD_RESET` | `true` |
| `welcome.password_broker` | `LARAVEL_USERS_WELCOME_PASSWORD_BROKER` | `null` |
| `laravelUsersBladeExtended` | `LARAVEL_USERS_LARAVEL_USERS_BLADE_EXTENDED` | `laravelusers::layouts.app` |
| `authEnabled` | `LARAVEL_USERS_AUTH_ENABLED` | `true` |
| `rolesEnabled` | `LARAVEL_USERS_ROLES_ENABLED` | `false` |
| `rolesMiddlwareEnabled` | `LARAVEL_USERS_ROLES_MIDDLWARE_ENABLED` | `true` |
| `rolesMiddlware` | `LARAVEL_USERS_ROLES_MIDDLWARE` | `role:admin` |
| `roleModel` | `LARAVEL_USERS_ROLE_MODEL` | `jeremykenedy\LaravelRoles\Models\Role` |
| `softDeletedEnabled` | `LARAVEL_USERS_SOFT_DELETED_ENABLED` | `false` |
| `defaultUserModel` | `LARAVEL_USERS_DEFAULT_USER_MODEL` | `App\Models\User` |
| `showUsersBlade` | `LARAVEL_USERS_SHOW_USERS_BLADE` | `laravelusers::usersmanagement.show-users` |
| `createUserBlade` | `LARAVEL_USERS_CREATE_USER_BLADE` | `laravelusers::usersmanagement.create-user` |
| `showIndividualUserBlade` | `LARAVEL_USERS_SHOW_INDIVIDUAL_USER_BLADE` | `laravelusers::usersmanagement.show-user` |
| `editIndividualUserBlade` | `LARAVEL_USERS_EDIT_INDIVIDUAL_USER_BLADE` | `laravelusers::usersmanagement.edit-user` |
| `showDeletedUsersBlade` | `LARAVEL_USERS_SHOW_DELETED_USERS_BLADE` | `laravelusers::usersmanagement.deleted-users` |
| `enablePackageBootstapAlerts` | `LARAVEL_USERS_ENABLE_PACKAGE_BOOTSTAP_ALERTS` | `true` |
| `enablePagination` | `LARAVEL_USERS_ENABLE_PAGINATION` | `true` |
| `paginateListSize` | `LARAVEL_USERS_PAGINATE_LIST_SIZE` | `25` |
| `enableSearchUsers` | `LARAVEL_USERS_ENABLE_SEARCH_USERS` | `true` |
| `searchDebounceEnabled` | `LARAVEL_USERS_SEARCH_DEBOUNCE_ENABLED` | `true` |
| `searchDebounce` | `LARAVEL_USERS_SEARCH_DEBOUNCE` | `2000` |
| `tableSorting` | `LARAVEL_USERS_TABLE_SORTING` | `false` |
| `tableFiltering` | `LARAVEL_USERS_TABLE_FILTERING` | `false` |
| `emailLinks` | `LARAVEL_USERS_EMAIL_LINKS` | `true` |
| `showCreatedColumn` | `LARAVEL_USERS_SHOW_CREATED_COLUMN` | `true` |
| `showUpdatedColumn` | `LARAVEL_USERS_SHOW_UPDATED_COLUMN` | `true` |
| `showOnlineColumn` | `LARAVEL_USERS_SHOW_ONLINE_COLUMN` | `true` |
| `showLastLoginDetailsColumn` | `LARAVEL_USERS_SHOW_LAST_LOGIN_DETAILS_COLUMN` | `true` |
| `showProfileAvatar` | `LARAVEL_USERS_SHOW_PROFILE_AVATAR` | `true` |
| `showLastLoginColumn` | `LARAVEL_USERS_SHOW_LAST_LOGIN_COLUMN` | `true` |
| `showUserCount` | `LARAVEL_USERS_SHOW_USER_COUNT` | `true` |
| `confirmDelete` | `LARAVEL_USERS_CONFIRM_DELETE` | `true` |
| `confirmSave` | `LARAVEL_USERS_CONFIRM_SAVE` | `true` |
| `enabledDatatablesJs` | `LARAVEL_USERS_ENABLED_DATATABLES_JS` | `false` |
| `datatablesJsStartCount` | `LARAVEL_USERS_DATATABLES_JS_START_COUNT` | `25` |
| `datatablesCssCDN` | `LARAVEL_USERS_DATATABLES_CSS_CDN` | `https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css` |
| `datatablesJsCDN` | `LARAVEL_USERS_DATATABLES_JS_CDN` | `https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js` |
| `datatablesJsPresetCDN` | `LARAVEL_USERS_DATATABLES_JS_PRESET_CDN` | `https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js` |
| `tooltipsEnabled` | `LARAVEL_USERS_TOOLTIPS_ENABLED` | `true` |
| `enableBootstrapPopperJsCdn` | `LARAVEL_USERS_ENABLE_BOOTSTRAP_POPPER_JS_CDN` | `true` |
| `bootstrapPopperJsCdn` | `LARAVEL_USERS_BOOTSTRAP_POPPER_JS_CDN` | `https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js` |
| `fontAwesomeEnabled` | `LARAVEL_USERS_FONT_AWESOME_ENABLED` | `true` |
| `fontAwesomeCdn` | `LARAVEL_USERS_FONT_AWESOME_CDN` | `https://use.fontawesome.com/releases/v5.0.6/css/all.css` |
| `enableBootstrapCssCdn` | `LARAVEL_USERS_ENABLE_BOOTSTRAP_CSS_CDN` | `true` |
| `bootstrapCssCdn` | `LARAVEL_USERS_BOOTSTRAP_CSS_CDN` | `https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css` |
| `enableAppCss` | `LARAVEL_USERS_ENABLE_APP_CSS` | `true` |
| `appCssPublicFile` | `LARAVEL_USERS_APP_CSS_PUBLIC_FILE` | `css/app.css` |
| `enableBootstrapJsCdn` | `LARAVEL_USERS_ENABLE_BOOTSTRAP_JS_CDN` | `true` |
| `bootstrapJsCdn` | `LARAVEL_USERS_BOOTSTRAP_JS_CDN` | `https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js` |
| `enableAppJs` | `LARAVEL_USERS_ENABLE_APP_JS` | `true` |
| `appJsPublicFile` | `LARAVEL_USERS_APP_JS_PUBLIC_FILE` | `js/app.js` |
| `enablejQueryCdn` | `LARAVEL_USERS_ENABLE_JQUERY_CDN` | `true` |
| `jQueryCdn` | `LARAVEL_USERS_JQUERY_CDN` | `https://code.jquery.com/jquery-3.3.1.min.js` |
