# Artisan setup commands

Install and update use Laravel Prompts for interactive screens when it is available in the host Laravel version. The screens include a title, autocomplete choices, a selection table, publication spinners, notes and a completion message. Pressing Enter retains the current CSS selection. Older supported applications retain a Symfony Console prompt fallback. Both paths work with the existing Blade frontend and retain Bootstrap 4 unless another supported CSS framework is selected. They reject cached configuration before changing files.

The implementations are `InstallCommand`, `UpdateCommand`, `SwitchCommand`, and `PublishCommand` under `src/App/Console/Commands/`, in the `jeremykenedy\laravelusers\App\Console\Commands` namespace. The former `jeremykenedy\laravelusers\Console` classes remain available for applications that reference them directly. Update and switch share the installer's validation and file operations.

## Install

```sh
php artisan laravelusers:install
# Alias: php artisan laravel-users:install
```

Choose CSS framework, theme, view publishing, avatar source, optional Toast integration, and an optional role package. Existing choices are defaults, not a reason to reset an installed application. Main configuration is copied only when absent. Existing custom view names, parent layouts, translations, and published views are preserved.

## Update

```sh
composer update jeremykenedy/laravel-users
php artisan config:clear
php artisan laravelusers:update
# Alias: php artisan laravel-users:update
```

Update uses the same selections and publication safeguards. Without interaction or flags, it retains the current framework, theme, and role configuration. Composer does not run this command automatically.

## Switch

```sh
php artisan laravelusers:switch --css=bootstrap5
php artisan laravelusers:switch --css=tailwind --theme=system
php artisan laravelusers:switch --roles=none
# Alias: php artisan laravel-users:switch
```

Switch is flag-based and never asks questions. Supply a framework, theme, views, roles, avatar, Toast, or notification option. It uses the same configuration and view-preservation rules as install and update.

## Roles

Laravel Roles is the preferred integration for new setups. Keep remains the default so existing custom or Spatie integration is preserved. The commands refuse to install a second roles package while another is installed; complete the host changes and removal first.

See [roles and permissions](roles.md) for traits, middleware, migrations, guards, teams, and direct permission selection.

## Options

| Option | Values or behavior |
| --- | --- |
| `--framework=` | `bootstrap4`, `bootstrap5`, `tailwind`. |
| `--css=` | Alias for framework. Conflicting flags fail without writing files. |
| `--frontend=` | `blade`; native SPA view sets are not bundled. |
| `--theme=` | `light`, `dark`, `system`. |
| `--views=package` | Keep view-loader behavior. Published overrides still take precedence. |
| `--views=publish` | Add missing package views; keep existing ones. |
| `--force` | Requires `--views=publish`. Back up the entire published view tree, then replace package view files. |
| `--roles=` | `keep`, `none`, `laravel-roles`, `spatie`. |
| `--role-middleware=` | Restriction for an explicitly selected role package; semicolons separate multiple middleware. |
| `--install-roles` | Explicitly install the selected missing role dependency with Composer. |
| `--setup-packages` | Create dedicated package-change queue storage and cache locks. Start the printed worker command separately. |
| `--setup-integrations` | Publish missing configuration for explicitly selected optional packages. |
| `--migrate-integrations` | Run only the selected optional package migrations. |
| `--setup-accounts` | Publish the optional account, avatar, and appearance migrations without enabling the account page. |
| `--avatar=` | `keep` or a supported [avatar source](avatars.md); Keep preserves the current choice. |
| `--install-avatars` | Install the official local DiceBear libraries when selecting DiceBear. PHP 8.2 or newer is required. |
| `--toast=` | `keep`, `install`, `remove`; changing the Composer dependency requires an explicit choice. |
| `--notifications=` | `alert` or `toast`; Toast must already be installed and configured. |
| `--with=` | Print setup instructions for optional UI Kit, Toast, Darkmode Toggle, IP Capture, or Seedster. Repeat as needed. |
| `--no-interaction` | Keep current choices or use explicit flags, with no prompts. |

Framework and theme selections live in `config/laravelusers-ui.php`. Role selections use `config/laravelusers-roles.php` only when explicitly changed. These files retain environment fallbacks. Avatar choices use `laravelusers-avatar.php`; notification defaults use `laravelusers-notifications.php`. Environment variables take precedence over command selections. Clear and rebuild configuration caches during deployment.

## Publishing

The standalone package publisher is available as `laravelusers:publish` or `laravel-users:publish`. It uses Laravel's `laravelusers` publish group for configuration, views, and translations, then publishes versioned public assets. Existing host files are retained by default.

