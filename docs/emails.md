# Email actions

## Setup

Laravel Users uses your application's mail transport, queue, password broker, and `password.reset` route. Configure those before sending mail. With an asynchronous queue, run your normal queue workers. The success message confirms that notifications were queued, not that the mail server delivered them.

Custom messages, welcome emails, and password reset links use the same dialog on directory tables, directory cards, profiles, and edit screens. Bulk actions reuse that dialog. Each recipient receives a separate notification with their own name and links.

## Feature switches

| Setting | Effect when false |
| --- | --- |
| `emails.enabled` | Hide and reject every package email action, including welcome mail during creation. |
| `emails.message` | Hide and reject custom messages. |
| `emails.welcome` | Hide and reject welcome mail, including the creation option. |
| `emails.reset` | Hide and reject password reset emails from management screens. |
| `emails.bulk` | Hide bulk email choices and reject multiple email recipients. Other bulk actions remain available. |
| `emails.edit_welcome` | Hide and reject welcome-content overrides while retaining its send action. |
| `emails.edit_reset` | Hide and reject reset-content overrides while retaining its send action. |
| `emails.reset_allow_never_expire` | Hide and reject non-expiring password reset links. |
| `account_links.allow_never_expire` | Hide and reject non-expiring account links. |
| `emails.preview` | Hide Preview email and reject preview requests. |
| `emails.deleted` | Hide and reject custom mail to deleted accounts. |
| `emails.reset_duration` | Hide the per-email expiration inputs and reject submitted expiration overrides. |
| `welcome.enabled` | Disable welcome mail and password setup. |
| `welcome.force_password_reset` | Hide and reject password setup during creation. |
| `account_links.enabled` | Disable all deleted-account recovery and deletion links. |
| `account_links.restore` | Disable restore links independently. |
| `account_links.force_delete` | Disable permanent-delete links independently. |

