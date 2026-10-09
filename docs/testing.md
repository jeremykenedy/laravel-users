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

Laravel 13 also has a lowest-dependency job. Historical Laravel jobs explicitly allow Composer to resolve dependencies affected by upstream advisories so backward compatibility remains testable. The current dependency job runs `composer audit` without that exception. The Laravel 8 SQLite job adds Doctrine DBAL 3.x only to its test dependencies so migration rollback is exercised using the framework's supported schema tools. Package users do not inherit CI-only Composer flags.

GitHub Actions runs the compatibility matrix, optional integration tests, Pint, dependency audits, PHP coverage, documentation linting, and the Playwright suite. StyleCI, CodeFactor, and Codacy provide separate code-quality checks.

The workflow uses Ubuntu 24.04 explicitly so changes to GitHub's `ubuntu-latest` label do not replace the tested operating system. Review the PHP and browser jobs before adopting a newer runner image.

The quality job exports coverage for inspection. It does not yet enforce the requested 100% coverage target. Coverage counts supplement behavioral assertions; they do not establish compatibility with untested host customizations. Local results also do not confirm GitHub Actions or external quality ratings for an unpublished commit.

`phpunit.coverage.xml` is the PHPUnit 12 coverage configuration used by that job. It includes all package PHP logic, configuration, routes and migrations, including uncovered files. It excludes `src/resources`, which contains template sources, generated assets and translation data. Blade executes through compiled templates, so counting its source as unexecuted PHP gives a misleading report. Browser behavior is checked separately by Playwright. The ordinary `phpunit.xml` remains compatible with the older PHPUnit versions in the Laravel matrix.

The quality job combines a core run without optional packages with a second run using the real roles, avatar and Toast libraries. Both runs use the same source filter. Their combined Clover report includes integration code that is unavailable in the ordinary dependency matrix.

With PHPUnit 12 and Xdebug installed, generate the core report locally:

```sh
XDEBUG_MODE=coverage vendor/bin/phpunit --configuration phpunit.coverage.xml --coverage-clover coverage.xml
```

Codacy excludes dependency directories, temporary browser data, and generated Tailwind output. PHP analyzers inspect package PHP rather than treating Blade templates as standalone PHP files. `phpmd.xml` permits Laravel facades and the package's existing stateless helpers while retaining complexity and unused-code checks. `phpcs.xml` aligns PSR checks with Pint's operator spacing, multiline conditions and anonymous-class formatting; it preserves legacy protected names and PHPUnit test naming. Pint remains the required formatter. `.markdownlint.json` allows the banner, badges, and screenshot tables required by this README. Tool configuration files must be enabled in Codacy after they are available on its default analysis branch.

Activity tests exercise an HTTP login and Laravel authentication events. They cover disabled defaults, latest-login replacement, trusted proxies, custom models, string identifiers, expiry, multiple sessions, logout, session regeneration, cleanup, and migration rollback. Store failures must be reported without preventing login. Activity records stay out of the default search JSON. Default listing metadata excludes login IP and agent details. Bundled tables request those fields explicitly when the login-details column is enabled. Listing tests check one query for login records rather than a query per row.

Command tests cover native Laravel Prompts with simulated key presses as well as the older console fallback. Publication tests verify real asset hashes, idempotent releases, preserved custom files, failed staging, malformed manifests, and rejected symbolic links. AST tests preserve route aliases, namespaces, comments, first-class route callables, dynamic expressions, and concurrent host edits. Invalid PHP stops setup before configuration or assets are written.

Impersonation tests reject modified or legacy session state before host routes execute, verify guard and target binding, restore the actor on revoked access, retain the exit route after expiration, and suppress package login activity on both transitions. Ordinary host requests remain unchanged when the feature is disabled.

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

Published-asset browser tests verify CSS and JavaScript content types, working tabs and search, and the `.laravel-users-main-card` boundary at 375x812, 768x1024, and 1440x900. Theme controls must work while a later JavaScript asset is still loading. Closing an email dialog must clear its draft and preserve focus when the user moves to search. The fixture uses the checked-out package views so stale Testbench publications cannot mask a source change.

## Asset builds

`npm run build` compiles prefixed Tailwind utilities into the bundled Blade stylesheet and its public CSS asset. Commit both generated files with source changes. CI rebuilds them and fails if the generated results differ from the committed versions. No build runs during Composer installation in consuming applications.

## Manual checks

Review list, create, edit, and detail views on desktop and mobile. Check published overrides, custom parent layouts, host authentication and authorization, role models, translations, asset loading, and any custom JavaScript before upgrading a consuming application.

Appearance tests cover independent light/dark global and individual settings, omission-preserving updates, inherited defaults, reset controls, authorization, validation and additive migration rollback. Settings width checks cover 320px through 1280px, around both grid breakpoints, with full width enabled and disabled.

