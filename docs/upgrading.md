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

The modern views intentionally use their own JavaScript search and native browser deletion confirmation. They do not load legacy DataTables, tooltips, jQuery, or Bootstrap modal scripts. Pagination remains server-side. To retain those integrations, continue using Bootstrap 4 or adapt published modern views.

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

Search now handles Laravel's JSON response correctly and escapes user values before inserting them into legacy search results. With roles disabled, it no longer reads an undeclared roles relationship. With roles enabled, the existing `roles` result field remains available.

Validation uses the configured user model's table and connection for uniqueness checks. Blank edit-password fields preserve the existing password. User changes and role assignments share a database transaction. Failure of a role assignment restores previous database state on that connection; external side effects in application model observers are outside that transaction.

Authenticated self-deletion compares normalized model identifiers. Applications that deliberately disable CRUD authentication can delete users without dereferencing a missing authenticated user. Existing route names and redirects remain unchanged.
