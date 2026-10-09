# Upgrading Laravel Users

New features remain opt-in. Package email actions, creation welcome mail, password meters and matching feedback, search debounce, browser date localization, responsive buttons, additional login columns, and user counts are disabled by default. The settings page, individual account page, package management, account links, activity tracking, avatars, table controls, and automatic cleanup also require explicit enablement. Existing configuration keys keep their released defaults. Composer updates do not run the optional migrations or change the selected frontend.

## Before updating

Keep a copy of application configuration and published views. Run the application's own tests against the package update. This package cannot test every customization in consuming applications.

Composer updates retain Blade and Bootstrap 4 unless you have explicitly selected another runtime or CSS framework. No Composer scripts publish views or change host configuration. Existing published Bootstrap 4 views continue to override bundled Bootstrap 4 views.

## Version transitions

Version 6.0.0 is prepared for 2026-10-09 and remains unreleased pending final runtime verification. Compare your installed version in `composer.lock` with the published target before changing the host application. The [changelog](changelog.md) indexes every historical tag and links the canonical release notes.

| From | To | What to review |
| --- | --- | --- |
| Before 4.0 | 4.0 | Multiple roles in the interface and additional languages. Merge role form overrides and translations; retain assigned role IDs. |
| 4.1.x | 4.2 | Laravel 8 support. Update the host framework using Laravel's upgrade guide before testing its auth, user model, and role integration. |
| 4.2 | 4.3 | PHP 8 compatibility. Check the host and HTML/form dependencies together; published form overrides still use the dependencies from that release. |
| 4.3 | 4.4 | Laravel 9 compatibility changes. Review the installed PHP/framework constraints and the role package version before resolving Composer. |
| 4.4 | 4.5 | HTML/form dependency changed to `laravellux/html`. Update any custom form overrides and the host's provider/alias setup consistently. |
| 4.5 | 5.0 | PHP 8.4 support and removal of the package's `laravellux/html` requirement. Version 5 requires PHP 8.1 or newer. Hosts with published `Form::` views must keep their own compatible HTML package until those views are converted. |
| 5.0 | 6.0 | Bootstrap 4 and original routes/config remain the defaults. Review the new view partials, strict text validation, opt-in tables, email controls, and dependency matrix below. |

