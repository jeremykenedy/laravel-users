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

Set `laravelUsersBladeExtended` to your dashboard layout. It should yield `template_title`, `template_linked_css`, `content`, and `template_scripts`. Use `showHeader=false` when your layout supplies its own header or drawer navigation. Alternatively set `headerView` to a Blade include that replaces the package header, and `footerView` to an optional footer include. The Laravel Users brand links to the application home. Breadcrumbs are hidden by default for compatibility; set `showBreadcrumbs=true` to show the current path below the navigation and within the content width. `showLogout=false` hides the package logout action. `fullWidth=true` removes the package content width limit. The theme button appears to the right of the avatar/username menu. Logout is an optional POST action inside that menu. Use the [standalone components](navigation-components.md) in your own navigation.

## Tables

`searchDebounceEnabled` submits search after typing stops. `searchDebounce` sets the delay in milliseconds after the final keystroke. Disable automatic submission with `searchDebounceEnabled=false`; Search and Enter remain available. Clear appears only when the field has text.

Sorting and column filters apply to displayed rows. Search finds users across the database. Keep server pagination for large user lists, or disable pagination when you want the whole directory available to table controls. `enabledDatatablesJs` and its original CDN settings remain available for Bootstrap 4. Choose one table-control integration in published views.

`columnVisibility` saves column choices in local storage separately for active and deleted users. It does not change server configuration. `responsiveTable` shows labelled entries below 640px; otherwise tables retain horizontal scrolling. `responsiveButtons` uses icons on mobile and tablet screens when icons are enabled. `tableButtonsIconOnly=true` uses icons for table actions at every screen size when icons are enabled. Accessible labels stay available. Tooltips follow `tooltipsEnabled`.

Activity columns require their tracking setting and their column setting. `showLastLoginDetailsColumn` stacks the last device, operating system, browser, and IP in one column on both tables. Each item uses a small font and stays on one line, with the full details available on hover. Search sends these details only when `include_login_details=1` is requested and the column is enabled. Online badges appear only for users currently online. Dates use the browser's timezone by default, with optional `displayTimezone`, `dateStyle`, and `timeStyle` overrides. `dateStyle` accepts `short` (the default), `medium`, `long`, or `full`; `timeStyle` accepts the same styles. For example, set `LARAVEL_USERS_DATE_STYLE=medium` to include a month name. Missing dates remain blank except Last login, which displays No logins. Dates retain UTC values in their `datetime` attribute.

`tableTextMaxWidth` bounds name and email contents in table view, defaulting to 240 pixels. Long values keep their full text and links inside a horizontally scrollable area. Keyboard focus is available when the contents overflow. Card view wraps long values. Set the width to 0 to keep the previous unrestricted table widths.

## Settings and appearance

See [user settings](settings.md) for migrations, host authorization gates, role/permission/level restrictions and package controls. The page and per-user appearance remain separate opt-ins. `profileCardGradient` and `editCardGradient` independently enable gradients; `profileCardGradientStrength` and `editCardGradientStrength` default to 50 and range from 0 to 100. Base colors retain their blue/yellow defaults. `profileCardDarkColor`, `profileCardDarkGradient` and `profileCardDarkGradientStrength` customize view and directory cards in dark mode; `editCardDarkColor`, `editCardDarkGradient` and `editCardDarkGradientStrength` customize edit cards. All six use corresponding `LARAVEL_USERS_*_DARK_*` environment helpers and default to null, inheriting the light appearance. The settings page offers independent dark controls, previews and reset buttons. Individual dark overrides require the separate additive dark-appearance migration and use the same appearance permission. Reset controls restore config/environment defaults globally, or inheritance individually.

Saved global settings override config fallback values only while `settings.enabled=true`. `settings.packages.enabled` also requires a dedicated gate, a persistent queue and shared cache. It never grants package access to every authenticated user.

## Avatars

Per-user selection is a separate opt-in through `avatar.per_user`, defaulting to false. It requires the separately published `laravelusers-avatar-migrations` migration; existing users inherit the global source. See [avatar setup and compatibility](avatars.md).

