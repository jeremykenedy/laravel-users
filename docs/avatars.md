# Avatars

## Existing applications

Per-user preferences are disabled by default. The package does not add columns to your users table, backfill users, publish migrations during Composer updates, or change your existing global avatar source. A saved preference is optional; users without one follow `avatar.source`.

The directory avatar column still follows `avatar.enabled`, and profile avatars follow `showProfileAvatar`. These display switches work independently of whether administrators can select a source.

## Enabling per-user selection

Publish and review the separate migration, then run your application's migrations:

```sh
php artisan vendor:publish --tag=laravelusers-avatar-migrations
php artisan migrate
```

Enable the option in your config, or use the environment fallback in the bundled config:

```dotenv
LARAVEL_USERS_AVATAR_PER_USER=true
```

If your config was published before this option existed, merge `per_user => env('LARAVEL_USERS_AVATAR_PER_USER', false)` into its `avatar` array. Preserve your other avatar settings. Clear and rebuild configuration caches as appropriate for your deployment.

Create and edit forms then offer Use package setting and all supported sources listed below. Use package setting is the default for existing and new users. Choosing it removes the saved override, so later global changes apply to that user again. Existing callers that omit `avatar_source` preserve the current preference on update. Disabling the feature hides the selector and uses the global source while retaining preferences for later re-enablement.

If enabled before the migration runs, pages continue using the global source and show a disabled selector with setup instructions. Requests attempting to change a source are rejected before user changes are saved.

## Sources and storage

| Choice | Behavior |
| --- | --- |
| Use package setting | Follow `avatar.source` and its fallback. |
| Avatar | Read the existing user-model attribute named by `avatar.attribute`. |
| Gravatar | Request the image for the normalized email hash; missing images use `avatar.fallback`. |
| Initials | Render initials locally without an image request. |
| Identicon, MonsterID, RoboHash, Retro, Wavatar, Mystery person (`mp`) | Gravatar-generated styles. These contact Gravatar; they are not local generators. |
| DiceBear | Generate SVG locally with the optional official PHP libraries. |
| UI Avatars | Generate an initials SVG locally with no additional dependency. |

Avatar URLs accept HTTP, HTTPS, or a root-relative application path. Script, data, and protocol-relative URLs are rejected. The source selector does not upload files or replace an application's existing avatar upload flow.

`laravelusers_avatar_preferences` stores the source and a hash identifying the model class, connection, table, and user key. It supports numeric and string keys without adding a foreign key to a host-owned users table. The migration and preference writes use the configured user model's connection. Preference changes share the user-update transaction. Directory rendering fetches preferences together rather than once per row.

Soft deletion and restoration retain preferences. Permanent Eloquent deletion removes them while the feature is enabled. Query-builder and raw SQL deletes bypass model events; application-owned deletion code must clean up package data if it bypasses Eloquent. Keep the preference table if you disable the feature temporarily. Dropping it removes overrides; all users then inherit global settings.

Published form overrides need the `partials/avatar-source.blade.php` include to expose the selector. Use the backed-up view publication workflow or merge that partial into your forms. [Upgrading](upgrading.md) covers both approaches.

## Local generation

UI Avatars uses `avatar.ui_avatars.driver=local` by default. It returns an escaped SVG data URI generated in the application, using configurable six-digit background/text colors. It sends no name or email to an external service. This works on the package's existing PHP 8.1 floor.

DiceBear uses local generation by default and requires PHP 8.2 or newer:

```sh
php artisan laravelusers:update --avatar=dicebear --install-avatars
```

Or install the optional libraries explicitly:

```sh
composer require dicebear/core:^10.7 dicebear/styles:^10.6
php artisan laravelusers:update --avatar=dicebear
```

`avatar.dicebear.style` selects a style supplied by `dicebear/styles`, defaulting to `identicon`. Invalid or unavailable styles use the existing icon/initials fallback. Missing libraries never trigger an implicit remote request. Restart long-running workers after installing dependencies. Applications with a Content Security Policy need `data:` in their image sources to render the local SVG images.

`avatar.image_size` defaults to 256, independently of the 40px table display size. Generated images and Gravatar requests use the larger resolution so profile avatars remain sharp. The generated size is bounded between the display size and 1024px.

## Remote services

`avatar.remote_enabled=false` disables generated remote avatars. Host-provided avatar URLs remain an explicit application choice. Gravatar sources use a SHA-256 email hash, `referrerpolicy=no-referrer`, and the configured fallback if loading fails. Forced style choices use Gravatar's matching default-image code.

Remote DiceBear requires both `avatar.dicebear.driver=remote` and an explicit HTTP(S) endpoint in `avatar.dicebear.url`. There is no implicit public endpoint. Use your own compatible server and review its API and licensing. Remote UI Avatars also requires `avatar.ui_avatars.driver=remote`; local remains the default.

DiceBear seeds use an opaque HMAC based on the model identity and application key, rather than names or email addresses. Remote UI Avatars receives initials rather than the full name. Changing the application key changes generated seeds. Do not treat generated avatars as authentication or proof of identity.

The source selector is also available in [global settings](settings.md). Saved per-user choices continue to override that global source while per-user preferences are enabled.
