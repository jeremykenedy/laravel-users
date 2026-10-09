# Login details and online status

Login capture and online status are separate opt-in features. Neither adds columns or traits to your user model. Both use `laravelusers.activity.guard` (default `web`) and the configured user model, table, and connection.

## Login capture

1. Publish the migration with `php artisan vendor:publish --tag=laravelusers-activity-migrations`.
2. Set `activity.connection` before migrating if records belong on another connection.
3. Run `php artisan migrate` using your normal deployment workflow.
4. Set `activity.login` to `true` and rebuild cached configuration if needed.

Each Laravel login replaces the user's previous login record. Ordinary authenticated requests do not change the login timestamp. Remember-me restoration follows Laravel's login events. The separate table stores a hashed model identifier, login time, IP, device family, OS, and browser. Raw user-agent headers are not retained.

IP addresses use Laravel's encrypted cast and the host application key. Keep that key available when reading stored records. Device labels and timestamps remain readable in storage.

Unknown agents are recorded as `Other`; recognized desktop operating systems use `Desktop` when no device family is supplied. Browser-provided details may be spoofed. IP resolution uses `Request::ip()` and Laravel's trusted proxy rules. Configure proxies in the host application rather than trusting arbitrary forwarded headers.

Records are removed on Eloquent user deletion, including soft deletion. Bulk query deletes do not dispatch model events; call `UserActivity::forget()` for each affected user if your application uses that workflow. Disabling capture does not delete existing records. Choose your own retention policy for stored IP addresses.

## Online status

Set `activity.online` to `true`. No migration is required. Choose a persistent `activity.cache_store` with atomic lock support. File works on one server; use a shared store such as Redis across servers. The array store retains data within a request and is suitable for tests.

Authenticated requests refresh the current session. A user is online if any tracked session has activity within `activity.online_seconds` (default 300). Logout removes that session only. Session regeneration retains the tracking token; expired sessions are pruned during updates. Closing a browser becomes offline after the inactivity window.

The directory and detail views show status on page load. Search includes an activity envelope when the corresponding columns are enabled. There is no presence polling endpoint. Status indicates recent authenticated activity rather than a live network connection. [Impersonation](impersonation.md) suppresses package login and presence updates until the original actor has been restored.

## Application usage

Inject the service into your application class:

```php
use jeremykenedy\laravelusers\Support\UserActivity;

public function __construct(private UserActivity $activity)
{
}
```

Then pass the configured user model:

```php
$login = $this->activity->lastLogin($user);
$online = $this->activity->isOnline($user);
```

`lastLogin()` returns a record with a Carbon `last_login_at`, or null when disabled, unrecorded, or unavailable. `isOnline()` returns true or false when enabled, and null when disabled or unavailable. Read these in a controller, action, or view composer rather than querying from Blade.

Errors are reported through Laravel's exception handler without blocking authentication. Review application logs when details are missing or status is `Unknown`. Custom authentication flows must dispatch Laravel's authentication events. Existing published views need a reviewed update to display the new fields.
