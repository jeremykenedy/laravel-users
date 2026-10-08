# Changelog

## Unreleased

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