The historical transition notes are based on the [published releases](https://github.com/jeremykenedy/laravel-users/releases) and their tagged Composer requirements. For older patch releases with no published notes, inspect the [tag comparison](https://github.com/jeremykenedy/laravel-users/compare) and your local overrides rather than assuming they share the next minor version's changes.

Do not upgrade a PHP 7 host directly to the current package. Resolve the host's PHP and Laravel upgrade first. The current tested floor is PHP 8.1 and Laravel 8; optional packages may require newer versions. Keep a compatible older package version until the host is ready.

## Changes from 5.0

| Area | Default and migration impact |
| --- | --- |
| Frontend | Blade, Bootstrap 4 and published overrides remain. Bootstrap 5 requires an explicit selection. Other frameworks are deferred. No Composer hook switches frameworks. |
| Configuration | All 36 original options and their defaults remain. New nested settings merge with bundled defaults; existing application config is preserved. Environment fallback calls must be merged into older published files if desired. |
| Search | Fixes decoded/text JSON handling from issue #90 and escapes inserted values. Optional debounce, sorting, filtering, and column controls apply to bundled views. |
| Validation | Create retains its six-character minimum and no default maximum. Edit retains its six-to-twenty limits and blank-password behavior. HTML usernames are rejected. Optional stronger rules apply to both forms. |
| Activity | Latest login storage and online presence remain off until enabled. Login capture needs its separate migration. No user columns are added. |
| Avatars | The avatar column and per-user preferences remain opt-in. Per-user preferences need their separate migration and existing users inherit global settings. |
| Deleted users | Uses the host's existing SoftDeletes implementation. Separate table views and bulk controls do not add a deleted_at column or change deletion behavior. |
| Email | Email features remain off by default. Creation welcome/password setup and deleted-account links are unchecked. Automatic goodbye notices require explicit configuration. The optional settings page can save global welcome availability and template defaults, subject to the existing email flags. Queue, broker, route, and authorization are host-owned. |
| Account links | Off by default; requires a separate migration. Encrypted, action-bound links require confirmation and are single use. Never expire is an explicit unchecked choice and can be disabled. |
| Roles | Existing integration stays unchanged. Optional Spatie support and installer choices do not install or migrate anything silently. Direct permissions are separately opt-in. Shared role levels stay host-owned. |
| Notifications/packages | Inline alerts remain the default. Explicit Toast installation completes setup automatically and retains existing notification settings. Web dependency changes require a dedicated gate, verified worker and typed confirmation. |
| Documentation/CI | New guides, current screenshots, theme-aware graphics, optional-role jobs, and browser/accessibility coverage. No release is published automatically. |

## Selecting a modern frontend

1. Clear the host configuration cache if enabled.
2. Run `php artisan laravelusers:update --frontend=blade --css=bootstrap5`; keep Bootstrap 4 with `--css=bootstrap4`.
3. Choose a default theme and whether to publish views.
4. Check custom layouts and assets. Remove duplicate framework stylesheets from the layout if needed.
5. Run host application tests and rebuild the configuration cache.

Install, update, switch and the standalone publisher now export versioned assets to `public/vendor/laravelusers/`. Run `php artisan laravelusers:update --no-interaction` as part of deployment to refresh them while retaining current settings. Unpublished or stale assets retain the bundled fallback. Allow writes to the package public directory and `storage/app/laravelusers` during this step. Host Vite and Tailwind configuration is retained.

The setup commands use PHP-Parser to add the impersonation-state guard to an existing `routes/web.php`. Review the route diff before rebuilding route caches. These entries are conditional on the middleware class being available, so an older package can still load the file after rollback. Invalid host PHP or a route file changed during setup stops the command. Impersonation remains disabled unless explicitly enabled; [the guide](impersonation.md) covers authorization, expiration and application listeners.

The optional Bootstrap 5 Blade views live under `laravelusers::modern`. Custom published templates and explicitly configured view names still win. Other CSS frameworks and application runtimes are deferred on the [roadmap](roadmap.md); no delivery order or dates are assigned.

Modern views use their own search and accessible confirmation modals. They provide optional table sorting, column filters, persistent column visibility, and a mobile entry layout without jQuery or legacy DataTables. Bootstrap 4 retains its existing modal and DataTables integrations. Pagination remains server-side. Table controls act on displayed rows; use search for matches across pages or disable pagination for a complete in-memory table.

The theme control uses Laravel Logger's icon button and cycles through light, dark, and system modes. Set `laravelusers.themeToggle` to `false` to hide it and ignore saved browser preferences; the configured `theme` still applies. Published layouts can keep their existing select control, which remains supported by the theme script, or adopt the updated theme partial and styles.

## Updating published views

`--views=publish` adds missing files. Existing files remain unchanged. `--views=publish --force` first copies the complete published view directory to a uniquely named directory under `storage/app/laravelusers/backups`. It then replaces files provided by this package, keeping unrelated custom files. Main configuration and translations are preserved.

Review and merge translated strings yourself. New modern interface labels are in `src/resources/lang/en/ui.php` and use Laravel's configured fallback locale. Set an English fallback or provide a translated `ui.php` in your locale. Existing translations continue to work.

Direct `vendor:publish --force` is Laravel's original overwrite operation and does not use the new command's backup behavior.

## Returning to Bootstrap 4

```sh
php artisan config:clear
php artisan laravelusers:update --frontend=blade --css=bootstrap4 --theme=light --no-interaction
```

Existing Bootstrap 4 overrides will be used again. To undo forced publication, copy the affected files from the backup path reported by the command. Rebuild configuration and view caches as required by your deployment.

## Compatibility fixes

Login capture and online status are opt-in. Missing `activity` settings retain disabled defaults, so published configurations do not need immediate changes. Login capture uses the separately published `laravelusers-activity-migrations` tag; install, update, switch, and Composer do not run migrations. See [activity setup](activity.md) before enabling tracking. Merge the activity partials into published list and detail views, or use the backed-up publication workflow after reviewing your customizations.

Search now handles Laravel's JSON response correctly and escapes user values before inserting them into legacy search results. With roles disabled, it no longer reads an undeclared roles relationship. With roles enabled, the existing `roles` result field remains available.

For the search failure reported in [issue #90](https://github.com/jeremykenedy/laravel-users/issues/90), the bundled search script accepts both an already-decoded array and legacy JSON text. `enableSearchUsers=false` hides the bundled search form. Published overrides still take precedence: merge changes from `scripts/search-users.blade.php` and `usersmanagement/show-users.blade.php`, or review the backed-up publication option above. Clear the host view and configuration caches after updating overrides or configuration.

The `css/app.css` and `js/app.js` URLs are host assets, not package files. Disable `enableAppCss` and `enableAppJs` when those public paths do not exist. Applications using Vite can load `@vite` assets in their own parent layout; the package does not assume that every host uses Vite or change existing asset defaults.

Validation uses the configured user model's table and connection for uniqueness checks. Blank edit-password fields preserve the existing password. User changes and role assignments share a database transaction. Failure of a role assignment restores previous database state on that connection; external side effects in application model observers are outside that transaction.

Authenticated self-deletion compares normalized model identifiers. Applications that deliberately disable CRUD authentication can delete users without dereferencing a missing authenticated user. Existing route names and redirects remain unchanged.

## Interface options

All existing config keys remain available. New options use environment fallbacks in the bundled config; published config files are preserved. Merge the `env()` calls into older published configuration if you want environment overrides there. See [configuration](configuration.md) for the complete mapping.

Published overrides need the shared table, date, avatar, selection, and column scripts to use these features. Use the backed-up publication workflow or merge those partials into your own views. Missing dates remain blank except Last login, which displays No logins. Dates display in the browser's timezone and the footer distinguishes the current page from the total count.

Welcome email and password setup options are unchecked by default. Password setup uses the host password broker and `password.reset` route, requires a welcome email, and stores an unusable random password until the user follows the reset link. No password is emailed. Configure mail and queue workers before using welcome notifications.

The deleted table requires the host model's `SoftDeletes` trait and existing `deleted_at` column. Enabling the package option does not modify the users table. Restoring a user keeps their login history; permanent deletion removes activity records.

## Optional tables and deployment

Publish only the migrations for features you choose:

```sh
php artisan vendor:publish --tag=laravelusers-activity-migrations
php artisan vendor:publish --tag=laravelusers-avatar-migrations
php artisan vendor:publish --tag=laravelusers-account-links-migrations
```

Review the generated migrations and database connections, then run your normal migration process. Composer never runs them. Install, update, and switch retain this behavior unless an explicit database setup option is supplied. Activity uses its configured connection; avatar preferences and account links use the configured user model's connection. Enable each matching feature only after its table is available. No migration alters or backfills the host users table.

For role-package changes, clear cached config, run update with the selected package, follow its trait and migration instructions, then rerun update in a fresh process. Keep custom implementations with `--roles=keep`. Review the role middleware before enabling access restrictions; the installer does not create or promote administrators. See [role setup](roles.md).

Before deployment, run the full host test suite and package compatibility suite, lint, build any host frontend assets, and check active/deleted lists, search, role editing, theme controls, and email preview in the browser. Send a real test email through your application's configured transport to verify delivery. Rebuild config/view caches and restart queue workers after deploying templates or notification changes.

For rollback, restore the previous package constraint and lock file, restore any overwritten views from the reported backup, and return generated UI/role selections to their previous values. Disable optional features before removing their tables. Keep preference, activity, and account-link tables if their data must survive a temporary rollback. Rotating APP_KEY invalidates encrypted links and captured encrypted IP values; it is not a normal package-upgrade step.

## Settings, card appearance and optional package management

These additions remain opt-in. Publish `laravelusers-settings-migrations` only for persistent global settings, and `laravelusers-appearance-migrations` only for individual card appearance. The latter contains separate gradient-strength, dark-appearance and gradient-highlight migrations for applications that already have the preference table. These add nullable columns to the package preference table and do not change the users table. Older tables still render and save their existing settings; new controls appear only when their columns exist. Dark config values default to null and inherit the light palette until changed. Light highlight colors default to white, preserving the previous gradient. Existing callers omitting fields retain their saved preferences. Run unapplied migrations; do not replace or rerun an applied migration. Existing users inherit config/global choices without a backfill.

Define the settings gate before enabling the page. Review role/permission restrictions using an administrator and a restricted account. Global saved settings override config while enabled; individual saved colors/strength override global appearance. Reset buttons restore defaults in the form and require a save. Package controls also need their separate gate, asynchronous queue and shared cache. Keep them disabled on read-only deployments. See [settings](settings.md).

The 240px name/email content limit prevents long values from widening table columns. Full values remain scrollable and their links remain intact. Set `LARAVEL_USERS_TABLE_TEXT_MAX_WIDTH=0` for the previous unlimited behavior. Gradients keep their existing appearance at strength 50; colors retain their existing defaults.

For local avatars, choose UI Avatars without installing a dependency, or explicitly install DiceBear on PHP 8.2 or newer. Generated image resolution is separate from display size. Remote styles remain explicit choices. Existing source overrides and published forms remain valid.

Standalone navigation components are additive. Add the documented script stack in your host layout and use unique theme-toggle IDs. Existing package header and layout partials remain available. [Navigation components](navigation-components.md) covers replacing only your own header controls.

Custom emails now use the Laravel Markdown mail layout. Published email overrides still take precedence. Review those overrides when adopting the new styled preview. Closing the modal now clears drafts; Back to editing continues preserving them.
