# Changelog

This is the canonical release history. The [documentation changelog](docs/changelog.md) provides a version index; [upgrading](docs/upgrading.md) covers application changes and rollback.

Published dates below are GitHub release dates in UTC. Entries marked **tag only** use the tagged commit's UTC date because no GitHub release record is available for that tag. Historical notes are checked against the published release descriptions and tagged source differences. Versions are listed by version number, including early tags whose commit dates are out of order.

<a id="v6-0-0"></a>

## 6.0.0 - Unreleased

Planned release date: **2026-10-09**. These notes describe the prepared release; the tag and release remain pending final runtime verification.

### Compatibility

- Keep Blade and Bootstrap 4 as defaults, with Bootstrap 5 available by explicit selection. Further CSS frameworks and application runtimes remain on the [roadmap](docs/roadmap.md).
- Retain the PHP 8.1 floor, Laravel 8 compatibility, existing route names, original configuration defaults, and application-owned view overrides.
- Keep optional features, dependencies, and migrations opt-in. No migration alters the host users table and Composer updates do not publish files or switch frameworks.

### Added

- Add responsive Bootstrap 5 Blade pages, light/dark/system themes, optional table and card views, sorting, filters, saved column visibility, search debounce, and readable local dates.
- Add separately authorized global settings and per-user preferences, with independent light/dark card colors, gradient highlights, strength controls, reset/inheritance controls, and live avatar previews using four fixed sample identities.
- Add local avatar generation, optional DiceBear libraries, larger image resolution, and per-user source selection without adding user columns.
- Add configurable inline alerts, optional Laravel Toast, or both. Preview unsaved notification options entirely in the browser before saving.
- Add queued optional-package installation, configuration, and removal with explicit authorization, typed confirmation, a verified queue/worker round trip, safe progress messages, and automatic Toast setup.
- Add optional login details and session-aware online presence, standalone navigation components, and guarded impersonation with expiration, session rotation, current-access checks, and excluded package login tracking.
- Add opt-in account profile, avatar, appearance, password, and deletion controls, plus email changes requiring confirmation from both old and new addresses.
- Add soft-deleted user management, bulk delete/restore/permanent deletion, opt-in retention cleanup, and encrypted single-use recovery/deletion links with explicit expiry choices.
- Add personalized welcome, password reset, custom, goodbye, and deleted-account email actions, shared individual/bulk composition, previews, editable templates, and configurable expiration including optional never-expiring links.
- Add direct-permission selection for Laravel Roles and Spatie, guard/team validation, and role-level access rules without changing shared role levels.
- Add install, update, switch, and standalone publish commands with compatible interactive prompts, safe view backups, versioned atomic asset publication, and host route updates parsed with PHP-Parser.

### Changed

- Align page icons, 74px page headers, tab titles, and page content within the shared container. Keep breadcrumbs and inline alerts aligned with the same content width.
- Present account appearance as two light/dark panels without a redundant inner card. Match the deletion panel to the other account tabs and keep its heading and explanation white in dark mode.
- Link the header brand to the available host home route, with the user directory as a fallback; compact account-menu login details while retaining full-value tooltips.
- Show completed package setup as a plain checked sentence, refresh settings after completed jobs, and retain separate requirement verification and operation status.
- Expand setup, feature, upgrade, and historical release documentation, with current interface screenshots.

### Fixed

- Correct legacy decoded/text JSON search handling, escape search and translated user values, reject HTML usernames, and respect the configured user table/connection during uniqueness validation.
- Preserve blank edit passwords and use transactions for user/role changes, bulk operations, account updates, and single-use account links.
- Recheck permissions, roles, levels, and configured middleware before queued dependency changes and during impersonation. Invalidate impersonation when the actor's credentials change.
- Reject unauthorized goodbye-template overrides while retaining explicitly configured automatic notices; validate malformed current passwords before account changes.
- Preserve host routes, custom configuration, published templates, and unrelated dependencies during setup; verify Composer metadata before reporting completion.
- Recover package status polling from temporary Composer dependency-discovery errors and show the current completion state after the automatic refresh.
- Persist explicit role selections over stale role environment overrides while preserving unrelated values, private file permissions, and environment-file symlinks. Default and `keep` selections leave the environment unchanged.
- Preserve unsaved email edits when returning from preview, clear closed compositions, maintain search focus after dialogs, and apply account gradient highlights when saved.

<a id="v5-0-0"></a>

## 5.0.0 - 2026-01-08

- Require PHP 8.1 or newer, add PHP 8.4 compatibility, and remove the `laravellux/html` dependency.
- Replace bundled form-builder markup with HTML forms and update route/controller compatibility.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/V5.0.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v4.5.0...V5.0.0)

