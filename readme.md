<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="art/banner-dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="art/banner-light.svg">
        <img src="art/banner-light.svg" alt="Laravel Users" width="800">
    </picture>
</p>

<p align="center">Manage Laravel user accounts with configurable access, views, roles, email, avatars, and account settings.</p>

<p align="center">
    <a href="https://packagist.org/packages/jeremykenedy/laravel-users"><img src="https://poser.pugx.org/jeremykenedy/laravel-users/d/total.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/jeremykenedy/laravel-users"><img src="https://poser.pugx.org/jeremykenedy/laravel-users/v/stable.svg" alt="Latest Stable Version"></a>
    <a href="https://github.com/jeremykenedy/laravel-users/actions/workflows/tests.yml"><img src="https://github.com/jeremykenedy/laravel-users/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
    <a href="https://github.styleci.io/repos/83162309"><img src="https://github.styleci.io/repos/83162309/shield?branch=master" alt="StyleCI"></a>
    <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT"></a>
</p>

<p align="center">
    <a href="https://www.codefactor.io/repository/github/jeremykenedy/laravel-users"><img src="https://www.codefactor.io/repository/github/jeremykenedy/laravel-users/badge" alt="CodeFactor"></a>
</p>

<p align="center">
    <a href="https://github.com/jeremykenedy"><img src="https://img.shields.io/github/followers/jeremykenedy?label=Follow&amp;style=social" alt="Follow Jeremy Kenedy on GitHub"></a>
    <a href="https://github.com/jeremykenedy/laravel-users"><img src="https://img.shields.io/github/stars/jeremykenedy/laravel-users?label=Star%20this%20repo&amp;style=social" alt="Star Laravel Users on GitHub"></a>
    <a href="https://github.com/sponsors/jeremykenedy" title="Sponsor jeremykenedy"><img src="https://img.shields.io/badge/Sponsor-jeremykenedy-ea4aaa?logo=githubsponsors&amp;logoColor=white" alt="Sponsor jeremykenedy"></a>
</p>

## Table of Contents

