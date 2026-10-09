# User settings

The settings page is optional. Existing applications keep their configuration, middleware and views until they enable it. Composer updates do not create settings tables or grant settings access.

## Enable the page

```sh
php artisan vendor:publish --tag=laravelusers-settings-migrations
php artisan migrate
```

Review the published migration before running it. `laravelusers_settings` uses `settings.connection`, or the configured user model's connection when that value is null. It does not modify the users table.

```dotenv
LARAVEL_USERS_SETTINGS_ENABLED=true
```

Define `manage-laravelusers-settings` in your application's authorization provider. It must check an existing administrator permission, policy or other application-owned authorization rule. For example, if your application already defines the `manage-user-directory` ability:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage-laravelusers-settings', function ($user) {
    return $user->can('manage-user-directory');
});
```

An undefined gate denies access. Change its name with `LARAVEL_USERS_SETTINGS_GATE`. The cog appears beside Create New User only when the signed-in user can edit settings. Missing migrations leave the page readable with disabled controls; writes return 409 until setup is complete.

## Appearance

The page controls the global avatar source, view-card base color, edit-card base color, independent gradient switches and gradient strength. Sliders range from 0 to 100. Zero removes the gradient's visual effect; 50 preserves the bundled gradient; larger values increase its highlight and shading. The small previews update before saving. Dark mode has independent view-card and edit-card base colors, gradient switches and strength sliders with the same reset controls and appearance permissions. Switching themes applies the saved palette immediately.

Reset buttons restore each color, gradient switch or strength to the application's config/environment default. They change the form first. Save settings persists the result. Individual user overrides remain unchanged.

Saved global values take precedence over config/environment values while settings are enabled. Config remains the fallback for keys that have never been saved. Disable the settings feature to return to config values while retaining the saved row. To remove saved defaults, delete the `global` row from `laravelusers_settings` through an application-owned maintenance procedure.

For individual card appearance:

```sh
php artisan vendor:publish --tag=laravelusers-appearance-migrations
php artisan migrate
```

```dotenv
LARAVEL_USERS_APPEARANCE_PER_USER=true
```

The publication contains the initial preference table, a separate additive gradient-strength migration, and a separate additive dark-appearance migration. Existing preferences survive both additive migrations. Older preference tables still support colors and gradient on/off without the new column; the strength control appears after its migration runs. Create and edit forms offer a color picker, inherited/on/off gradient, and optional individual strength. Individual Reset buttons return to the current global setting. Dark mode offers the same individual color picker, inherited/on/off gradient, strength slider and reset controls on create and edit forms, including deleted-user editing. Its fields appear after the dark-appearance migration runs. It adds nullable columns to the package preference table and never changes the host users table. Omitted fields preserve existing overrides. Soft deletion retains them; permanent Eloquent deletion removes them while the feature is enabled.

### Dark-mode defaults

All six dark-mode config values default to `null`, preserving the existing appearance until customized. An unset global dark value follows its light counterpart; an individual dark override takes precedence, followed by an explicitly set global dark value, then that user's light appearance. These defaults also work without the settings page enabled.

```dotenv
LARAVEL_USERS_PROFILE_CARD_DARK_COLOR="#19283a"
LARAVEL_USERS_PROFILE_CARD_DARK_GRADIENT=true
LARAVEL_USERS_PROFILE_CARD_DARK_GRADIENT_STRENGTH=65
LARAVEL_USERS_EDIT_CARD_DARK_COLOR="#49340e"
LARAVEL_USERS_EDIT_CARD_DARK_GRADIENT=true
LARAVEL_USERS_EDIT_CARD_DARK_GRADIENT_STRENGTH=50
```

Use `user_card_dark_color`, `user_card_dark_gradient` (`inherit`, `on`, `off`) and `user_card_dark_gradient_strength` (0 to 100) when submitting individual appearance through the existing create/update routes. Empty color or strength restores inheritance. Missing fields preserve saved values. Existing preference tables continue rendering the old appearance until the additive migration is published and applied:

```sh
php artisan vendor:publish --tag=laravelusers-appearance-migrations
php artisan migrate
```

Apply only pending migrations. Do not replace applied migrations or backfill existing users. Turning off per-user appearance uses the global palettes while retaining saved overrides.

Laravel 8 applications using SQLite need a compatible `doctrine/dbal` 3.x dependency to roll back migrations that drop columns, as described in [Laravel 8's migration documentation](https://laravel.com/docs/8.x/migrations#dropping-columns). Applying these additive migrations does not require it.

## Notifications

Alerts are the default and can be dismissed. Settings can disable dismissal. The Toast selector appears only when Laravel Toast is installed and its views are available. Without that package, notification rendering falls back to alerts. Validation errors stay inline.

Install and configure Toast with the [Artisan commands](commands.md), or enable the optional package controls below. For Bootstrap 5:

```sh
php artisan laravelusers:update --toast=install
php artisan toast:install --css=bootstrap5 --frontend=blade
php artisan laravelusers:update --notifications=toast
```

Preserve an existing Toast configuration. Use its own update command to change frameworks. Global saved notification choices take precedence over command/config defaults while settings are enabled.

Normal Toast notifications use the application session and do not require a persistent queue, worker or shared cache. Toast's optional real-time broadcasting has separate requirements. See the [Laravel Toast requirements](https://github.com/jeremykenedy/laravel-toast#requirements). Installing or removing Toast through this settings page runs Composer through the same queued package-management workflow as the roles packages; its queue and cache requirements still apply. The Artisan installation path does not need a package-management worker.

## Access rules

Role, permission and level controls appear only when the configured user model uses Laravel Roles or Spatie's trait and its required tables exist. Custom middleware and existing policies remain in effect.

| Mode | Behavior |
| --- | --- |
| Inherit | Preserve existing route, policy and middleware access. Editing settings still requires the configured settings gate. |
| Restrict | Allow a selected role, selected permission, or qualifying minimum level. An empty restriction denies access. |
| Deny | Deny the operation. |

The page has independent rules for viewing, creating, editing and deleting users; viewing, editing, restoring and permanently deleting deleted users; custom, reset, welcome and deleted-user emails; editing settings; global appearance; individual appearance; and notifications. Restrictions apply to routes, search, bulk requests, preview requests and rendered controls. Unauthorized appearance or notification fields are rejected even if submitted manually.

Spatie roles and permissions are scoped to the user's guard; team roles are also scoped to the current team. Laravel Roles minimum-level rules use its effective user level. A save that removes the current user's settings access is rejected. Account-recovery links remain subject to their own signed-token checks and confirmation flow; administrators must have the relevant action permission to include them in an email.

Editing a deleted user is available through `/users/deleted/{id}/edit` when settings and soft-delete management are enabled and `edit_deleted` is allowed. The ordinary active-user edit route continues to exclude deleted users.

## Package installation and removal

Laravel Roles is the preferred package for new integrations. Spatie and compatible custom role models remain supported. Existing integrations stay selected unless explicitly changed.

Web package management is separately disabled by default:

```dotenv
LARAVEL_USERS_SETTINGS_PACKAGES_ENABLED=true
LARAVEL_USERS_SETTINGS_PACKAGES_GATE=manage-laravelusers-packages
LARAVEL_USERS_SETTINGS_PACKAGES_QUEUE=package-maintenance
```

Define the separate gate using an existing application-dependency administration ability. Settings access alone does not grant package access:

```php
Gate::define('manage-laravelusers-packages', function ($user) {
    return $user->can('manage-application-dependencies');
});
```

Configure a persistent database, Redis, SQS or Beanstalkd queue and a shared persistent cache. Set the queue's `retry_after` above the job's 360-second timeout, such as 600 seconds. For SQS, set the queue visibility timeout above 360 seconds in its service configuration. Use a supervised worker:

- [Configure a persistent queue](https://laravel.com/docs/13.x/queues#introduction) and follow its [driver prerequisites](https://laravel.com/docs/13.x/queues#driver-prerequisites).
- [Run a queue worker](https://laravel.com/docs/13.x/queues#running-the-queue-worker), configure [timeouts](https://laravel.com/docs/13.x/queues#job-expirations-and-timeouts), and keep it running with [Supervisor](https://laravel.com/docs/13.x/queues#supervisor-configuration).
- [Configure a cache store](https://laravel.com/docs/13.x/cache#configuration) that supports [atomic locks](https://laravel.com/docs/13.x/cache#atomic-locks), including [locks across processes](https://laravel.com/docs/13.x/cache#managing-locks-across-processes).

Select your application's Laravel version in the documentation. The settings-page links select it automatically. A file cache works on one host when the web process and worker use the same cache directory; separate hosts need a shared store such as Redis or database cache.

The page checks queue storage and cache locking whenever it loads. Verified requirements show a green checkmark, retain the worker reminder after a refresh, and change the button to **Re-Verify package requirements**. Re-verifying a failed setup disables package changes until the requirements pass again. This check does not confirm that a supervised worker is running; verify that separately on every application server.

```sh
php artisan queue:work --queue=package-maintenance --timeout=360 --tries=1
```

`settings.packages.connection` optionally selects another queue connection. The worker needs Composer on its PATH and write access to the application's Composer files, vendor directory and generated package manifests. All web and queue processes must share the same application files and cache. Keep deployment-managed or read-only installations on the CLI workflow instead.

Only Laravel Toast, Laravel Roles and Spatie Laravel Permission can be managed here. Install confirmation requires the exact word `continue` and the acknowledgement checkbox. Removal requires `remove`; role removal also displays a prominent warning about authentication and authorization. Cancel or Close clears confirmation fields.

The server checks package state before enqueueing and again in the worker. Either installed roles package blocks installing the other. Removal stops while authentication models use its trait, Laravel Users enables its roles integration, restricted access rules depend on it, or application PHP files reference its namespace. Replace those references and protections before removal. Composer also refuses a removal that leaves the package required transitively.

Changes are serialized with cache locks and authorization is checked again when the worker starts. Status is visible only to the initiating user, who must still have package access. Expired queued operations are cancelled; completed jobs cannot run a second dependency change. Status polling has a separate rate limit from submission.

The worker runs Composer with plugins and scripts disabled, refreshes Laravel's generated package manifests and requests a graceful queue-worker restart. Missing configuration and the selected package's migrations are set up only when those choices are explicitly confirmed. It never drops role tables, removes published files, seeds roles, edits user-model source or automatically grants roles. Review setup instructions after installation before enabling an integration. Removing Toast leaves alert rendering available.

Back up the database and commit Composer files before changing dependencies. Avoid running deployments or other Composer processes during a web package change. Composer failures are reported without enabling the integration. If Composer changed files before discovery or restart failed, inspect those files and application logs; a failure does not promise that dependencies were rolled back. Restore the previous Composer files and run `composer install` if rollback is needed.