<a id="v4-5-0"></a>

## 4.5.0 - 2024-10-29

- Replace `laravelcollective/html` with `laravellux/html` and update Composer requirements.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v4.5.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/V4.4.0...v4.5.0)

<a id="v4-4-0"></a>

## 4.4.0 - 2023-11-02

- Add Laravel 9 compatibility through the HTML dependency constraint.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/V4.4.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v4.3.0...V4.4.0)

<a id="v4-3-0"></a>

## 4.3.0 - 2021-01-03

- Update the PHP constraint for PHP 8 support.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v4.3.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v4.2.0...v4.3.0)

<a id="v4-2-0"></a>

## 4.2.0 - 2020-09-21

- Add Laravel 8 support.
- Remove `alpha_dash` from changed-email validation so valid email addresses can be saved.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v4.2.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v4.1.3...v4.2.0)

<a id="v4-1-3"></a>

## 4.1.3 - 2020-05-22

- Update development dependencies and the test fixture for Laravel 7; adjust the test configuration.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v4.1.3) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v4.1.2...v4.1.3)

<a id="v4-1-2"></a>

## 4.1.2 - 2020-05-22

- Strip HTML tags from usernames when creating and updating users.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v4.1.2) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v4.1.1...v4.1.2)

<a id="v4-1-1"></a>

## 4.1.1 - 2020-05-22

- Use Laravel's `Hash` facade for password hashing.
- Update name validation and its translated error message.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v4.1.1) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v4.1.0...v4.1.1)

<a id="v4-1-0"></a>

## 4.1.0 - 2020-04-26

- Extend the HTML dependency constraint for Laravel 7.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v4.1.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v4.0.0...v4.1.0)

<a id="v4-0-0"></a>

## 4.0.0 - 2020-04-26

- Support selecting multiple roles in the edit interface.
- Add Arabic, German, and Dutch translations and correct back-navigation tooltips.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v4.0.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.5.1...v4.0.0)

<a id="v3-5-1"></a>

## 3.5.1 - 2019-09-05

- Update the historical Travis test configuration. No package runtime files changed from v3.5.0.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v3.5.1) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.5.0...v3.5.1)

<a id="v3-5-0"></a>

## 3.5.0 - 2019-09-05

- Add Laravel 6 support to the dependency constraint and installation documentation.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v3.5.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.4.0...v3.5.0)

<a id="v3-4-0"></a>

## 3.4.0 - 2019-05-20

- Extend Laravel's routing controller instead of requiring the host application's controller class.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v3.4.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.3.0...v3.4.0)

<a id="v3-3-0"></a>

## 3.3.0 - 2019-04-05

- Add Brazilian Portuguese translations and update installation/testing documentation.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v3.3.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.2.0...v3.3.0)

<a id="v3-2-0"></a>

## 3.2.0 - 2019-03-23

- Extend dependency support for Laravel 5.7 and 5.8 and refresh development requirements.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v3.2.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.1.1...v3.2.0)

<a id="v3-1-1"></a>

## 3.1.1 - 2018-09-06

- Fix editing users when roles are disabled.
- Update Blade translation calls and clarify configuration publishing.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v3.1.1) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.1.0...v3.1.1)

<a id="v3-1-0"></a>

## 3.1.0 - 2018-06-02

**Tag only.** Date identifies the tagged commit, not a published release.

- Make the four user-management view names configurable.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v3.1.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.0.4...v3.1.0)

<a id="v3-0-4"></a>

## 3.0.4 - 2018-02-26

**Tag only.** Date identifies the tagged commit, not a published release.

- Apply PHP formatting fixes to the controller and English form translations. No functional change is shown by the tag comparison.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v3.0.4) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.0.3...v3.0.4)

<a id="v3-0-3"></a>

## 3.0.3 - 2018-02-26

**Tag only.** Date identifies the tagged commit, not a published release.

- Restore the full user list when the search field is emptied and clear the input when resetting search.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v3.0.3) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.0.2...v3.0.3)

<a id="v3-0-2"></a>

## 3.0.2 - 2018-02-26

**Tag only.** Date identifies the tagged commit, not a published release.

- Disable role assignments by default and update configuration documentation.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v3.0.2) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.0.1...v3.0.2)

<a id="v3-0-1"></a>

## 3.0.1 - 2018-02-26

**Tag only.** Date identifies the tagged commit, not a published release.

- Add user search, its route, form, and result script.
- Raise the PHP requirement to 7.1.3 and update the package provider and test configuration.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v3.0.1) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v3.0.0...v3.0.1)

