# Upgrading Laravel Users

## Before updating

Keep a copy of application configuration and published views. Run the application's own tests against the package update. This package cannot test every customization in consuming applications.

Composer updates retain Bootstrap 4 unless you have explicitly selected another framework. No Composer scripts publish views or change host configuration. Existing published Bootstrap 4 views continue to override bundled Bootstrap 4 views.

## Selecting a modern frontend

1. Clear the host configuration cache if enabled.
2. Run `php artisan laravelusers:update --framework=bootstrap5` or use `tailwind`.
3. Choose a default theme and whether to publish views.
4. Check custom layouts and assets. Remove duplicate framework stylesheets from the layout if needed.
5. Run host application tests and rebuild the configuration cache.

Modern views live under `laravelusers::modern`. They share form and page partials across Bootstrap 5 and Tailwind. They do not rewrite existing Bootstrap 4 view overrides. Explicitly configured custom view names still win, even if you select a modern framework.

Modern views use their own search and accessible confirmation modals. They provide optional table sorting, column filters, persistent column visibility, and a mobile entry layout without jQuery or legacy DataTables. Bootstrap 4 retains its existing modal and DataTables integrations. Pagination remains server-side. Table controls act on displayed rows; use search for matches across pages or disable pagination for a complete in-memory table.

The theme control uses Laravel Logger's icon button and cycles through light, dark, and system modes. Set `laravelusers.themeToggle` to `false` to hide it and ignore saved browser preferences; the configured `theme` still applies. Published layouts can keep their existing select control, which remains supported by the theme script, or adopt the updated theme partial and styles.

## Updating published views

`--views=publish` adds missing files. Existing files remain unchanged. `--views=publish --force` first copies the complete published view directory to a uniquely named directory under `storage/app/laravelusers/backups`. It then replaces files provided by this package, keeping unrelated custom files. Main configuration and translations are preserved.

Review and merge translated strings yourself. New modern interface labels are in `src/resources/lang/en/ui.php` and use Laravel's configured fallback locale. Set an English fallback or provide a translated `ui.php` in your locale. Existing translations continue to work.

Direct `vendor:publish --force` is Laravel's original overwrite operation and does not use the new command's backup behavior.

## Returning to Bootstrap 4

```sh
php artisan config:clear
php artisan laravelusers:update --framework=bootstrap4 --theme=light --no-interaction
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

Published overrides need the shared table, date, avatar, selection, and column scripts to use these features. Use the backed-up publication workflow or merge those partials into your own views. Missing dates now remain blank. Dates display in the browser's timezone and the footer distinguishes the current page from the total count.

Welcome email and password setup options are unchecked by default. Password setup uses the host password broker and `password.reset` route, requires a welcome email, and stores an unusable random password until the user follows the reset link. No password is emailed. Configure mail and queue workers before using welcome notifications.

The deleted table requires the host model's `SoftDeletes` trait and existing `deleted_at` column. Enabling the package option does not modify the users table. Restoring a user keeps their login history; permanent deletion removes activity records.