Card tests verify saved view choices, configurable grid counts, toolbar order, mobile icon alignment, pinned footers, selection controls, and accessibility. The table tests cover debounce cancellation and immediate submission, column sorting and filtering, stored visibility choices with merged headers, avatar image failures, profile cards, configurable timezone formatting, compact actions, red delete confirmations, mobile entries, and disabled options. Bulk tests use real requests to create users, soft-delete them, visit the separate deleted table, restore them, and permanently delete them. Server tests reject invalid or oversized selections and self-deletion before changing any selected account.

Welcome tests confirm that ordinary creation sends no mail, only validated fields are saved, passwords are not emailed, disabled choices cannot be submitted, and password setup tokens work once. A mail dispatch failure leaves the created account intact and reports a warning. Create and edit tests reject HTML usernames.

Email tests cover per-recipient authorization before dispatch, disabled actions, request limits, personalized messages, HTML escaping, queued delivery, broker selection, expiry, throttling, and single-use reset tokens. Expiry tests verify that a newly resolved native host broker rejects expired tokens and that an unset override preserves host settings. Welcome templates are rendered as HTML and plain text with and without password setup. Password tests cover unchanged default limits, optional stronger rules, and preserving blank passwords. Browser tests check the shared email dialog in all bundled frameworks, hidden selection controls for the current user, and the Bootstrap 5 edit-card layout and password meter.

## Optional integration jobs

The role-integration CI matrix installs real Laravel Roles alongside Spatie 5 on Laravel 8, Spatie 6 on Laravel 12, and Spatie 8 on Laravel 13. It tests assignment, multiple roles, guard restrictions, direct permissions, inherited permissions, middleware, and installer readiness. Ordinary package tests skip those integrations when the optional dependencies are absent; they remain optional runtime dependencies.

Per-user avatar tests verify disabled defaults, a missing optional migration, inherited settings, explicit choices, all three forms, search metadata, batched queries, safe URLs, soft-delete restoration, permanent deletion, and transaction rollback on the host connection. Create-password tests preserve the existing unbounded create maximum and enforce configured stronger rules.

Email coverage includes editable welcome/reset content, escaped templates, preview without side effects, minutes/hours/days, non-expiring single-use tokens, cleanup behavior, disabled switches, CSRF rejection, public-link throttling, and concurrent consumption. Browser coverage checks preview/back state, recipient chips, expiry inputs, and create-password feedback across all bundled frameworks.

The isolated automated browser fixture raises only its send/preview request limit to avoid a shared-actor rate limit across sequential tests. Production defaults and the friendly preview retain their normal limit. PHP tests exercise the production limits directly.

## Settings and dependency-change coverage

Settings tests verify opt-in defaults, missing migrations, host gates, forbidden fields, persistence, self-lockout protection and route/search/bulk/email-preview restrictions. Real roles tests cover direct and inherited permissions, minimum levels, guards and team boundaries. Appearance tests cover nullable inheritance, strength limits, transaction behavior and cleanup after permanent Eloquent deletion.

Package-management tests use a mocked Composer boundary so they cannot install or remove dependencies from the test application. They verify exact confirmation words, acknowledgement, the package allowlist, the second-roles-package block, unsafe removal, queue timeouts, pending-operation locks, status ownership, authorization revocation before execution and duplicate-job handling. Browser tests verify the warning modals, disabled confirmations, cleared fields and visible backend rejections. Composer installation is tested through the separate real optional-dependency jobs; this is distinct from testing a complete deployment's web package-removal workflow.

The presentation integration matrix installs actual DiceBear core/styles and Laravel Toast on Laravel 12 and 13. It tests local SVG generation, installed notification rendering and settings availability. Package setup tests run the real optional installers, preserve host configuration, leave features disabled and reject cached configuration. A deliberately failing host migration proves that setup runs only the selected package migrations. Optional integrations remain absent from the normal dependency matrix. Each suite explicitly reports applicable skips.

Standalone component browser tests render an application-owned page without `#laravelusers`, check theme persistence and synchronized controls, and verify outside-click/Escape behavior. Long-name/email tests cover bounded horizontal scrolling, keyboard access, preserved mail links and mobile card wrapping in all three frameworks. Settings browser tests include color/gradient resets, sliders, persistence, small-screen overflow and modern accessibility.

Composer process tests use disposable application directories and local executable fixtures. They check fixed command arguments, disabled scripts/plugins, manifest refresh, malformed installed metadata, graceful worker restart, failed commands, and dependencies that remain installed. They never change the working application's Composer files. Account tests reject non-string current passwords before dispatching mail or changing an account. Navigation tests verify that host gate changes hide the management link and malformed ability names fail closed.

Worker tests check explicit migration choices, failed installation or setup, concurrent Composer locks and expired operation ownership. An older job cannot release a newer operation's lock. Account setup tests also prove that unrelated migrations and the host users table stay unchanged.