<a id="v3-0-0"></a>

## 3.0.0 - 2018-02-24

**Tag only.** Date identifies the tagged commit, not a published release.

- Move bundled views and confirmation modals to Bootstrap 4.
- Extend support to Laravel 5.6 and add the initial Testbench/PHPUnit setup.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v3.0.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v2.0.2...v3.0.0)

<a id="v2-0-2"></a>

## 2.0.2 - 2018-02-11

- Translate deletion success and self-deletion messages.
- Use stable dependency resolution and add the historical Travis configuration.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v2.0.2) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v2.0.1...v2.0.2)

<a id="v2-0-1"></a>

## 2.0.1 - 2018-02-10

- Raise the PHP requirement to 7.0 and disable optional roles by default.
- Refresh package metadata and clarify the unimplemented soft-delete option in that version.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v2.0.1) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v2.0.0...v2.0.1)

<a id="v2-0-0"></a>

## 2.0.0 - 2018-02-10

- Add published package configuration for authentication, roles, user models, layouts, pagination, and interface options.
- Refactor the controller, templates, styles, and translations around those settings.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v2.0.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v1.5.0...v2.0.0)

<a id="v1-5-0"></a>

## 1.5.0 - 2017-09-04

- Add Laravel 5.5 package discovery and extend the HTML dependency for Laravel 5.4/5.5.

[Release](https://github.com/jeremykenedy/laravel-users/releases/tag/v1.5.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v1.4.0...v1.5.0)

<a id="v1-4-0"></a>

## 1.4.0 - 2017-05-20

**Tag only.** Date identifies the tagged commit, not a published release.

- Update the Laravel Collective HTML constraint from `^5.2.0` to `^5.3.0`.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v1.4.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v1.3.0...v1.4.0)

<a id="v1-3-0"></a>

## 1.3.0 - 2017-05-20

**Tag only.** Date identifies the tagged commit, not a published release.

- Document version-specific installation choices for Laravel 5.2, 5.3, and 5.4. No runtime files changed from v1.2.0.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v1.3.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v1.2.0...v1.3.0)

<a id="v1-2-0"></a>

## 1.2.0 - 2017-05-20

**Tag only.** Date identifies the tagged commit, not a published release.

- Allow Laravel Collective HTML `^5.2.0` for Laravel 5.2 installations.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v1.2.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v1.0.2...v1.2.0)

<a id="v1-0-2"></a>

## 1.0.2 - 2017-05-20

**Tag only.** Date identifies the tagged commit, not a published release.

- Include the case-sensitive `src/App` directory correction and expand older-Laravel installation guidance.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v1.0.2) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v1.0.1...v1.0.2)

<a id="v1-0-1"></a>

## 1.0.1 - 2017-03-17

**Tag only.** Date identifies the tagged commit, not a published release.

- Enable view publishing and update the language-file publication destination.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v1.0.1) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v1.0.0...v1.0.1)

<a id="v1-0-0"></a>

## 1.0.0 - 2017-02-25

**Tag only.** Date identifies the tagged commit, not a published release.

- Publish the first 1.0 tag with installation documentation updates. Its runtime tree matches v0.0.3rc.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v1.0.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v0.0.3rc...v1.0.0)

<a id="v0-9-0"></a>

## 0.9.0 - 2017-03-29

**Tag only.** Date identifies the tagged commit, not a published release.

- Correct the `App` directory casing for autoloading on case-sensitive filesystems.
- This tag points to a March 2017 commit after the commits used by v1.0.0 and v1.0.1; version order does not describe its chronology.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v0.9.0) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v1.0.1...v0.9.0)

<a id="v0-0-3rc"></a>

## 0.0.3rc - 2017-02-25

**Tag only.** Date identifies the tagged commit, not a published release.

- Tag the same commit as v0.0.2 (`4ee425f`), with no additional source changes.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v0.0.3rc) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v0.0.2...v0.0.3rc)

<a id="v0-0-2"></a>

## 0.0.2 - 2017-02-25

**Tag only.** Date identifies the tagged commit, not a published release.

- Continue the package conversion with namespaced controller routes, web middleware, and package view references.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v0.0.2) | [Changes](https://github.com/jeremykenedy/laravel-users/compare/v0.0.1...v0.0.2)

<a id="v0-0-1"></a>

## 0.0.1 - 2017-02-25

**Tag only.** Date identifies the tagged commit, not a published release.

- Add the initial package provider, user-management controller, routes, Blade views, English translations, and Composer metadata.

[Tag](https://github.com/jeremykenedy/laravel-users/tree/v0.0.1)
