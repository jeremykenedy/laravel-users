# Roles and permissions

Laravel Roles is the preferred package for new integrations. Spatie and compatible custom role models remain supported. Existing installations retain their current role system.

## Supported integrations

Roles remain optional. Existing Laravel Roles integrations and custom models using the original role contract continue to work. Spatie Laravel Permission is also supported without making either package a required dependency.

| Selection | Package | User model trait | Default role model |
| --- | --- | --- | --- |
| `laravel-roles` | `jeremykenedy/laravel-roles` | `jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission` | `jeremykenedy\LaravelRoles\Models\Role` |
| `spatie` | `spatie/laravel-permission` | `Spatie\Permission\Traits\HasRoles` | `Spatie\Permission\Models\Role` |
| `keep` | Existing or custom integration | Your existing implementation | Existing `roleModel` |
| `none` | None | Not required | Role UI disabled |

Use one role system on the user model. Existing hosts may already have both packages as dependencies, but their competing `roles` relationships cannot be mixed on the same model. Setup commands and settings block installing a second roles package. Existing installed integrations can still be configured explicitly.

## Installer choices

Install and update ask which package you use. Keep is the default and changes no role settings. An unattended update with no `--roles` flag also preserves every existing role setting.

```sh
php artisan laravelusers:update --roles=laravel-roles
php artisan laravelusers:update --roles=spatie --role-middleware="role:admin"
```

For a missing package, the command offers to run Composer, with No as the default. Otherwise it prints installation instructions. Explicit unattended installation is available:

```sh
php artisan laravelusers:install --roles=spatie --install-roles --no-interaction
```

Composer runs in the host application and resolves a version compatible with its requirements. Its normal scripts and package discovery apply. Review the resulting `composer.json` and lock file. A Composer failure stops before Laravel Users writes configuration or views. Composer itself may have changed the host dependency files before failing; inspect its output before retrying.

The command shows the model trait and migration instructions. It does not rewrite your model, migrate role tables, create roles, assign administrator privileges, or run seeds. Complete those host-owned steps, then rerun update to enable the integration. A newly installed dependency also needs a fresh Artisan process so its provider and model trait are loaded. Role UI is not enabled while the configured user model lacks its package's trait.

Once ready, the command saves settings in `config/laravelusers-roles.php`, keeping the main package configuration intact. That file overrides the four corresponding role settings and uses their existing environment variable names. Remove it to return to the main config. `--roles=none` explicitly disables role UI; additional management middleware remains unchanged.

For custom role models or implementations, choose Keep and configure `roleModel`, `rolesEnabled`, and middleware yourself. This retains the original contract rather than replacing it with a package default.

## Laravel Roles setup

Install [Laravel Roles](https://github.com/jeremykenedy/laravel-roles) and add `HasRoleAndPermission` to the user model selected by `defaultUserModel`. Follow its installed version's migration instructions. Register its `role` middleware alias if your application does not already provide it, and assign an administrator role before restricting management to that role.

The package uses `attachRole()` when creating an account and `detachAllRoles()` followed by `attachRole()` when replacing roles. Compatible custom models need those methods and a `roles` relationship. `level()` remains supported where available; it is not required for Spatie models.

## Spatie setup

Install [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) and add `HasRoles` to your configured user model. Publish and review its config and migrations, create the tables, and register middleware aliases according to your installed version. Spatie 5 uses the `Middlewares` namespace; later releases use `Middleware`.

Set `roleModel` to your configured Spatie role model if you customize it. The package uses `assignRole()` for creation and `syncRoles()` for editing, passing role objects so submitted string identifiers are handled correctly. Choices respect the user's default guard. With Spatie teams enabled, choices respect the current permission team and globally available roles. Your host middleware must establish the current team before reaching user management.

## Selecting and displaying roles

When enabled, the create form offers a role selector and the edit form allows multiple roles. Assigned roles appear in active and deleted directories, card views, profile screens, and edit cards. Submitted identifiers must refer to available roles. A failed role assignment rolls back user changes on the user's database connection. Keep role tables and user records on that connection.

## Management middleware

The historically spelled `rolesMiddlwareEnabled` and `rolesMiddlware` keys remain supported. They apply when roles are enabled. The newer `middleware` option applies independently of role UI, including to search, bulk actions, email, and previews:

```php
'middleware' => ['auth', 'can:manage-users'],
'rolesEnabled' => true,
'rolesMiddlwareEnabled' => true,
'rolesMiddlware' => ['role:admin', 'permission:manage users'],
```

Register aliases in your host application. Laravel 8 through 10 commonly use `app/Http/Kernel.php`; newer application skeletons use `bootstrap/app.php`. Preserve your application's existing registration method.

`LARAVEL_USERS_MIDDLEWARE` accepts comma-separated middleware names. Put middleware arguments containing commas in the config array instead. Installer `--role-middleware` accepts one middleware or a semicolon-separated list, such as `"role:admin;permission:manage users"`. The role middleware environment override is a single Laravel middleware string; use a config array for multiple middleware.

Authentication alone does not restrict ordinary users to administrator actions. Add the required gate or role middleware before exposing management. Public deleted-account confirmation routes use their own secret, CSRF protection, and throttle rather than management authorization; see [account links](emails.md#deleted-account-links).

## Direct permissions and role levels

Set `permissionsEnabled=true`, or `LARAVEL_USERS_PERMISSIONS_ENABLED=true`, to allow direct-permission selection when roles are enabled and the user model supports the selected package. It is off by default. `permissionModel=null` follows the permission model configured by Laravel Roles or Spatie. Set a model explicitly for a compatible custom integration.

Create and edit forms provide a multiple-selection field for direct permissions. Submitted IDs must exist and, for Spatie, match the user's guard. Clearing the field removes direct assignments only. Permissions inherited from roles remain controlled by that roles package. Older requests that omit the permission fields preserve direct assignments on update. Requests to modify permissions are ignored while this feature is disabled.

Set `permissionsGate` to an application gate to require separate authorization for assigning direct permissions. A denied gate or invalid permission rolls back user and role changes on the user's connection. The gate is additional to management middleware. Keep permission and role pivots on the user connection for those transactions to cover all assignments.

Profile screens show direct permissions when enabled. Edit screens show selected direct permissions. Laravel Roles choices show each role's level when `showRoleLevels=true`. The user's effective level follows their assigned roles and is shown on the profile. Shared role levels are managed in the roles package, not changed through a user form. Spatie does not require a level column or `level()` method.
