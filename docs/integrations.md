# Optional integrations

Laravel Users provides its own views, theme control, and session alerts. None of the following packages is required. `--with` prints setup instructions only. Explicit Toast and roles options can install and configure those integrations. Their PHP and Laravel requirements may be higher than this package's compatibility floor.

| Option | Package | Setup command after Composer installation |
| --- | --- | --- |
| `ui-kit` | `jeremykenedy/laravel-ui-kit` | `php artisan ui-kit:install` |
| `toast` | `jeremykenedy/laravel-toast` | `php artisan toast:install` |
| `darkmode-toggle` | `jeremykenedy/laravel-darkmode-toggle` | `php artisan darkmode:install` |
| `ip-capture` | `jeremykenedy/laravel-ip-capture` | `php artisan ip-capture:install` |
| `seedster` | `jeremykenedy/laravel-seedster` | Register only the seeders your application needs. |

Install with `composer require` followed by the chosen package name. Consult each package's documentation for its current setup options:

- [UI Kit](https://github.com/jeremykenedy/laravel-ui-kit): use components in your custom or published views. Selecting it does not automatically replace Laravel Users templates.
- [Toast](https://github.com/jeremykenedy/laravel-toast): choose `notifications.driver=toast` or `both` to render the package's existing session notices through its built-in adapter. Keep the default `alert` when your host already handles those messages.
- [Darkmode Toggle](https://github.com/jeremykenedy/laravel-darkmode-toggle): use a host-managed theme component if preferred. Disable `themeToggle` to avoid duplicate controls and synchronize the package root's `data-lu-theme` and `data-bs-theme` attributes from the host theme state. The built-in selector uses the separate `laravelusers.theme` local-storage key.
- [IP Capture](https://github.com/jeremykenedy/laravel-ip-capture): configure tracking on your user model according to your application's requirements. Laravel Users does not add IP columns or enable tracking.
- [Seedster](https://github.com/jeremykenedy/laravel-seedster): register application-owned seeders. Laravel Users does not seed accounts or execute seeders.

The `--with` options do not run migrations, seeds, Composer, or another package's installer. Role setup has a separate explicit Composer installation choice; see [roles and permissions](roles.md#installer-choices).

## Toast notifications

Laravel Toast is optional. Use `php artisan laravelusers:update --toast=install --notifications=toast`, or install it through the enabled Packages settings tab. Both workflows complete Toast setup automatically and preserve existing host configuration. In the settings UI, choose Toast or Alerts and Toast under Notifications and save. These choices appear only when the dependency and its views are available. Missing Toast falls back to inline alerts. Validation errors remain inline.

When Toast is selected, the Notifications tab exposes position, direction, duration, visible count, opacity, animations, progress, stacking, hover behavior, icons, border, closing and automatic dismissal. Saved choices require notification-edit access and fall back to the host Toast configuration. Normal session notifications do not need the package-management queue; installing or removing dependencies through the UI does.

`--toast=remove` removes the Composer dependency explicitly, preserves published files and selects alerts. Application references must be reviewed separately. [Settings](settings.md) documents the optional web installation/removal workflow, dedicated gate, queue requirements and typed confirmations.

## Local avatars

UI Avatars works locally without another package. DiceBear can use its official optional PHP libraries on PHP 8.2 or newer. See [avatars](avatars.md) for installation, resolution, fallbacks, external-service choices and privacy settings.

## Application navigation

The theme toggle and avatar/user menu are available separately as [Blade components](navigation-components.md). They do not require a full package page, UI Kit or Darkmode Toggle dependency.
