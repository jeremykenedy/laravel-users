# User impersonation

Impersonation is disabled by default. It requires a working Laravel Roles or Spatie roles integration and authorization to manage users. Without a roles integration, the buttons and package controls are hidden. A user cannot impersonate themselves or begin a second impersonation while one is active.

Enable the feature in config or through the internal package control on the settings page. Review the `impersonate_users` access rule before enabling it. Existing management middleware still applies, and `impersonation.middleware` can add further restrictions.

| Option | Environment variable | Default |
| --- | --- | --- |
| `impersonation.enabled` | `LARAVEL_USERS_IMPERSONATION_ENABLED` | `false` |
| `impersonation.timeout` | `LARAVEL_USERS_IMPERSONATION_TIMEOUT` | `60` minutes |
| `impersonation.middleware` | `LARAVEL_USERS_IMPERSONATION_MIDDLEWARE` | Empty comma-separated list |
| `authorization.middleware_gates` | `LARAVEL_USERS_AUTHORIZATION_MIDDLEWARE_GATES` | Empty JSON object |

The duration is bounded to 1 through 1,440 minutes. Changes apply to new impersonation sessions. The orange status bar identifies the active account and provides an exit button. Exiting returns to the local page where impersonation started and uses the configured notification driver.

## Session verification

The session stores the original actor, target, guard, user model, return path and expiration. An encrypted, authenticated proof binds those values together, including a keyed fingerprint of the actor's password and remember token. Modifying a value without its matching proof invalidates the session. A password or remember-token change ends the session rather than signing the actor back in. Starting and exiting regenerate both the session identifier and CSRF token.

`VerifyImpersonationState` runs on host web routes as well as package routes. It checks the target identity and the original actor's current permissions on each request. Expiration, a disabled feature or revoked permission ends impersonation and restores the original actor before the requested action runs. A missing actor, mismatched identity or malformed state ends the session. The exit route remains available when permission has been revoked or the session has expired.

The service provider registers the guard with the web middleware group. Install, update and switch also use AST edits to register it in an existing host `routes/web.php` and its literal middleware arrays. Generated entries use class-existence checks for compatibility with older package versions. Custom application routes and dynamic middleware expressions are preserved. Rebuild route caches through your normal deployment process after reviewing changes.

## Rechecking host authorization

Every request rechecks both management middleware and `impersonation.middleware` against the original actor. Supported restrictions include Laravel authentication, email verification and gates, Laravel Roles roles, permissions and levels, and Spatie roles and permissions. Registered class aliases and nested middleware groups are recognized. Role lists and guard arguments keep the selected package's meaning.

Custom middleware is not executed inside a queue worker or under a substituted authentication guard. Unrecognized restrictions deny impersonation and queued package changes unless they have an explicit host gate mapping:

```php
// config/laravelusers.php
'authorization' => [
    'middleware_gates' => [
        'ensure-active' => 'active-account-access',
    ],
],
```

Define that gate in the host application. It receives the actor, a nullable target and the middleware arguments. It must represent the custom middleware's authorization decision. Package jobs have no target; list-level checks also pass `null`. Recognized restrictions still apply even if their alias is present in the mapping.

`can` checks support abilities, class arguments and quoted literal arguments. Route-bound arguments cannot be reconstructed safely on unrelated host requests or in a worker and deny the operation. Use an actor-aware gate mapping for those custom restrictions. An undefined gate denies access.

## Activity and application listeners

Laravel Users does not update login timestamps, login IPs or online-presence records while impersonating or restoring the original actor. It still dispatches Laravel's normal authentication events. Application-owned listeners should check `session()->has('laravelusers.impersonation')` if they need to distinguish an impersonation from a normal sign-in.

The package does not create administrator roles or grant permissions. Access changes use the same role, permission, guard and team rules as the rest of user management. Test your host middleware and any application-specific authentication restrictions before enabling the feature.
