# Changelog

## Unreleased

- Reject malformed current passwords before changing or deleting an account.
- Preserve first-class route callables during middleware setup.
- Verify Composer installed metadata before reporting package changes as completed.
- Make theme controls available during asset loading and preserve search focus when email dialogs close.
- Report executable PHP coverage separately from templates, generated assets and translation data.
- Add versioned public assets to install, update, switch, and standalone publish commands, with staging, hash verification, publication locks, and a bundled fallback.
- Use native Laravel Prompts for supported interactive environments while preserving current defaults and the older console fallback.
- Protect impersonation on host web routes with encrypted session proofs, current authorization checks, configurable expiration, and session rotation.
- Parse host route changes with PHP-Parser while preserving custom routes, comments, dynamic middleware, and rollback compatibility.
- Add independent global and per-user dark-mode card colors, gradient switches and strength controls while preserving existing appearance defaults.
- Fix settings overflow caused by hidden fieldset legends and link to Laravel queue, worker and cache setup instructions.

- Add optional saved global settings with action-specific role, permission and level access rules.
- Add individual card colors and gradient strength through separate additive migrations.
- Add color and gradient reset controls, live previews, and independent profile/edit defaults.
- Add local avatar generators, optional local DiceBear dependencies, larger image requests, and avatar setup flags.
- Add dismissible notifications and optional Laravel Toast setup to install, update and switch commands.
- Add separately authorized queued Toast and roles package management with typed confirmations, dependency checks and duplicate-role prevention.
- Add standalone avatar-menu and theme-toggle Blade components for host layouts.
- Bound long name and email table cells without truncating their underlying content.
- Fix custom email preview styling and clear email forms on close while preserving drafts when returning from preview.
- Fix deleted-card action overflow and settings layout across all bundled frameworks.
- Add settings, component, avatar, dependency-management and upgrade documentation.

- Add opt-in per-user avatar sources with a separate migration, inherited defaults, and no changes to host user columns.
- Add create-password strength and matching feedback while preserving existing password length defaults.
- Add optional direct-permission assignment for Laravel Roles and Spatie, with guard validation and role-level display.
- Add shared individual/bulk email actions, recipient chips, preview/back editing, and editable welcome/reset templates.
- Add selectable reset and account-link expiration, including an unchecked configurable Never expire choice.
- Add encrypted single-use deleted-account restore and permanent-delete links with CSRF confirmation and concurrent-use protection.
- Add explicit optional role-package setup to install, update, and switch commands with an ASCII banner and preserved main config.
- Add real optional-role integration CI jobs, upgrade guides, and current browser screenshots.

- Restore icon input groups, button icons, and modal confirmations in modern views.
- Add configurable search debounce, compact mobile actions, table filters, sorting, saved column visibility, and responsive entries.
- Add optional avatars, email links, distinct online and login columns, readable local dates, and page-aware totals.
- Add optional welcome emails and password setup links through the host password broker.
- Add separate soft-deleted user management and optional bulk delete, restore, and permanent deletion.
- Support custom headers, footers, hidden navigation, and optional logout.
- Add environment fallbacks for every configuration option without removing existing keys.
- Reject HTML usernames on create and edit.

- Add opt-in latest-login details and session-aware online status without changing the users table.
- Add a quick switch command and CSS selection aliases while retaining existing command options.
- Add regression coverage for decoded and text search responses, disabled search, and host asset switches from issue #90.
- Keep Bootstrap 4, existing routes, config keys, and publish behavior as the default.
- Add optional Bootstrap 5 and Tailwind Blade views with responsive forms, tables, search, and pagination.
- Add light, dark, and system themes with an optional persistent icon button matching Laravel Logger.
- Add interactive install and update commands, safe view publication, and backups before forced replacement.
- Add optional integration setup instructions without introducing runtime dependencies.
- Fix legacy JSON search handling and escape user values in search results and translated headings.
- Respect the configured user table and connection during uniqueness validation.
- Preserve blank edit passwords and protect user/role changes with database transactions.
- Cover CRUD, roles, frontend rendering, publication, and upgrades with Testbench and browser tests.
- Replace obsolete Travis configuration with GitHub Actions, repair Dependabot, and add dependency audits and asset build checks.
- Refresh documentation, add theme-aware README graphics, and update the license year to 2026.

## 5.0.0

- Add PHP 8.4 support and remove the `laravellux/html` dependency.

## 4.5.0

- Use `laravellux/html` and update package dependencies.

## 4.4.0

- Update Laravel 9 compatibility.

## 4.3.0

- Add PHP 8 compatibility.

## 4.2.0

- Add Laravel 8 support.

## 4.0.0

- Add multiple roles in the interface and additional languages.