- [Framework Support](#framework-support)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Features](#features)
- [Configuration](#configuration)
- [Changing Frameworks](#changing-frameworks)
- [Artisan Commands](#artisan-commands)
  - [Install Options](#install-options)
- [Routes](#routes)
- [Optional Integrations](#optional-integrations)
  - [Packages That Work Together](#packages-that-work-together)
  - [Related Package](#related-package)
- [Project Layout](#project-layout)
- [Testing](#testing)
- [Documentation](#documentation)
- [Screenshots](#screenshots)
  - [Mobile](#mobile)
  - [Tablet](#tablet)
  - [Desktop](#desktop)
- [License](#license)

## Framework Support

This release supports Blade with Bootstrap 4 and Bootstrap 5. Bootstrap 4 remains the default for existing applications. Switching CSS preserves routes, authentication, models, configuration, and published custom views.

| CSS framework | Blade | Availability |
| --- | --- | --- |
| Bootstrap 4, legacy | Default | Included |
| Bootstrap 5.3 | Optional | Included |

Only Blade and the two Bootstrap choices are offered by the release installer. Deferred CSS frameworks and application runtimes are tracked separately in the [roadmap](docs/roadmap.md), with release dates and order still to be determined.

The package's documented compatibility suite covers Laravel 8 through 13, with framework and PHP versions paired in the [CI matrix](.github/workflows/tests.yml). Older Laravel applications should use a package version compatible with their framework and PHP runtime. See [framework setup and commands](docs/commands.md).

## Requirements

- PHP 8.1 or newer.
- Laravel 8 or newer, with compatibility validated against the versions in the CI matrix.
- An existing application user model, users table, and authentication guard.
- Composer.

The package does not create or alter the host users table, add an authentication system, or grant administrator access. Optional features may require their own published migrations, queue worker, or cache configuration. Those features are disabled by default.

## Installation

Install the package and run its setup command:

```sh
composer require jeremykenedy/laravel-users
php artisan laravelusers:install
```

The installer keeps Blade and Bootstrap 4 as the defaults, detects existing package configuration through the current settings, and preserves existing configuration and published views. It offers settings for Bootstrap CSS, theme, view publishing, avatars, notifications, and optional integrations. Use `--no-interaction` for scripted setup. Review the [installation and update guide](docs/commands.md) before enabling optional migrations or package changes.

The original publish command remains available:

```sh
php artisan vendor:publish --tag=laravelusers
```

The package also provides `php artisan laravelusers:publish`, with `laravel-users:publish` as an alias. It publishes configuration, translations, views, and versioned assets in `public/vendor/laravelusers/` while preserving existing host files. Install and update also publish these assets. The bundled templates do not depend on Laravel Collective HTML. If you have custom published views, check them for their own form-builder calls before removing a host application's dependency.

## Quick Start

The default setup preserves Blade and Bootstrap 4:

```sh
php artisan laravelusers:install --frontend=blade --css=bootstrap4 --no-interaction
```

To use Bootstrap 5:

```sh
php artisan laravelusers:install --frontend=blade --css=bootstrap5 --no-interaction
```

Sign in with an account authorized by the host application's middleware, then open `/users`. Existing installations can use `laravelusers:update` with the same flags. For application-owned navigation, link to the package route:

```blade
<a href="{{ route('users') }}">Manage users</a>
```

See [standalone navigation components](docs/navigation-components.md) for the Blade controls that can be embedded in a host layout, and the [roadmap](docs/roadmap.md) for future frontend work.

## Features

- Create, view, edit, search, sort, filter, and delete users with paginated table or card layouts.
- Preserve Bootstrap 4 and Blade by default, with Bootstrap 5 available through an explicit choice.
- Keep soft-deleted users in a separate list, with authorized restore and permanent-delete actions.
- Restrict management routes and individual actions with host middleware, gates, roles, permissions, and supported role levels.
- Integrate optionally with Laravel Roles, Spatie Laravel Permission, and compatible custom role models.
- Track latest login time, IP address, device, operating system, browser, and recent online status when enabled.
- Select global or per-user avatar sources, including initials, local generators, Gravatar, DiceBear, and host-provided images.
- Send customizable welcome, password reset, account recovery, goodbye, and custom emails, individually or in bulk.
- Set global welcome-email defaults and enable or disable welcome actions from the administrator settings page.
- Preview email content before sending and retain edits when returning to the composer.
- Configure password strength rules and confirmation feedback for account creation and editing.
- Customize independent light/dark card colors, gradient highlight colors and strength, with inherited per-user overrides.
- Choose inline alerts, installed Laravel Toast notifications, or both, with configurable Toast behavior.
- Preview unsaved notification choices in the browser before saving settings.
- Use consistent page icons, headers, tabs, and content width across the Bootstrap interfaces.
- Install, configure, and remove supported optional packages through authorized settings controls with worker verification and visible progress.
- Add a signed-in account page with profile, avatar, appearance, email, password, and account-removal controls.
- Use the theme toggle and user menu as standalone Blade components.
- Impersonate another account with role-based authorization, expiring sessions, verified state, and an exit control when explicitly enabled.
- Keep new account settings, cleanup, activity, and package-management features disabled unless explicitly enabled.

See [configuration](docs/configuration.md) and the individual feature guides for defaults, requirements, and upgrade notes.

## Configuration

The package configuration is [src/config/laravelusers.php](src/config/laravelusers.php). Existing installations retain Bootstrap 4 and existing option defaults. Every configuration option supports its documented `LARAVEL_USERS_*` environment variable fallback. The detailed configuration reference is maintained in [docs/configuration.md](docs/configuration.md).

| Setting | Default | Purpose |
| --- | --- | --- |
| `frontend` | `bootstrap4` | Preserve the original bundled views and styles. |
| `runtime` | `blade` | Preserve server-rendered screens unless explicitly changed. |
| `settings.enabled` | `false` | Keep the global settings page opt-in. |
| `emails.enabled` | `false` | Keep package email actions off until configured. |
| `welcome.enabled` | `false` | Require an administrator or environment opt-in for welcome actions. |
| `activity.login`, `activity.online` | `false` | Leave login history and online presence disabled. |
| `account.enabled`, `cleanup.enabled` | `false` | Keep end-user account controls and automatic cleanup disabled. |

The settings page is separately enabled with a migration and a host-defined authorization gate. Optional activity, account settings, per-user avatars, per-user appearance, deleted-account links, cleanup, Toast notifications, and managed package changes each have separate requirements. Do not enable them before reviewing [settings and access rules](docs/settings.md), [activity](docs/activity.md), and [integrations](docs/integrations.md).

## Changing Frameworks

Bootstrap 4 remains the default. Use `laravelusers:update` for interactive setup or explicit flags. It preserves the main config file and existing custom view overrides:

```sh
php artisan config:clear
php artisan laravelusers:update
php artisan laravelusers:update --frontend=blade --css=bootstrap5 --theme=system --no-interaction
php artisan config:cache
```

For a direct change without prompts, use `laravelusers:switch`:

```sh
php artisan laravelusers:switch --css=bootstrap4
php artisan laravelusers:switch --framework=bootstrap4 --theme=light
```

| Option | Values | Purpose |
| --- | --- | --- |
| `--framework` / `--css` | `bootstrap4`, `bootstrap5` | Select the CSS framework. |
| `--frontend` | `blade` | Keep the Blade screen runtime for this release. |
| `--theme` | `light`, `dark`, `system` | Set the initial package theme. |
| `--views` | `package`, `publish` | Use bundled views or publish missing view files. |
| `--force` | flag | Back up and replace published package views; only valid with `--views=publish`. |

The commands publish compiled package CSS and runtime assets automatically. Run `npm run build` after switching if your host application imports or modifies its own assets; the bundled screens do not require a host Node build. Existing host overrides take precedence. Read [upgrading and rollback](docs/upgrading.md) before replacing published views.

## Artisan Commands

| Command | Purpose | Options |
| --- | --- | --- |
| `laravelusers:install` | Configure the package and select optional integrations. | [Setup options](#install-options) and [integration flags](docs/commands.md) |
| `laravelusers:update` | Refresh view choices and optional integration setup while preserving config. | Same setup flags as install |
| `laravelusers:switch` | Apply explicit runtime, CSS, theme, view, avatar, role, or notification choices. | Same setup flags as install; pass choices explicitly |
| `laravelusers:publish` | Publish configuration, views, translations and versioned public assets; `laravel-users:publish` is an alias. | No package-specific flags |
| `laravelusers:setup-accounts` | Publish optional account, avatar, and appearance migrations. | `--migrate` |
| `laravelusers:setup-package` | Configure an installed Toast or roles package. | `package` argument, `--framework`, `--migrate` |
| `laravelusers:prune-deleted` | Permanently remove soft-deleted users when scheduled cleanup is enabled. | No package-specific flags |
| `laravelusers:prune-account-links` | Remove expired account-link records. | No package-specific flags |

Install, update, and switch share flags for runtimes, CSS, views, themes, role package selection, avatars, Toast, and optional setup tasks. Both `laravelusers:*` and `laravel-users:*` spellings are available for those commands. These three commands publish versioned assets and register the impersonation-state guard in an existing host web routes file. The full option list, publishing safeguards, queue setup, and safe removal steps are in [Artisan commands](docs/commands.md).

### Install Options

| Option | Values | Purpose |
| --- | --- | --- |
| `--framework` / `--css` | `bootstrap4`, `bootstrap5` | Select the CSS framework. |
| `--frontend` | `blade` | Select the screen runtime supported by this release. |
| `--theme` | `light`, `dark`, `system` | Set the initial theme. |
| `--views` | `package`, `publish` | Use bundled views or publish missing templates. |
| `--force` | flag | Back up and replace published views when `--views=publish` is selected. |
| `--no-interaction` | flag | Use current settings or explicit choices without prompts. |

Optional package installation and migration flags are documented in the [complete command reference](docs/commands.md).

## Routes

The package provides `/users` for the directory, `/users/deleted` for soft-deleted accounts, `/users/settings` for optional global settings, and `/users/account` for the signed-in account page. Account recovery links use `/users/account-link/{token}` when enabled. The complete route names, methods, middleware, and feature requirements are in [docs/routes.md](docs/routes.md).

Management routes use authentication and the configured package middleware. Authentication by itself does not grant administrator authorization. Configure a role middleware, a gate, or explicit access rules before exposing user management to ordinary signed-in accounts. The package prevents deleting the current authenticated user through its management actions.

## Optional Integrations

The package has no required Laravel Collective HTML or roles package dependency. Supported optional integrations include [Laravel Roles](https://github.com/jeremykenedy/laravel-roles), [Spatie Laravel Permission](https://github.com/spatie/laravel-permission), [Laravel Toast](https://github.com/jeremykenedy/laravel-toast), local DiceBear libraries, and host-provided avatar, UI, dark-mode, IP-capture, and seed services.

The install and update commands can show setup instructions and configure supported optional packages. The settings page can manage package installation or removal only when the queue, worker, cache, and authorization requirements are met. A second roles package cannot be installed alongside an already detected roles integration. Read [integrations](docs/integrations.md), [roles](docs/roles.md), and [package settings](docs/settings.md) before making dependency changes.

### Packages That Work Together

Laravel Users fits into a collection of focused Laravel packages. Choose the parts your application needs; all of the integrations below are optional.

| Package | What it brings to your application | How Laravel Users uses it |
| --- | --- | --- |
| [Laravel Toast](https://github.com/jeremykenedy/laravel-toast) | Configurable toast notifications with positioning, animations, progress, stacking, and dismissal controls. | Renders user-management notices through Toast or alongside inline alerts. The settings UI configures its behavior and previews unsaved options; explicit installation completes Toast setup automatically. |
| [Laravel Roles](https://github.com/jeremykenedy/laravel-roles) | Roles, permissions, levels, and middleware for application access control. | The preferred integration for new role setups, with role assignment, optional direct permissions, and role/permission/level access rules. Existing role systems remain supported. |
| [Laravel UI Kit](https://github.com/jeremykenedy/laravel-ui-kit) | Shared UI components for application-owned interfaces. | Setup guidance for hosts that use its components in custom or published views; selecting it does not replace the bundled screens. |
| [Laravel Darkmode Toggle](https://github.com/jeremykenedy/laravel-darkmode-toggle) | A theme control for your host application's interface. | An alternative to the built-in theme button when your layout already manages light and dark themes. Synchronize the package theme with your host control. |
| [Laravel IP Capture](https://github.com/jeremykenedy/laravel-ip-capture) | IP capture for host user models. | Setup guidance for application-owned IP tracking. Laravel Users' optional login activity has separate storage and does not add IP columns to your users table. |
| [Laravel Seedster](https://github.com/jeremykenedy/laravel-seedster) | Application seeding tools. | Setup guidance for host-managed seeders. Laravel Users does not run seeds or create administrator accounts. |

Spatie Laravel Permission is an alternative role integration. Official `dicebear/core` and `dicebear/styles` libraries provide optional local avatars. Required runtime dependencies are [PHP-Parser](https://github.com/nikic/PHP-Parser), for preserving host routes during setup, and [UA Parser](https://github.com/ua-parser/uap-php), for optional device and browser details.

### Related Package

[Laravel Notifications](https://github.com/jeremykenedy/laravel-notifications) adds an in-app notification center with bell and badge controls, read/unread tracking, mark-all-read, deletion, and a REST API. It complements Toast's immediate feedback when your application needs a place to keep and revisit notifications. It is a separate host integration; Laravel Users does not currently install it or send notices to its notification center automatically.

## Project Layout

```text
src/
  Actions/                 Account, email, and user operations
  App/Http/                Controllers, middleware, and requests
  App/Console/Commands/    Install, update, switch and publish commands
  Console/                 Compatible command classes and maintenance commands
  Support/                 Package integrations and configuration
  database/                Opt-in package migrations
  resources/assets/        Compiled CSS, runtime scripts and license notices
  resources/views/         Blade screens, components, and email templates
  routes/                  Package routes
docs/                      Setup, feature, and upgrade guides
art/                       Theme banners and browser screenshots
tests/                     Feature, integration, and browser tests
phpunit.xml                Tests across the supported Laravel versions
phpunit.coverage.xml       PHP coverage configuration for PHPUnit 12
```

## Testing

Run the package checks locally:

```sh
composer validate --strict
composer check
npm ci
npm run build
npm run test:runtime
npm run test:browser
```

CI covers PHP and Laravel compatibility, optional roles and presentation integrations, code style, dependency audit, coverage collection, and browser tests. Browser jobs exercise Bootstrap 4 and 5 in Chromium, Firefox and WebKit. See [testing and CI](docs/testing.md) for the commands, matrix, and fixture details. Browser fixtures use isolated temporary databases and sample users.

PHP coverage uses `phpunit.coverage.xml` with PHPUnit 12 and Xdebug. It includes package logic, routes, configuration and migrations. Blade templates and generated assets are checked by the browser suite. The requested 100% coverage target has not yet been reached.

## Documentation

All guides are available in the [`docs/` folder](docs/index.md):

- [Activity tracking and online status](docs/activity.md)
- [Avatar sources and local generators](docs/avatars.md)
- [Artisan commands, install, update, and package setup](docs/commands.md)
- [Configuration and environment variables](docs/configuration.md)
- [Email templates, previews, expiration, and account recovery](docs/emails.md)
- [Optional integrations](docs/integrations.md)
- [User impersonation and session security](docs/impersonation.md)
- [Standalone navigation components](docs/navigation-components.md)
- [Roles, permissions, and middleware](docs/roles.md)
- [Routes and authorization](docs/routes.md)
- [Global settings and access rules](docs/settings.md)
- [Testing and CI](docs/testing.md)
- [Upgrade and rollback guide](docs/upgrading.md)
- [Changelog and historical versions](docs/changelog.md)
- [Roadmap and future frontend choices](docs/roadmap.md)
- [Canonical release notes](CHANGELOG.md)

## Screenshots

These screenshots use sample accounts from the isolated preview application. The pages below are captured from the current Bootstrap 5 interface.

### Mobile

<table>
  <tr><th>User directory</th></tr>
  <tr><td><img src="art/screenshots/mobile-users.jpg" alt="Mobile user directory in card view"></td></tr>
</table>

### Tablet

<table>
  <tr><th>User directory</th><th>User profile</th></tr>
  <tr>
    <td><img src="art/screenshots/tablet-users.jpg" alt="Tablet user directory"></td>
    <td><img src="art/screenshots/tablet-profile.jpg" alt="Tablet user profile"></td>
  </tr>
</table>

### Desktop

<table>
  <tr><th>Active users</th><th>Deleted users</th><th>Create user</th></tr>
  <tr>
    <td><img src="art/screenshots/desktop-users.jpg" alt="Active users table"></td>
    <td><img src="art/screenshots/desktop-deleted-users.jpg" alt="Deleted users directory in card view"></td>
    <td><img src="art/screenshots/desktop-create-user.jpg" alt="Create user form"></td>
  </tr>
  <tr><th>Edit user</th><th>Edit deleted user</th><th>User profile</th></tr>
  <tr>
    <td><img src="art/screenshots/desktop-edit-user.jpg" alt="Edit user form"></td>
    <td><img src="art/screenshots/desktop-edit-deleted-user.jpg" alt="Edit deleted user form"></td>
    <td><img src="art/screenshots/desktop-profile.jpg" alt="User profile card"></td>
  </tr>
  <tr><th>Global settings</th><th>Email settings</th><th>Account settings</th></tr>
  <tr>
    <td><img src="art/screenshots/desktop-settings.jpg" alt="Global settings page"></td>
    <td><img src="art/screenshots/desktop-settings-emails.jpg" alt="Global welcome email and template settings"></td>
    <td><img src="art/screenshots/desktop-account.jpg" alt="Signed-in account settings"></td>
  </tr>
  <tr><th>Notification settings</th><th>Account access settings</th><th>Cleanup settings</th></tr>
  <tr>
    <td><img src="art/screenshots/desktop-settings-notifications.jpg" alt="Inline alert and Toast notification settings"></td>
    <td><img src="art/screenshots/desktop-settings-accounts.jpg" alt="Global account access and individual override settings"></td>
    <td><img src="art/screenshots/desktop-settings-cleanup.jpg" alt="Disabled account cleanup with destructive-action warning"></td>
  </tr>
  <tr><th colspan="3">Optional package settings</th></tr>
  <tr>
    <td colspan="3"><img src="art/screenshots/desktop-settings-packages.jpg" alt="Optional package settings with completed Toast setup displayed as a checked sentence"></td>
  </tr>
  <tr><th>Email preview</th><th colspan="2">Account link confirmation</th></tr>
  <tr>
    <td><img src="art/screenshots/email-preview.jpg" alt="Email preview"></td>
    <td colspan="2"><img src="art/screenshots/desktop-account-link.jpg" alt="Invalid or expired account link confirmation page"></td>
  </tr>
</table>

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