The optional avatar column is first. `avatar.source=initials` renders initials locally. `avatar.source=avatar` reads `avatar.attribute` from the host model; use an accessor returning an HTTP, HTTPS, or root-relative image URL. `avatar.source=gravatar` loads images from Gravatar using its [documented SHA-256 email hash and 404 fallback](https://docs.gravatar.com/sdk/images/). Enable that service only when wanted. Identicon, MonsterID, RoboHash, Retro, Wavatar and `mp` are also Gravatar styles. Local DiceBear and UI Avatars are available; see [the full source and privacy guide](avatars.md). Unavailable images reveal the configured `icon` or `initials` fallback. No external avatar service is contacted for initials.

`showProfileAvatar` controls the profile card avatar independently of the table column. It uses the same avatar source and fallback.

`tableViewToggle` adds Table view and Card view buttons to both directories. The choice persists in local storage separately for active and deleted users, including search and pagination. Disable it to ignore saved view choices and use the configured `responsiveTable` behavior. Sorting, column choices, and bulk actions work in both views. The toolbar orders Table view, Card view, Columns, Filters, Sort, Select all, and Deselect all. Filters appears only in card view; the table keeps its column filters. Clicking anywhere on the joined Sort control opens the native dropdown. Select all skips disabled users and selects the visible rows. Selection buttons disable themselves when there is nothing to select or clear. Disabled buttons use the not-allowed cursor.

`cardColumns` sets the card grid: `mobile=1`, `tablet=2`, `desktop=3`, and `wide=4`. Tablet starts at 768px, desktop at 992px, and extra-large desktop at 1400px. Counts are limited to 1 through 12. Each setting accepts its own environment override, such as `LARAVEL_USERS_CARD_COLUMNS_TABLET=3`. Cards keep field labels and icons on the left and values on the right. Text actions share the full footer width evenly. Icon actions keep their natural width and are centered. Footers stay at the bottom of each card. Login details show icons for recognized browsers and operating systems, with a network icon for the IP address. These icons follow `iconsEnabled`.

`profileCardColor` sets the base color of the Bootstrap 5 avatar panels on directory cards and individual user cards. Both use the same gradient, with a lighter highlight around the avatar, and the profile panel selects readable text colors automatically. Use a three- or six-digit hex color, such as `#2458b7` or `#264e36`. Invalid values fall back to `#2458b7`. Set `LARAVEL_USERS_PROFILE_CARD_COLOR="#264e36"` in `.env` to override the default. On mobile, Back to users uses its reply icon when `responsiveButtons` is enabled, with a tooltip when `tooltipsEnabled` is enabled.

## Welcome emails

Welcome email and password setup choices are unchecked initially. `welcome.enabled` is the global admin-controlled switch for the creation option and Welcome actions in the email menus. It defaults to false and can be changed in Settings, Email Templates after the optional settings migration is installed. The saved choice is combined with `emails.enabled` and `emails.welcome`, so those config/environment switches can always disable sending. `welcome.force_password_reset` controls the separate password setup choice. Normal account creation retains its existing password rules and sends no mail unless selected.

Password setup requires a welcome email, the host `password.reset` route, a password broker for the configured model, and that broker's normal token storage. The new account receives an unusable random password, and the email explains that a password must be set before signing in. Reset links use the host broker's expiry and single-use token behavior. Passwords are never emailed.

The global Settings, Email Templates section stores the welcome subject and message alongside the other editable email defaults. Individual email senders can customize that content when `emails.edit_welcome` is enabled. The welcome template is `src/resources/views/emails/welcome.blade.php`. Laravel renders it as both HTML and plain text. It includes either a sign-in button or a password-setup button with its expiration notice. Publish the email templates to customize them in your application:

```bash
php artisan vendor:publish --provider="jeremykenedy\laravelusers\LaravelUsersServiceProvider" --tag=laravelusers-email-views
```

Edit `resources/views/vendor/laravelusers/emails/welcome.blade.php`. Existing overrides are preserved unless publishing with `--force`. Custom message templates are published by the same command.

Notifications use Laravel's mail channel and queue configuration after the user transaction commits. Run a queue worker when the host uses an asynchronous queue. A dispatch failure reports a warning without removing the created account. Later queue failures belong to the host's failed-job handling; use the host password recovery flow if an email needs to be resent.

## Editing passwords and emailing users

The Bootstrap 5 edit card uses the same avatar sidebar as the profile card. `editCardColor` controls its dark-yellow base color, defaulting to `#705000`. Set `LARAVEL_USERS_EDIT_CARD_COLOR` to a three- or six-digit hex color to change it.

`password.meter` shows a strength meter when a password is entered on the edit form. The required length and optional mixed-case, number, and symbol rules come from `password`. The server enforces the same requirements even when the meter is disabled. Defaults preserve the existing 6-to-20-character edit limits. Leaving the password blank keeps it unchanged. Account creation retains its existing validation.

Email actions share one component on the edit page, profile page, and directory. Directory rows use an Email menu to keep actions compact. `emails.enabled` disables all three actions; `emails.message`, `emails.reset`, and `emails.welcome` disable each individually. The welcome action also respects `welcome.enabled`.

The message dialog has a subject, plain-text body, optional greeting, and optional closing. Greeting and closing are enabled by default, with `Hi` and `Thanks` as their defaults. The recipient's username can be appended to the greeting with a comma. A separate name can appear below the closing. Bulk mail uses the same dialog, personalizes each greeting, and queues separate notifications for each recipient. HTML is escaped in the email body.

Password reset uses the host's `password.reset` route and matching password broker. `emails.password_broker=null` uses the host's default broker. Tokens follow that broker's `expire` and `throttle` settings in `config/auth.php`; links are single use. Sending a reset or welcome email does not change an existing user's password.

Set `emails.reset_expire`, or `LARAVEL_USERS_EMAIL_RESET_EXPIRE=30`, to change the expiration to a positive number of minutes. The default is `null`, which preserves the host's expiry. This overrides `expire` on the selected host broker for all of its reset links, including links issued by the application's own reset form and welcome setup links using that broker. Other brokers and throttling remain unchanged. The email and confirmation dialog show the configured duration. Invalid values preserve the host setting. After changing cached configuration, rebuild it with `php artisan config:cache` and restart queue workers.

Email requests always require authentication and use the package's configured role middleware. Set `emails.gate` to an application gate to restrict them further. If a model policy exists, every recipient must pass its `update` authorization before any email is queued. `emails.throttle` limits requests independently of the password broker. Bulk mail also requires `bulkActions=true` and respects `bulkLimit`. Missing or duplicate recipients and active/deleted selection mismatches are rejected before dispatch.

Configure the host mailer and run its queue worker when using an asynchronous queue. Success means notifications were queued, not that delivery has been confirmed. A dispatch failure reports how many notifications were queued before it failed; later delivery failures use the host application's normal failed-job handling.

## Deleted users and bulk actions

`softDeletedEnabled` requires Laravel's `SoftDeletes` trait and a `deleted_at` column on the configured user model. The package never adds that column automatically. The separate `/users/deleted` table supports restore and permanent deletion. Existing deletion follows the configured model's delete behavior; the opt-in flag controls deleted-user management.

`bulkActions` enables multi-selection and delete on the active table, with restore and permanent delete on the deleted table. The active directory also offers custom, welcome, and password reset emails when their email settings are enabled. Select all and Deselect all appear in the list toolbar. Select all selects visible rows only. The signed-in user's checkbox is omitted in both table and card views, including search results. Bulk controls appear only while rows are selected. The deleted-user link appears only when deleted users exist. Search refreshes clear selection. `bulkLimit` bounds a request, with an upper limit of 1000. Invalid, missing, duplicate, or self-selected records are rejected before bulk deletion. Bulk writes share a database transaction and keep model events.

## Email previews, expiration, and account links

See [the email guide](emails.md) for all feature switches, editable expiration, recipient chips, published templates, preview behavior, deleted-account recovery, and security details. `emails.enabled=false` disables all package mail, including creation welcome mail. `emails.bulk=false` keeps other bulk actions while disabling multi-recipient emails.

Both create and edit forms offer a password strength meter. The default create minimum remains six characters with no maximum; the default edit limits remain six to twenty characters. `password.create_max` optionally limits new passwords without changing the edit maximum. Stronger mixed-case, number, and symbol requirements apply server-side on both forms when enabled. The meter can be disabled independently.

Password confirmation feedback appears on blur or after `password.confirmation_debounce` milliseconds, defaulting to 2000. Disable the feedback with `password.confirmation_feedback=false`; server-side confirmation remains required. Form icon labels focus their matching input when clicked.

## Roles and middleware

See [roles and permissions](roles.md) for both supported optional packages, multiple role selection, optional direct permissions, shared role levels, custom models, guards, teams, and middleware setup. `middleware` applies even with role UI disabled. Generated role choices live in `laravelusers-roles.php`, preserve the main config, and use the original role environment names.

## Environment variables

| Config key | Environment variable | Default |
| --- | --- | --- |
| `laravelUsersBladeExtended` | `LARAVEL_USERS_LARAVEL_USERS_BLADE_EXTENDED` | `laravelusers::layouts.app` |
| `middleware` | `LARAVEL_USERS_MIDDLEWARE` | `[]`; comma-separated middleware |
| `frontend` | `LARAVEL_USERS_FRONTEND` | `bootstrap4` |
| `theme` | `LARAVEL_USERS_THEME` | `light` |
| `themeToggle` | `LARAVEL_USERS_THEME_TOGGLE` | `false` |
| `bootstrap5CssCdn` | `LARAVEL_USERS_BOOTSTRAP5_CSS_CDN` | `https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css` |
| `showHeader` | `LARAVEL_USERS_SHOW_HEADER` | `true` |
| `showBreadcrumbs` | `LARAVEL_USERS_SHOW_BREADCRUMBS` | `false` |
| `showLogout` | `LARAVEL_USERS_SHOW_LOGOUT` | `true` |
| `headerView` | `LARAVEL_USERS_HEADER_VIEW` | `null` |
| `footerView` | `LARAVEL_USERS_FOOTER_VIEW` | `null` |
| `fullWidth` | `LARAVEL_USERS_FULL_WIDTH` | `false` |
| `iconsEnabled` | `LARAVEL_USERS_ICONS_ENABLED` | `true` |
| `tableButtonsIconOnly` | `LARAVEL_USERS_TABLE_BUTTONS_ICON_ONLY` | `false` |
| `responsiveButtons` | `LARAVEL_USERS_RESPONSIVE_BUTTONS` | `false` |
| `localizeDates` | `LARAVEL_USERS_LOCALIZE_DATES` | `false` |
| `dateStyle` | `LARAVEL_USERS_DATE_STYLE` | `short` |
| `timeStyle` | `LARAVEL_USERS_TIME_STYLE` | `short` |
| `displayTimezone` | `LARAVEL_USERS_DISPLAY_TIMEZONE` | `null` |
| `bulkActions` | `LARAVEL_USERS_BULK_ACTIONS` | `false` |
| `bulkLimit` | `LARAVEL_USERS_BULK_LIMIT` | `100` |
| `tableViewToggle` | `LARAVEL_USERS_TABLE_VIEW_TOGGLE` | `false` |
| `responsiveTable` | `LARAVEL_USERS_RESPONSIVE_TABLE` | `false` |
| `columnVisibility` | `LARAVEL_USERS_COLUMN_VISIBILITY` | `false` |
| `cardColumns.mobile` | `LARAVEL_USERS_CARD_COLUMNS_MOBILE` | `1` |
| `cardColumns.tablet` | `LARAVEL_USERS_CARD_COLUMNS_TABLET` | `2` |
| `cardColumns.desktop` | `LARAVEL_USERS_CARD_COLUMNS_DESKTOP` | `3` |
| `cardColumns.wide` | `LARAVEL_USERS_CARD_COLUMNS_WIDE` | `4` |
| `activity.login` | `LARAVEL_USERS_ACTIVITY_LOGIN` | `false` |
| `activity.online` | `LARAVEL_USERS_ACTIVITY_ONLINE` | `false` |
| `activity.guard` | `LARAVEL_USERS_ACTIVITY_GUARD` | `web` |
| `activity.connection` | `LARAVEL_USERS_ACTIVITY_CONNECTION` | `null` |
| `activity.cache_store` | `LARAVEL_USERS_ACTIVITY_CACHE_STORE` | `null` |
| `activity.online_seconds` | `LARAVEL_USERS_ACTIVITY_ONLINE_SECONDS` | `300` |
| `avatar.enabled` | `LARAVEL_USERS_AVATAR_ENABLED` | `false` |
| `avatar.per_user` | `LARAVEL_USERS_AVATAR_PER_USER` | `false` |
| `avatar.source` | `LARAVEL_USERS_AVATAR_SOURCE` | `initials` |
| `avatar.attribute` | `LARAVEL_USERS_AVATAR_ATTRIBUTE` | `avatar` |
| `avatar.fallback` | `LARAVEL_USERS_AVATAR_FALLBACK` | `icon` |
| `avatar.size` | `LARAVEL_USERS_AVATAR_SIZE` | `40` |
| `avatar.image_size` | `LARAVEL_USERS_AVATAR_IMAGE_SIZE` | `256` |
| `avatar.remote_enabled` | `LARAVEL_USERS_AVATAR_REMOTE_ENABLED` | `true` |
| `avatar.dicebear.driver` | `LARAVEL_USERS_AVATAR_DICEBEAR_DRIVER` | `local` |
| `avatar.dicebear.style` | `LARAVEL_USERS_AVATAR_DICEBEAR_STYLE` | `identicon` |
| `avatar.dicebear.url` | `LARAVEL_USERS_AVATAR_DICEBEAR_URL` | `null` |
| `avatar.ui_avatars.driver` | `LARAVEL_USERS_AVATAR_UI_DRIVER` | `local` |
| `avatar.ui_avatars.url` | `LARAVEL_USERS_AVATAR_UI_URL` | `https://ui-avatars.com/api/` |
| `avatar.ui_avatars.background` | `LARAVEL_USERS_AVATAR_UI_BACKGROUND` | `e7eef8` |
| `avatar.ui_avatars.color` | `LARAVEL_USERS_AVATAR_UI_COLOR` | `344760` |
| `settings.enabled` | `LARAVEL_USERS_SETTINGS_ENABLED` | `false` |
| `settings.connection` | `LARAVEL_USERS_SETTINGS_CONNECTION` | `null` |
| `settings.gate` | `LARAVEL_USERS_SETTINGS_GATE` | `manage-laravelusers-settings` |
| `settings.packages.enabled` | `LARAVEL_USERS_SETTINGS_PACKAGES_ENABLED` | `false` |
| `settings.packages.gate` | `LARAVEL_USERS_SETTINGS_PACKAGES_GATE` | `manage-laravelusers-packages` |
| `settings.packages.queue` | `LARAVEL_USERS_SETTINGS_PACKAGES_QUEUE` | `default` |
| `settings.packages.connection` | `LARAVEL_USERS_SETTINGS_PACKAGES_CONNECTION` | `null` |
| `notifications.driver` | `LARAVEL_USERS_NOTIFICATIONS_DRIVER` | `alert` |
| `notifications.dismissible` | `LARAVEL_USERS_NOTIFICATIONS_DISMISSIBLE` | `true` |
| `appearance.per_user` | `LARAVEL_USERS_APPEARANCE_PER_USER` | `false` |
| `profileCardGradient` | `LARAVEL_USERS_PROFILE_CARD_GRADIENT` | `true` |
| `profileCardGradientStrength` | `LARAVEL_USERS_PROFILE_CARD_GRADIENT_STRENGTH` | `50` |
| `editCardGradient` | `LARAVEL_USERS_EDIT_CARD_GRADIENT` | `true` |
| `editCardGradientStrength` | `LARAVEL_USERS_EDIT_CARD_GRADIENT_STRENGTH` | `50` |
| `tableTextMaxWidth` | `LARAVEL_USERS_TABLE_TEXT_MAX_WIDTH` | `240` |
| `welcome.enabled` | `LARAVEL_USERS_WELCOME_ENABLED` | `false` |
| `welcome.force_password_reset` | `LARAVEL_USERS_WELCOME_FORCE_PASSWORD_RESET` | `true` |
| `welcome.password_broker` | `LARAVEL_USERS_WELCOME_PASSWORD_BROKER` | `null` |
| `password.confirmation_feedback` | `LARAVEL_USERS_PASSWORD_CONFIRMATION_FEEDBACK` | `false` |
| `password.confirmation_debounce` | `LARAVEL_USERS_PASSWORD_CONFIRMATION_DEBOUNCE` | `2000` |
| `password.meter` | `LARAVEL_USERS_PASSWORD_METER` | `false` |
| `password.min` | `LARAVEL_USERS_PASSWORD_MIN` | `6` |
| `password.max` | `LARAVEL_USERS_PASSWORD_MAX` | `20` |
| `password.create_max` | `LARAVEL_USERS_PASSWORD_CREATE_MAX` | `null` |
| `password.mixed_case` | `LARAVEL_USERS_PASSWORD_MIXED_CASE` | `false` |
| `password.numbers` | `LARAVEL_USERS_PASSWORD_NUMBERS` | `false` |
| `password.symbols` | `LARAVEL_USERS_PASSWORD_SYMBOLS` | `false` |
| `emails.enabled` | `LARAVEL_USERS_EMAILS_ENABLED` | `false` |
| `emails.bulk` | `LARAVEL_USERS_EMAIL_BULK` | `true` |
| `emails.preview` | `LARAVEL_USERS_EMAIL_PREVIEW` | `true` |
| `emails.deleted` | `LARAVEL_USERS_EMAIL_DELETED` | `true` |
| `emails.message` | `LARAVEL_USERS_EMAIL_MESSAGE` | `true` |
| `emails.reset` | `LARAVEL_USERS_EMAIL_RESET` | `true` |
| `emails.welcome` | `LARAVEL_USERS_EMAIL_WELCOME` | `true` |
| `emails.edit_welcome` | `LARAVEL_USERS_EMAIL_EDIT_WELCOME` | `true` |
| `emails.edit_reset` | `LARAVEL_USERS_EMAIL_EDIT_RESET` | `true` |
| `emails.welcome_subject` | `LARAVEL_USERS_EMAIL_WELCOME_SUBJECT` | `null` |
| `emails.welcome_message` | `LARAVEL_USERS_EMAIL_WELCOME_MESSAGE` | `null` |
| `emails.reset_subject` | `LARAVEL_USERS_EMAIL_RESET_SUBJECT` | `null` |
| `emails.reset_message` | `LARAVEL_USERS_EMAIL_RESET_MESSAGE` | `null` |
| `emails.gate` | `LARAVEL_USERS_EMAIL_GATE` | `null` |
| `emails.throttle` | `LARAVEL_USERS_EMAIL_THROTTLE` | `10,1` |
| `emails.password_broker` | `LARAVEL_USERS_EMAIL_PASSWORD_BROKER` | `null` |
| `emails.reset_expire` | `LARAVEL_USERS_EMAIL_RESET_EXPIRE` | `null` |
| `emails.reset_duration` | `LARAVEL_USERS_EMAIL_RESET_DURATION` | `true` |
| `emails.reset_allow_never_expire` | `LARAVEL_USERS_EMAIL_RESET_ALLOW_NEVER_EXPIRE` | `true` |
| `emails.reset_max_expire` | `LARAVEL_USERS_EMAIL_RESET_MAX_EXPIRE` | `43200` |
| `emails.recipient_height` | `LARAVEL_USERS_EMAIL_RECIPIENT_HEIGHT` | `96` |
| `emails.max_length` | `LARAVEL_USERS_EMAIL_MAX_LENGTH` | `10000` |
| `emails.greeting` | `LARAVEL_USERS_EMAIL_GREETING` | `Hi` |
| `emails.use_greeting` | `LARAVEL_USERS_EMAIL_USE_GREETING` | `true` |
| `emails.include_name` | `LARAVEL_USERS_EMAIL_INCLUDE_NAME` | `true` |
| `emails.signoff` | `LARAVEL_USERS_EMAIL_SIGNOFF` | `Thanks` |
| `emails.use_signoff` | `LARAVEL_USERS_EMAIL_USE_SIGNOFF` | `true` |
| `emails.signoff_name` | `LARAVEL_USERS_EMAIL_SIGNOFF_NAME` | `` |
| `account_links.enabled` | `LARAVEL_USERS_ACCOUNT_LINKS_ENABLED` | `false` |
| `account_links.restore` | `LARAVEL_USERS_ACCOUNT_LINKS_RESTORE` | `true` |
| `account_links.force_delete` | `LARAVEL_USERS_ACCOUNT_LINKS_FORCE_DELETE` | `true` |
| `account_links.expire` | `LARAVEL_USERS_ACCOUNT_LINKS_EXPIRE` | `60` |
| `account_links.max_expire` | `LARAVEL_USERS_ACCOUNT_LINKS_MAX_EXPIRE` | `43200` |
| `account_links.allow_never_expire` | `LARAVEL_USERS_ACCOUNT_LINKS_ALLOW_NEVER_EXPIRE` | `true` |
| `account_links.throttle` | `LARAVEL_USERS_ACCOUNT_LINKS_THROTTLE` | `20,1` |
| `authEnabled` | `LARAVEL_USERS_AUTH_ENABLED` | `true` |
| `rolesEnabled` | `LARAVEL_USERS_ROLES_ENABLED` | `false` |
| `rolesMiddlwareEnabled` | `LARAVEL_USERS_ROLES_MIDDLWARE_ENABLED` | `true` |
| `rolesMiddlware` | `LARAVEL_USERS_ROLES_MIDDLWARE` | `role:admin` |
| `roleModel` | `LARAVEL_USERS_ROLE_MODEL` | `jeremykenedy\LaravelRoles\Models\Role` |
| `permissionsEnabled` | `LARAVEL_USERS_PERMISSIONS_ENABLED` | `false` |
| `permissionModel` | `LARAVEL_USERS_PERMISSION_MODEL` | `null` |
| `permissionsGate` | `LARAVEL_USERS_PERMISSIONS_GATE` | `null` |
| `showRoleLevels` | `LARAVEL_USERS_SHOW_ROLE_LEVELS` | `true` |
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
| `searchDebounceEnabled` | `LARAVEL_USERS_SEARCH_DEBOUNCE_ENABLED` | `false` |
| `searchDebounce` | `LARAVEL_USERS_SEARCH_DEBOUNCE` | `2000` |
| `tableSorting` | `LARAVEL_USERS_TABLE_SORTING` | `false` |
| `tableFiltering` | `LARAVEL_USERS_TABLE_FILTERING` | `false` |
| `emailLinks` | `LARAVEL_USERS_EMAIL_LINKS` | `false` |
| `showCreatedColumn` | `LARAVEL_USERS_SHOW_CREATED_COLUMN` | `true` |
| `showUpdatedColumn` | `LARAVEL_USERS_SHOW_UPDATED_COLUMN` | `true` |
| `showOnlineColumn` | `LARAVEL_USERS_SHOW_ONLINE_COLUMN` | `false` |
| `showLastLoginDetailsColumn` | `LARAVEL_USERS_SHOW_LAST_LOGIN_DETAILS_COLUMN` | `false` |
| `showProfileAvatar` | `LARAVEL_USERS_SHOW_PROFILE_AVATAR` | `true` |
| `profileCardColor` | `LARAVEL_USERS_PROFILE_CARD_COLOR` | `#2458b7` |
| `editCardColor` | `LARAVEL_USERS_EDIT_CARD_COLOR` | `#705000` |
| `showLastLoginColumn` | `LARAVEL_USERS_SHOW_LAST_LOGIN_COLUMN` | `false` |
| `showUserCount` | `LARAVEL_USERS_SHOW_USER_COUNT` | `false` |
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
| `access` | `LARAVEL_USERS_ACCESS` | `[]`; JSON object of optional access rules |
