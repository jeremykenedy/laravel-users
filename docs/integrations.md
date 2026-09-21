# Optional integrations

Laravel Users provides its own Blade views, theme control, and session alerts. None of the following packages is required. `--with` prints setup instructions only. You decide whether to run them and configure the host application. Their PHP and Laravel requirements may be higher than this package's compatibility floor.

| Option | Package | Setup command after Composer installation |
| --- | --- | --- |
| `ui-kit` | `jeremykenedy/laravel-ui-kit` | `php artisan ui-kit:install` |
| `toast` | `jeremykenedy/laravel-toast` | `php artisan toast:install` |
| `darkmode-toggle` | `jeremykenedy/laravel-darkmode-toggle` | `php artisan darkmode:install` |
| `ip-capture` | `jeremykenedy/laravel-ip-capture` | `php artisan ip-capture:install` |
| `seedster` | `jeremykenedy/laravel-seedster` | Register only the seeders your application needs. |

Install with `composer require` followed by the chosen package name. Consult each package's documentation for its current setup options:

- [UI Kit](https://github.com/jeremykenedy/laravel-ui-kit): use components in your custom or published views. Selecting it does not automatically replace Laravel Users templates.
- [Toast](https://github.com/jeremykenedy/laravel-toast): connect the existing `success` and `error` session messages to the host toast component. Disable `enablePackageBootstapAlerts` if the host displays the same messages.
- [Darkmode Toggle](https://github.com/jeremykenedy/laravel-darkmode-toggle): use a host-managed theme component if preferred. Disable `themeToggle` to avoid duplicate controls and synchronize the package root's `data-lu-theme` and `data-bs-theme` attributes from the host theme state. The built-in selector uses the separate `laravelusers.theme` local-storage key.
- [IP Capture](https://github.com/jeremykenedy/laravel-ip-capture): configure tracking on your user model according to your application's requirements. Laravel Users does not add IP columns or enable tracking.
- [Seedster](https://github.com/jeremykenedy/laravel-seedster): register application-owned seeders. Laravel Users does not seed accounts or execute seeders.

No integration option runs migrations, seeds, nested Composer commands, or another package's installer.