| Command | Files |
| --- | --- |
| `php artisan laravelusers:publish` | Main config, all views, translations and versioned public assets. |
| `php artisan vendor:publish --tag=laravelusers` | Main config, all views, and translations. |
| `php artisan vendor:publish --tag=laravelusers-settings-migrations` | Optional global settings storage. |
| `php artisan vendor:publish --tag=laravelusers-appearance-migrations` | Optional individual card colors, gradients and gradient strength. |
| `php artisan vendor:publish --tag=laravelusers-avatar-migrations` | Optional per-user avatar preferences on the user model connection. |
| `php artisan vendor:publish --tag=laravelusers-email-views` | Welcome, reset, custom-message, and deleted-user email templates. |
| `php artisan vendor:publish --tag=laravelusers-activity-migrations` | Optional latest-login activity table migration. |
| `php artisan vendor:publish --tag=laravelusers-account-links-migrations` | Optional deleted-account link table migration. |
| `php artisan laravelusers:prune-account-links` | Remove expired account links without changing users. |
| `php artisan laravelusers:setup-accounts --migrate` | Publish and run only the optional account, avatar, and appearance migrations. Feature switches remain unchanged. |
| `php artisan laravelusers:setup-package toast` | Configure an installed optional package; accepts `toast`, `laravel-roles`, or `spatie`, `--framework`, and an explicit `--migrate` flag. |
| `php artisan laravelusers:prune-deleted` | Permanently remove eligible soft-deleted users only when cleanup is explicitly enabled. |

Laravel's `vendor:publish --force` overwrites files without the setup commands' backup step. Use the reviewed update workflow for customized views. Composer updates never run migrations. Database setup requires one of the explicit setup or migration options above; no command seeds roles or grants administrators.

Install, update, switch and publish export the bundled styles and directory script to `public/vendor/laravelusers/`. Each release uses content hashes. Files are staged and verified under an exclusive filesystem lock; `manifest.json` is replaced only after the release is complete. Existing asset URLs remain available for pages loaded before an update. Custom files in that directory are retained.

Modern views use published assets when their hashes match the installed package. They use the bundled inline fallback when assets are absent, outdated or damaged, so an ordinary Composer update does not leave existing pages without styles or scripts. Bootstrap 4 retains its existing script stack. Bootstrap CDN settings and host assets remain separate from the package assets.

The commands require write access to the package's public directory and `storage/app/laravelusers`. Run them during deployment before making a release read-only. Consuming applications do not need a Node build for these bundled assets. Run `npm run build` in your application after changing its own asset sources or CSS imports. Custom Vite layouts can use `@vite` and disable the package's `enableAppCss` and `enableAppJs` settings.

Install, update and switch parse an existing `routes/web.php` with PHP-Parser and add the [impersonation-state middleware](impersonation.md). Existing route groups, aliases, comments and dynamic configuration are preserved. The generated entries check that the middleware class exists, so rolling back to a package version without that class leaves them inactive. Invalid PHP or a concurrent route edit stops the command. Applications without that file use the service provider's middleware registration; no route stub is generated. The standalone publish command does not edit routes, Vite configuration, Tailwind configuration or dependency manifests.

## Recovery

A forced view update reports a unique backup directory under `storage/app/laravelusers/backups`. Restore your overridden files from that directory if needed, then clear the view cache. To undo a CSS selection, switch back to Bootstrap 4. To undo a role selection, remove the sidecar role config or explicitly select the desired integration; do not remove role tables or assignments as part of rollback.

## Toast and avatar examples

```sh
php artisan laravelusers:update --avatar=ui-avatars
php artisan laravelusers:update --avatar=dicebear --install-avatars
php artisan laravelusers:update --toast=install
php artisan toast:install --css=bootstrap5 --frontend=blade
php artisan laravelusers:update --notifications=toast
php artisan laravelusers:update --toast=remove
```

Toast needs PHP 8.2 and Laravel 10 or newer. Installation prints the follow-up commands because the newly installed provider is not booted in the running process. Existing Toast configuration is retained. Removal returns the package's notification default to alerts and leaves published files in place. Review other application consumers before removing a shared dependency. Saved [global settings](settings.md) override command notification defaults while the settings page is enabled.

Web-based package controls are a separate opt-in with a dedicated gate, persistent queue, cache locks and typed confirmations. They do not replace the CLI workflow or run migrations. See [package management requirements](settings.md#package-installation-and-removal).
