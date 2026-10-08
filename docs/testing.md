# Testing

## PHP suite

`composer check` runs Pint and PHPUnit. The package uses Orchestra Testbench and SQLite, with a test user model and a small role API fixture. Tests exercise HTTP routes rather than only calling controller methods. The role fixture verifies the package's existing model contract without requiring an optional package.

CI tests these combinations:

| Laravel | PHP | Testbench |
| --- | --- | --- |
| 8 | 8.1 | 6 |
| 9 | 8.1 | 7 |
| 10 | 8.1 | 8 |
| 11 | 8.2 | 9 |
| 12 | 8.2, 8.3, 8.4 | 10 |
| 13 | 8.3, 8.4, 8.5 | 11 |

Laravel 13 also has a lowest-dependency job. Historical Laravel jobs explicitly allow Composer to resolve dependencies affected by upstream advisories so backward compatibility remains testable. The current dependency job runs `composer audit` without that exception. Package users do not inherit CI-only Composer flags.

Scrutinizer uses the Jammy build image with PHP 8.2 and a SQLite version supported by Laravel. It runs PHPUnit coverage, static analysis, and `composer lint` in its named analysis node. The repository configuration explicitly lists these commands so website build overrides cannot omit the test suite. Pint checks the same repository standard locally and in both CI services. Website analysis and coding-style settings remain active; the obsolete CodeSniffer wrapper is replaced by the package's lint command.

The quality job exports coverage for inspection. Coverage counts supplement behavioral assertions; they do not establish compatibility with untested host customizations.

Activity tests exercise an HTTP login and Laravel authentication events. They cover disabled defaults, latest-login replacement, trusted proxies, custom models, string identifiers, expiry, multiple sessions, logout, session regeneration, cleanup, and migration rollback. Store failures must be reported without preventing login. Activity records stay out of the default search JSON. Bundled views request only listing metadata, excluding login IP and agent details. Listing tests check one query for login times rather than a query per row.

## Browser suite

```sh
npm ci
npx playwright install chromium firefox webkit
npm run test:browser
```

Playwright starts a local PHP server on `127.0.0.1:19847`. The fixture uses an isolated SQLite database and sessions under ignored `tests/browser/runtime`. It creates sample accounts for tests only. A preview served on port 19849 uses a separate temporary runtime with three friendly sample users and the normal page size. It never shares the browser suite's injection fixture or test accounts. No application database is used. Do not serve the fixture publicly.

The suite exercises all three frontends in Chromium, Firefox, and WebKit. It checks search with literal hostile-looking names, theme button cycling and keyboard activation, persistence, disabled controls, system preference changes, mobile overflow, and modern account creation, validation, editing, and deletion. Axe checks the modern form in light and dark mode. The fixture runs real CSRF middleware and submits real forms.

Legacy search regression tests cover both `application/json` responses decoded by jQuery and JSON text returned as `text/html`, including empty results and browser errors. PHP rendering tests verify the disabled search setting and host asset switches in all three frameworks.

The legacy Bootstrap 4 browser tests load its existing external CDN assets. Modern Bootstrap 5 loads its configured CSS CDN. Tailwind loads its bundled stylesheet. Browser artifacts are uploaded on CI failure. Tests that only pass on retry also fail CI.

## Asset builds

`npm run build` compiles prefixed Tailwind utilities into the bundled Blade stylesheet. Commit the generated stylesheet with source changes. CI rebuilds it and fails if the generated result differs from the committed version. No build runs during Composer installation in consuming applications.

## Manual checks

Review list, create, edit, and detail views on desktop and mobile. Check published overrides, custom parent layouts, host authentication and authorization, role models, translations, asset loading, and any custom JavaScript before upgrading a consuming application.

The table tests cover debounce cancellation and immediate submission, column sorting and filtering, stored visibility choices, avatar image failures, timezone formatting, compact actions, mobile entries, and disabled options. Bulk tests use real requests to create users, soft-delete them, visit the separate deleted table, restore them, and permanently delete them. Server tests reject invalid or oversized selections and self-deletion before changing any selected account.

Welcome tests confirm that ordinary creation sends no mail, only validated fields are saved, passwords are not emailed, disabled choices cannot be submitted, and password setup tokens work once. A mail dispatch failure leaves the created account intact and reports a warning. Create and edit tests reject HTML usernames.
