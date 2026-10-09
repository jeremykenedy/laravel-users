# Laravel Users documentation

| Guide | What it covers |
| --- | --- |
| [Artisan commands](commands.md) | Installation, updating, switching CSS, role selection, publishing, backups, and rollback. |
| [Routes and authorization](routes.md) | Route names, middleware, access rules, and optional user/account pages. |
| [User settings](settings.md) | Global defaults, individual appearance, access rules, notifications, package confirmations, and queue requirements. |
| [Navigation components](navigation-components.md) | Standalone theme toggle and user menu in an application-owned layout. |
| [Configuration](configuration.md) | Configuration keys, environment variables, UI controls, layouts, and defaults. |
| [Roles and permissions](roles.md) | Optional Laravel Roles, Spatie, custom models, guards, teams, and middleware. |
| [Emails](emails.md) | Templates, previews, bulk recipients, expiration, welcome mail, and deleted-account links. |
| [Avatars](avatars.md) | Global sources, per-user preferences, migration, inheritance, and safe fallbacks. |
| [Login activity](activity.md) | Latest login, browser/device/IP information, online sessions, storage, and cleanup. |
| [Impersonation](impersonation.md) | Optional role-restricted sessions, verification, expiration, middleware and activity behavior. |
| [Optional integrations](integrations.md) | Optional UI and host services with explicit setup. |
| [Upgrading](upgrading.md) | Version transitions, published overrides, opt-in migrations, deployment checks, and rollback. |
| [Testing](testing.md) | PHP and browser suites, CI matrix, accessibility, and isolated preview fixtures. |
| [Changelog](../CHANGELOG.md) | Release-specific changes and unreleased work. |

Start with [upgrading](upgrading.md) for an existing application. New applications should run the installer after setting up their user model and authentication. No package command creates administrator accounts or silently enables optional tracking, account links, or role integrations.
