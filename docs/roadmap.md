# Roadmap

Laravel Users 6.0.0 supports **Blade with Bootstrap 4 and Bootstrap 5**. Bootstrap 4 remains the default. The release work includes user management, optional account settings, authorized package setup, email workflows, appearance controls, and consistent page layouts.

See the [6.0.0 release notes](../CHANGELOG.md#v6-0-0) for the implemented changes.

## Current release scope

| Area | Included in 6.0.0 |
| --- | --- |
| Screen runtime | Server-rendered Blade pages |
| CSS | Bootstrap 4 by default; Bootstrap 5 by explicit choice |
| User interface | Shared content width, consistent icons and headers, responsive tabs, light/dark appearance, and unsaved notification previews |
| Application behavior | User and role management, account settings, email/link workflows, optional tracking and impersonation, cleanup, and authorized optional-package setup |
| Compatibility | PHP 8.1 minimum and Laravel 8 compatibility, preserved routes/configuration, and application-owned overrides |

Optional features still require their documented permissions, configuration, storage, and workers. Inclusion in this release does not enable them automatically.

## Future CSS frameworks

Each framework will be considered separately for a future release. The order, release numbers, and dates are **TBD**.

| Framework | Status |
| --- | --- |
| Tailwind CSS | Deferred; TBD |
| Materialize | Deferred; TBD |
| Material Design 3 | Deferred; TBD |
| Bulma | Deferred; TBD |
| Foundation | Deferred; TBD |

## Future application runtimes

These are separate from the CSS choices. They are not release installer options; their delivery order and dates are **TBD**.

| Runtime | Status |
| --- | --- |
| Livewire | Deferred; TBD |
| Vue | Deferred; TBD |
| React | Deferred; TBD |
| Svelte | Deferred; TBD |

## Before adding another option

- Preserve the existing authorization, validation, account protections, email behavior, and package-operation requirements.
- Exercise complete user workflows in the browser, including forms, confirmations, unsaved changes, errors, accessibility, mobile layouts, and both color themes.
- Verify installation, updates, rollback, and published host overrides without replacing unrelated application assets or configuration.
- Document dependency and runtime requirements before adding a choice to the supported matrix.

Existing applications keep their selected views and defaults. Future frontend work must remain an explicit choice.

## Related guides

- [Changelog and historical versions](changelog.md)
- [Installation and framework commands](commands.md)
- [Upgrade and rollback guide](upgrading.md)
- [Feature documentation](index.md)
