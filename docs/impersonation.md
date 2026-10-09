# User impersonation

Impersonation is disabled by default. It requires a working Laravel Roles or Spatie roles integration and authorization to manage users. Without a roles integration, the buttons and package controls are hidden. A user cannot impersonate themselves or begin a second impersonation while one is active.

Enable the feature in config or through the internal package control on the settings page. Review the `impersonate_users` access rule before enabling it. Existing management middleware still applies, and `impersonation.middleware` can add further restrictions.

| Option | Environment variable | Default |
| --- | --- | --- |
| `impersonation.enabled` | `LARAVEL_USERS_IMPERSONATION_ENABLED` | `false` |
| `impersonation.timeout` | `LARAVEL_USERS_IMPERSONATION_TIMEOUT` | `60` minutes |
| `impersonation.middleware` | `LARAVEL_USERS_IMPERSONATION_MIDDLEWARE` | Empty comma-separated list |

The duration is bounded to 1 through 1,440 minutes. Changes apply to new impersonation sessions. The orange status bar identifies the active account and provides an exit button. Exiting returns to the local page where impersonation started and uses the configured notification driver.

## Session verification

The session stores the original actor, target, guard, user model, return path and expiration. An encrypted, authenticated proof binds those values together. Modifying a value without its matching proof invalidates the session. Starting and exiting regenerate both the session identifier and CSRF token.

`VerifyImpersonationState` runs on host web routes as well as package routes. It checks the target identity and the original actor's current permissions on each request. Expiration, a disabled feature or revoked permission ends impersonation and restores the original actor before the requested action runs. A missing actor, mismatched identity or malformed state ends the session. The exit route remains available when permission has been revoked or the session has expired.

The service provider registers the guard with the web middleware group. Install, update and switch also use AST edits to register it in an existing host `routes/web.php` and its literal middleware arrays. Generated entries use class-existence checks for compatibility with older package versions. Custom application routes and dynamic middleware expressions are preserved. Rebuild route caches through your normal deployment process after reviewing changes.

## Activity and application listeners

Laravel Users does not update login timestamps, login IPs or online-presence records while impersonating or restoring the original actor. It still dispatches Laravel's normal authentication events. Application-owned listeners should check `session()->has('laravelusers.impersonation')` if they need to distinguish an impersonation from a normal sign-in.

The package does not create administrator roles or grant permissions. Access changes use the same role, permission, guard and team rules as the rest of user management. Test your host middleware and any application-specific authentication restrictions before enabling the feature.