Every switch has an environment fallback in the bundled config. See [configuration](configuration.md#environment-variables). The global settings page also has an administrator-controlled welcome-email switch and editable welcome subject and message. It requires the settings migration and uses the configured settings gate. That saved choice cannot override `emails.enabled=false` or `emails.welcome=false`. Disabling package reset emails does not disable your application's own password recovery pages.

## Custom messages and bulk recipients

Enter a subject and plain-text message. The optional greeting defaults to `Hi`, and the optional closing defaults to `Thanks`. Include name adds the recipient's username and a comma after the greeting. Name below closing supplies a signature such as `Admin`. These defaults are configurable in `emails`; the sender can change them in the dialog. Message content and names are escaped before rendering HTML.

Bulk recipient chips appear below the count. Remove a chip to exclude that user from the request. `emails.recipient_height` controls the scroll area's height in pixels, defaulting to 96. The request still enforces `bulkLimit` and verifies every selected account before queuing anything. Duplicate, missing, or mixed active/deleted recipients are rejected.

## Welcome and reset content

Welcome and password reset actions have the same subject, message, greeting, recipient name, and closing fields as a custom message. Administrators can save the welcome subject and message as global defaults under Settings, Email Templates; senders can edit the content in an individual or bulk email when `emails.edit_welcome` is enabled. The global welcome switch controls both the creation checkbox and the Welcome action in email menus. It is off by default, can be overridden with `LARAVEL_USERS_WELCOME_ENABLED`, and is stored only when the optional settings migration is enabled. Defaults come from `emails.welcome_subject`, `emails.welcome_message`, `emails.reset_subject`, and `emails.reset_message`; null uses the translated package default. `emails.edit_welcome` and `emails.edit_reset` control whether senders can change that copy. These switches do not disable the underlying email action.

Drafts are kept separately while switching actions in an open page. Preview and delivery use the edited copy. Reset buttons, expiry notices, and required password-setup notices remain part of the template; a sender cannot remove those by editing the message. Leaving out content fields in an existing API request retains the default notification content. Creation uses the welcome defaults and optional password-setup notice.

## Previewing

Click Preview email to replace the editor with the rendered email. Back to editing appears above the preview and restores the editor with its fields, checkboxes, units, and recipients intact. Sending remains an explicit separate action.

The preview renders the same Blade or Laravel Markdown template used for delivery, including published overrides. Bulk preview uses the first remaining recipient and shows their name above the email. Every recipient is still authorized before the preview is returned. Preview buttons use sample destinations: previewing sends no notification, creates no password token, creates no account link, and changes no account.

The rendered document appears in a sandboxed iframe. Preview responses use `Cache-Control: no-store, private`. Preview and sending share authentication, role middleware, the optional email gate, model-policy checks, CSRF protection, and the email request limit.

## Password reset expiration

The reset dialog contains an expiration number and a minutes, hours, or days selector. The default comes from the selected host password broker. `emails.reset_max_expire` limits a submitted duration in minutes, defaulting to 43200 (30 days). The server validates the converted duration; changing the unit cannot bypass the maximum.

`emails.password_broker=null` uses Laravel's default broker. Its provider must use the configured user model. Sending a link keeps the current password active until the recipient resets it. Links retain native throttling and single-use behavior.

For a default shared by every link from that broker, set `emails.reset_expire` to minutes. Null preserves the host setting. This default applies to links from the application's native recovery form and welcome setup links using that broker as well. Rebuild cached config and restart queue workers after changing it.

Per-email overrides use an authenticated encrypted token envelope around the broker's token. The database keeps a native token hash, with a package marker for a non-expiring token. A newly resolved native host broker verifies the chosen expiration without changing the expiry of ordinary host links. Laravel's expired-token cleanup retains token records for the configured maximum duration so a longer management link survives cleanup; expired ordinary links are still rejected using their original expiry. Reducing that maximum can shorten the retention of previously issued longer links.

Custom password broker manager subclasses are preserved. Per-email overrides require the package's native broker wrapper; if your application replaces that manager or repository, disable `emails.reset_duration` and use your broker's default expiry. Laravel cache-backed brokers keep their native cache behavior; the selected duration is applied when their token repository is created.

## Welcome emails

Welcome mail and password setup are unchecked when creating a user. A normal creation sends no mail. Password setup requires welcome mail, stores an unusable random password, and includes a reset link with a notice that a password must be chosen before signing in. No password is emailed.

Sending welcome mail to an existing user does not change their password. The template instead provides the host's sign-in link when that route exists. A welcome dispatch failure during creation leaves the account intact and reports a warning.

## Deleted-account links

Deleted accounts can receive custom messages individually or in bulk. Reset and welcome actions are not offered for deleted accounts. Restore my account and Permanently delete my account checkboxes are available only when their opt-in settings are enabled. Both are unchecked by default.

Account links require `SoftDeletes`, an existing `deleted_at` column on your user model, and a separate package table on the user model's database connection:

```sh
php artisan vendor:publish --tag=laravelusers-account-links-migrations
php artisan migrate
```

Then enable the feature:

```dotenv
LARAVEL_USERS_SOFT_DELETED_ENABLED=true
LARAVEL_USERS_ACCOUNT_LINKS_ENABLED=true
LARAVEL_USERS_ACCOUNT_LINKS_RESTORE=true
LARAVEL_USERS_ACCOUNT_LINKS_FORCE_DELETE=true
LARAVEL_USERS_ACCOUNT_LINKS_EXPIRE=60
LARAVEL_USERS_ACCOUNT_LINKS_MAX_EXPIRE=43200
```

The dialog provides another number and minutes/hours/days selector for these links. Expiration starts when links are issued, so queue delays consume part of their lifetime. The email includes the chosen duration and single-use notice.

Each link contains an encrypted, authenticated random secret. Only its hash is stored. The stored record binds the action to the exact model, user identifier, email, deletion state, and expiration. Confirmation locks the user and link in a database transaction, checks them again, and claims the link before changing the account. Tests cover concurrent attempts to use the same link.

Opening a link displays a confirmation page. GET requests never restore or delete an account, so opening mail or scanning a link does not perform the action. The confirmation requires a CSRF-protected POST. After one action succeeds, all outstanding links for that account are invalidated. An administrator restore or Eloquent deletion also revokes old links. Changed addresses, expired links, and used links show an invalid-link page with HTTP 410.

These public confirmation routes intentionally do not require administrator middleware or sign-in: a deleted recipient cannot sign in. They instead require the secret, current deleted state, CSRF confirmation, and a separate rate limit. Responses disable caching, framing, search indexing, and referrer disclosure. Serve them over HTTPS with a trusted application URL. Keep `APP_KEY` private and stable; rotating it invalidates outstanding links. A forwarded email grants its holder the same capability as the recipient until use or expiration.

Model events are part of invalidation. Raw SQL and query-builder updates bypass Eloquent events; host code that changes an account this way must revoke its outstanding links itself. Account links are not a replacement for the application's retention policy.

Schedule cleanup using your application's scheduler:

```php
Schedule::command('laravelusers:prune-account-links')->daily()->withoutOverlapping();
```

Use `onOneServer()` when your scheduler runs on multiple servers with a shared lock store. Cleanup deletes expired link records only; it does not remove user accounts.

## Templates and authorization

```sh
php artisan vendor:publish --tag=laravelusers-email-views
```

| Template | Purpose |
| --- | --- |
| `emails/welcome.blade.php` | Laravel Markdown welcome and optional password setup. |
| `emails/reset-password.blade.php` | Laravel Markdown password reset and expiration notice. |
| `emails/message.blade.php` | Escaped custom message HTML. |
| `emails/message-text.blade.php` | Custom message plain text. |
| `emails/deleted-user.blade.php` | Laravel Markdown deleted-user message and optional account buttons. |

Overrides live under `resources/views/vendor/laravelusers/emails`. Publishing without `--force` preserves them. Laravel Markdown supplies both HTML and plain text for its templates.

`emails.gate` can require an application gate in addition to the management middleware. A configured user policy's `update` check applies to every recipient before dispatch. `emails.throttle` limits send and preview requests; `account_links.throttle` separately limits public confirmations. A dispatch error reports how many notifications were queued before the error. Delivery errors after queuing belong to the host's failed-job handling.

## Never expire

Both expiration controls offer Never expire (not recommended). It is unchecked by default. Hide and reject the choice with `emails.reset_allow_never_expire=false` or `account_links.allow_never_expire=false`. Reset expiration overrides also require `emails.reset_duration=true`.

A non-expiring link remains single use and subject to its other checks. Issuing a replacement reset token revokes the previous token; account restoration, deletion, and successful account-link use revoke outstanding account links. Turning off the matching never-expire setting rejects previously issued non-expiring links while it is disabled. Keep it disabled if your application requires a maximum lifetime.

Non-expiring reset records survive native expired-token cleanup. Non-expiring account-link records have a null expiry and are excluded from the prune command. Revocation does not depend on scheduled cleanup. The email and preview explicitly state when a link does not expire. Stable `APP_KEY` storage and the privacy of the delivered email remain necessary.

## Closing and previewing

Cancel, Close and Escape discard the email draft, clear selected recipients and validation messages, reset expiration units and restore configured content defaults. Reopening starts a fresh message. Back to editing from a preview preserves the current draft and recipients.

Custom messages, welcome emails and reset emails use Laravel's Markdown mail layout for matching HTML and plain-text output. `emails/message.blade.php` supplies the custom-message Markdown body. Existing published overrides still win; merge the template change or review a backed-up view update to adopt the styled preview. Previewing never dispatches mail or creates a live account-action token.
