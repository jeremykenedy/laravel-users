<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="art/banner-dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="art/banner-light.svg">
        <img src="art/banner-light.svg" alt="Laravel Users" width="800">
    </picture>
</p>

<p align="center">User management for Laravel with configurable models, authentication, roles, search, and Blade views.</p>

<p align="center">
    <a href="https://packagist.org/packages/jeremykenedy/laravel-users"><img src="https://poser.pugx.org/jeremykenedy/laravel-users/d/total.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/jeremykenedy/laravel-users"><img src="https://poser.pugx.org/jeremykenedy/laravel-users/v/stable.svg" alt="Latest Stable Version"></a>
    <a href="https://github.com/jeremykenedy/laravel-users/actions/workflows/tests.yml"><img src="https://github.com/jeremykenedy/laravel-users/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
    <a href="https://github.styleci.io/repos/83162309"><img src="https://github.styleci.io/repos/83162309/shield?branch=master" alt="StyleCI"></a>
    <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT"></a>
</p>

## Table of Contents

- [Framework Support](#framework-support)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Features](#features)
- [Configuration](#configuration)
- [Login Details and Online Status](#login-details-and-online-status)
- [Changing Frameworks](#changing-frameworks)
- [Artisan Commands](#artisan-commands)
  - [Install Options](#install-options)
- [Roles and Optional Integrations](#roles-and-optional-integrations)
- [Routes](#routes)
- [Testing](#testing)
- [Screenshots](#screenshots)
- [Historical Releases](#historical-releases)
- [License](#license)

## Framework Support

**Bootstrap 4 remains the default.** Running `composer update` does not publish files, change your frontend, install optional packages, or modify your application configuration. Existing routes, configuration keys (including their historical spelling), view names, and the `laravelusers` publish tag remain available.

| Frontend | Views | Assets | Default |
| --- | --- | --- | --- |
| Bootstrap 4 | Existing Blade templates and scripts | Existing configurable Bootstrap, jQuery, Popper, and Font Awesome URLs | Yes |
| Bootstrap 5 | Modern Blade templates | Bootstrap 5.3 CSS, optional host assets; plain JavaScript | No |
| Tailwind CSS | Modern Blade templates | Bundled, compiled Tailwind 4 utilities; plain JavaScript | No |

Modern views provide accessible form labels and errors, responsive tables, server pagination, search, and deletion confirmation. They do not require jQuery or a Node build in the consuming application. DataTables and Bootstrap tooltips remain options for the Bootstrap 4 views. Modern views use their own search and pagination controls.

## Requirements

This branch requires PHP 8.1 or newer, as before. CI covers Laravel 8 through 13 on compatible PHP versions. Older Laravel applications should retain their compatible package release; this update does not restore support for older PHP versions. Historical compatibility tests do not extend upstream framework security support.

The host application needs an existing users table, user model, and authentication setup.

## Installation

From an application with an existing users table, user model, and authentication setup:

```sh
composer require jeremykenedy/laravel-users
php artisan laravelusers:install
```

The interactive command asks for the framework, theme, and whether to publish views. It detects existing configuration and keeps the current choices as defaults. Existing configuration and published views are preserved. Visit `/users` after signing in. Package discovery registers the provider automatically.

For unattended installation with the existing default:

```sh
php artisan laravelusers:install --no-interaction
```

The package manages accounts; it does not install an authentication system, create administrator accounts, or change database schemas. Authentication is enabled by default. Configure your application's authorization middleware before exposing user management to ordinary signed-in users. Authentication alone is not an administrator permission check.

The original publishing workflow remains available:

```sh
php artisan vendor:publish --tag=laravelusers
```

It publishes configuration, translations, and all views. Laravel Collective is not required by the bundled templates. Previously customized templates that use it still need their own dependency.

## Quick Start

All three frontends use Blade. Choose one during installation:

```sh
php artisan laravelusers:install --framework=bootstrap4 --no-interaction
php artisan laravelusers:install --framework=bootstrap5 --no-interaction
php artisan laravelusers:install --framework=tailwind --no-interaction
```

Sign in and visit `/users`. Add `--theme=system` to follow the device color preference, or `--theme=dark` to start in dark mode.

Livewire applications can link to the same server-rendered pages:

```blade
<a href="{{ route('users') }}">Manage users</a>
```

Vue, React, and Svelte applications can link to `/users` from their existing navigation:

```html
<a href="/users">Manage users</a>
```

These integrations use the package's Blade pages. Native Livewire, Vue, React, and Svelte view sets are not provided; `--frontend` accepts `blade` only.

## Features

- Create, search, edit, and delete users.
- Keep existing Bootstrap 4 views or choose Bootstrap 5 or Tailwind CSS.
- Use light, dark, or system themes with an optional theme selector.
- Configure user models, routes, role middleware, and parent layouts.
- Preserve published customizations during installation and updates.
- Back up published views before replacing them with `--force`.
- Optionally display the latest login time, IP address, device, OS, and browser.
- Optionally show online status from recent authenticated sessions.

## Configuration

The complete configuration is in [src/config/laravelusers.php](src/config/laravelusers.php). Applications may publish it or set the same keys in their configuration.

| Option | Default | Purpose |
| --- | --- | --- |
| `frontend` | `bootstrap4` | View framework: `bootstrap4`, `bootstrap5`, or `tailwind`. |
| `theme` | `light` | Default theme: `light`, `dark`, or `system`. |
| `themeToggle` | `false` | Show the theme selector. |
| `defaultUserModel` | `App\Models\User` | Application user model. |
| `authEnabled` | `true` | Require authentication for CRUD routes. |
| `rolesEnabled` | `false` | Enable role management. |
| `rolesMiddlwareEnabled` | `true` | Use role middleware when role support is enabled. |
| `rolesMiddlware` | `role:admin` | Role middleware name. |
| `enablePagination` | `true` | Paginate the user list. |
| `paginateListSize` | `25` | Users per page. |
| `activity.login` | `false` | Record the latest login in the separate activity table. |
| `activity.online` | `false` | Track recent authenticated sessions in the cache. |
| `activity.guard` | `web` | Guard to track. |
| `activity.connection` | `null` | Connection for login records and migration; null uses the default. |
| `activity.cache_store` | `null` | Presence cache store; null uses the default. |
| `activity.online_seconds` | `300` | Inactivity window in seconds. |

Set `themeToggle` to `true` to show a light, dark, and system selector. A user's selection is stored locally in their browser. System mode follows their device preference. No dark mode package is required. Dark styles are scoped to the package interface. Existing published layouts need the theme partials added or a reviewed update before they can display the new selector.

If you use the setup commands, `laravelusers-ui.framework` and `laravelusers-ui.theme` take precedence over `laravelusers.frontend` and `laravelusers.theme`.

`laravelUsersBladeExtended` selects your parent layout. Custom layouts should render `template_title`, `template_linked_css`, `content`, and `template_scripts` sections. Custom view settings (`showUsersBlade`, `createUserBlade`, `showIndividualUserBlade`, `editIndividualUserBlade`) are never remapped. Only the four exact bundled view names switch to the modern templates when a modern framework is selected.

Asset switches remain available. `enableBootstrapCssCdn` controls loading Bootstrap CSS in the package layout; `bootstrap5CssCdn` selects the Bootstrap 5 stylesheet. `enableAppCss` and `enableAppJs` control host assets. Disable them when your application does not provide the configured `css/app.css` or `js/app.js`. Tailwind utilities are compiled and bundled with the package, with an `lu:` prefix and no global preflight reset. Custom layouts should load one framework stylesheet appropriate to the selected view set.

`enableSearchUsers=false` hides the search form in every bundled framework. Search accepts both JSON responses decoded by jQuery and legacy JSON text. If you published views before updating, merge the search fixes into your overrides or review the backed-up publication workflow in [the upgrade guide](docs/upgrading.md). Vite applications can use their own parent layout to load assets through `@vite`; disable the package's host asset switches when those files are not served from the configured public paths.

## Login Details and Online Status

Both features are disabled by default. Enable online status in `config/laravelusers.php`:

```php
'activity' => [
    'login' => false,
    'online' => true,
    'guard' => 'web',
    'connection' => null,
    'cache_store' => null,
    'online_seconds' => 300,
],
```

Use a persistent cache store with atomic lock support, such as file, database, Redis, or Memcached. Use a shared store across application servers. Status refreshes when Laravel authenticates a request, expires after the configured interval, and removes the current session on logout. Other active devices remain online. Status is shown on page load; no polling or heartbeat is added.

For login details, publish and run the separate migration, then set `activity.login` to `true`:

```sh
php artisan vendor:publish --tag=laravelusers-activity-migrations
php artisan migrate
```

Set `activity.connection` before migrating if records belong on another connection. The migration creates `laravelusers_login_activity`; it never changes the users table. Composer and package setup commands do not run migrations. Only the latest login is retained. Eloquent user deletion removes its activity records.

All three view sets show login time, IP, device, OS, and browser on the detail page. IP resolution follows Laravel's trusted proxy configuration. Device details come from the user-agent header and are estimates, not verified device identities. Existing published views need a reviewed update to display these fields.

Tracking follows Laravel's `Login`, `Authenticated`, and `Logout` events for the configured guard and user model. Custom authentication flows must dispatch those events. Tracking errors are reported without interrupting sign-in. Login details and IP addresses stay out of search JSON. Keep user management behind your application's administrator authorization middleware.

See [activity configuration and usage](docs/activity.md). Laravel IP Capture remains an independent optional integration; neither feature requires it.

## Changing Frameworks

```sh
composer update jeremykenedy/laravel-users
php artisan laravelusers:update

php artisan laravelusers:update --framework=bootstrap5 --theme=system --no-interaction
php artisan laravelusers:update --framework=tailwind --theme=dark --no-interaction
php artisan laravelusers:update --framework=bootstrap4 --theme=light --no-interaction
```

Framework and theme choices are saved in `config/laravelusers-ui.php`. The command leaves existing `config/laravelusers.php` contents intact. Explicitly configured custom view names and custom parent layouts continue to take precedence. Clear a cached configuration before running the command, then rebuild it as part of deployment:

```sh
php artisan config:clear
php artisan laravelusers:update --framework=bootstrap5 --no-interaction
php artisan config:cache
```

`--force` applies only to views. Neither command overwrites existing main configuration or translations. To stop using a customized view, rename or remove that specific override after reviewing it. `--views=package` deliberately does not delete files. See [upgrading and rollback](docs/upgrading.md).

The update command supports both interactive selection and direct flags. Package assets are ready to use. If you change your host application's asset sources when switching, run `npm run build` in that application.

For a quick switch without prompts:

```sh
php artisan laravelusers:switch --css=bootstrap5
php artisan laravelusers:switch --framework=tailwind --theme=dark
```

| Command | Selection | Configuration |
| --- | --- | --- |
| `laravelusers:update` | Interactive or direct flags | Keeps main configuration and custom views. |
| `laravelusers:switch` | Direct flags only | Uses the same publication and backup safeguards. |

Run `npm run build` after switching if your host builds its own framework assets. Bundled package assets do not need a host build.

## Artisan Commands

| Command | Purpose | Options |
| --- | --- | --- |
| `laravelusers:install` | Set up the package, preserving existing configuration and views. | Options below. |
| `laravelusers:update` | Update framework, theme, and view publishing choices. | Options below. |
| `laravelusers:switch` | Change frontend choices without prompts. | Options below; requires framework, CSS, theme, or views. |
| `vendor:publish --tag=laravelusers-activity-migrations` | Publish the opt-in login activity migration. | Laravel's standard publish options. |
| `vendor:publish --tag=laravelusers` | Publish package configuration, translations, and views through Laravel. | Laravel's standard publish options. |

### Install Options

These options also apply to update and switch.

| Option | Behavior |
| --- | --- |
| `--framework=bootstrap4\|bootstrap5\|tailwind` | Select a frontend. Omitted unattended updates retain the current selection. |
| `--css=bootstrap4\|bootstrap5\|tailwind` | Alias for `--framework`. Conflicting choices are rejected. |
| `--frontend=blade` | Select the supported Blade frontend. Other values are rejected without writing files. |
| `--theme=light\|dark\|system` | Select the default color theme. |
| `--views=package` | Use the view loader without publishing files. Existing overrides still take precedence. |
| `--views=publish` | Copy missing views to `resources/views/vendor/laravelusers`. Preserve existing files. |
| `--views=publish --force` | Back up the entire published view directory under `storage/app/laravelusers/backups`, then replace package-owned view files. |
| `--with=ui-kit` | Print optional integration setup instructions. Repeat for other integrations. |
| `--no-interaction` | Use current choices or package defaults without prompts. |

## Roles and Optional Integrations

Role support stays disabled by default. To enable it, configure your role model and middleware. The role-enabled user model must provide `roles`, `attachRole()`, `detachAllRoles()`, and, for the legacy detail view, `level()`. Role assignment and user changes use a database transaction on the user model's connection; role tables should use that same connection.

Optional packages are not runtime dependencies:

- [Laravel UI Kit](https://github.com/jeremykenedy/laravel-ui-kit)
- [Laravel Toast](https://github.com/jeremykenedy/laravel-toast)
- [Laravel Darkmode Toggle](https://github.com/jeremykenedy/laravel-darkmode-toggle)
- [Laravel IP Capture](https://github.com/jeremykenedy/laravel-ip-capture)
- [Laravel Seedster](https://github.com/jeremykenedy/laravel-seedster)

For setup instructions from either command:

```sh
php artisan laravelusers:install --with=ui-kit --with=toast
```

Also accepted: `--with=darkmode-toggle`, `--with=ip-capture`, and `--with=seedster`. These options print Composer and Artisan instructions; they do not install or configure another package. See [integration details](docs/integrations.md) before adding components, tracking, or seeds to your host application.

## Routes

| Method | URI | Name |
| --- | --- | --- |
| GET | `/users` | `users` |
| GET | `/users/create` | `users.create` |
| POST | `/users` | `users.store` |
| GET | `/users/{user}` | `users.show` |
| GET | `/users/{user}/edit` | `users.edit` |
| PUT/PATCH | `/users/{user}` | `users.update` |
| DELETE | `/users/{user}` | `user.destroy` |
| POST | `/search-users` | `search-users` |

Search accepts `user_search_box` and returns the existing JSON array of matching models, using the model's hidden attributes. Ensure sensitive attributes on custom user models are hidden. Search retains its existing `web` and `auth` middleware even when `authEnabled` is disabled for CRUD. Deleting the current authenticated user is blocked.

The `softDeletedEnabled` option is retained for compatibility; a deleted-user management screen is not implemented.

## Testing

```sh
composer update
composer check
npm ci
npm run build
npx playwright install chromium firefox webkit
npm run test:browser
```

Tests cover CRUD, validation, password hashing and preservation, authentication, roles and transactional rollback, search, missing users, pagination, custom models, configuration caching, installers, backups, view overrides, themes, and framework rendering. Activity tests cover opt-in defaults, login events, trusted proxies, session regeneration, multiple devices, logout, expiry, failed stores, cleanup, and migration rollback. Browser tests exercise real HTTP requests and rendered Blade templates. Modern views receive automated accessibility checks in both themes.

See [testing](docs/testing.md) for the CI matrix and local fixture. The fixture is development-only and is never registered by the package service provider. Please include a reproducing test when submitting a bug fix.

## Screenshots

The original Bootstrap 4 screens remain available with the default frontend:

![Show Users](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/show-users.jpg)
![Show User](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/show-user.jpg)
![Edit User](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/edit-user.jpg)
![Edit User Password](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/edit-user-pw.jpg)
![Create User](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/create-user.jpg)
![Create User Modal](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/save-user-modal.jpg)
![Delete User Modal](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/delete-user-modal.jpg)
![Error Create](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/error-create.jpg)
![Error Update](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/error-update.jpg)
![Error Delete](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-users/error-delete.jpg)

## Historical Releases

For applications still on older Laravel versions, the previous installation pins were Laravel 5.5: `2.0.2`, Laravel 5.4: `1.4.0`, Laravel 5.3: `1.3.0`, and Laravel 5.2: `1.2.0`. Review each release's Composer requirements before upgrading an older application.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
