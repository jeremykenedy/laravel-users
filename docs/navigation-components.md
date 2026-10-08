# Standalone navigation components

Applications can use the theme toggle and user menu in their own header without rendering a Laravel Users page. Both are Blade components supplied by the installed package. No separate JavaScript library or CSS framework is required.

## Add them to a layout

```blade
<nav class="dashboard-navigation" aria-label="Account navigation">
    <x-laravelusers::user-menu />
    <x-laravelusers::theme-toggle />
</nav>

@stack('laravelusers-components-scripts')
```

Put the script stack before `</body>`, after the components. The user-menu scripts are pushed once and close the dropdown on outside click or Escape. Component styles are included once; the theme script initializes after the document is ready. Published templates retain Laravel's normal override precedence.

## Theme toggle

```blade
<x-laravelusers::theme-toggle
    id="dashboard-theme"
    target="html"
    class="dashboard-theme-control"
/>
```

| Prop | Default | Purpose |
| --- | --- | --- |
| `id` | `laravelusers-theme-toggle` | Unique button ID. Supply different IDs if rendering multiple controls. |
| `target` | `html` | CSS selector for the element that receives the theme. Use `#laravelusers` when controlling the package interface. |

The control cycles light, dark and system modes using the same icons as bundled navigation. It saves the preference in `localStorage` under `laravelusers.theme`. System mode follows `prefers-color-scheme`. Multiple controls targeting the same element update together.

The target receives `data-lu-theme="light|dark"`, Bootstrap's `data-bs-theme`, and the `dark` class. The component supplies its own button styling; your application supplies styles for its page. Bootstrap 5 can use `data-bs-theme`; Tailwind layouts can use their configured class-based dark variant. An explicitly included component remains available even when `themeToggle=false` hides the bundled navigation control.

Listen for theme changes if another application service needs them:

```js
document.documentElement.addEventListener('lu:theme', event => {
    document.body.dataset.appTheme = event.detail.theme;
});
```

`theme` is the resolved light/dark value. `preference` also permits system. Use a target that exists by the time the document is ready. Custom selectors are read from escaped attributes, not inserted into executable source.

## User menu

```blade
<x-laravelusers::user-menu
    :show-logout="true"
    logout-route="logout"
    class="dashboard-account-control"
/>
```

| Prop | Default | Purpose |
| --- | --- | --- |
| `show-logout` | `laravelusers.showLogout` | Include the logout form when the named route exists. |
| `logout-route` | `logout` | Application route accepting POST logout with CSRF protection. |

The menu displays the authenticated user's escaped name and configured avatar in a circle. It respects global saved settings and opt-in per-user avatar preferences. Missing images show the configured fallback. Guests receive no account menu. Without logout, the component shows the avatar/name without an empty dropdown.

The component does not create authentication routes or replace the application's logout behavior. Hide logout with `:show-logout="false"`, or supply your application's POST route. Keep the default web session/CSRF middleware in the host layout's route group.

## Publish and customize

```sh
php artisan laravelusers:update --views=publish
```

This adds missing templates while preserving existing ones. The component templates are `resources/views/vendor/laravelusers/components/theme-toggle.blade.php` and `user-menu.blade.php`. Their shared partials and scripts remain overrideable. Use `--views=publish --force` only after reviewing the backed-up publication workflow in [upgrading](upgrading.md).

For a dashboard that already supplies navigation, set `LARAVEL_USERS_SHOW_HEADER=false` and keep your own header containing these components. Custom package parent layouts must continue yielding the package's CSS, content and script sections described in [configuration](configuration.md).
