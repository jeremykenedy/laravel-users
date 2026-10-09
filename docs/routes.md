# Routes and authorization

Routes are registered by the package service provider. Management routes require the configured authentication guard. Additional middleware in `laravelusers.middleware` applies to management routes. Role middleware is applied when `rolesEnabled` and `rolesMiddlwareEnabled` are enabled.

Authentication alone does not make a user an administrator. Set host authorization middleware or configure the package's optional action access rules before granting access to user-management routes. Account settings have their own feature middleware and host gate. Public account-link routes are unauthenticated by design and require the encrypted, expiring, single-use link flow and a confirmation step.

| Method | URI | Name | Feature |
| --- | --- | --- | --- |
| GET | `/users` | `users` | Active user directory |
| GET | `/users/create` | `users.create` | Create form |
| POST | `/users` | `users.store` | Create user |
| GET | `/users/{user}` | `users.show` | User profile |
| GET | `/users/{user}/edit` | `users.edit` | Edit user |
| PUT/PATCH | `/users/{user}` | `users.update` | Update user |
| DELETE | `/users/{user}` | `user.destroy` | Delete user, using the host model's supported behavior |
| GET | `/users/deleted` | `users.deleted` | Soft-deleted user directory |
| GET | `/users/deleted/{id}/edit` | `users.deleted.edit` | Edit a soft-deleted user |
| PUT | `/users/deleted/{id}` | `users.deleted.update` | Update a soft-deleted user |
| POST | `/users/{id}/restore` | `users.restore` | Restore a soft-deleted user |
| DELETE | `/users/{id}/force` | `users.force-destroy` | Permanently delete a soft-deleted user |
| POST | `/users/bulk` | `users.bulk` | Apply an authorized bulk action |
| POST | `/users/{id}/impersonate` | `users.impersonate` | Start authorized impersonation |
| POST | `/users/impersonation/stop` | `users.impersonation.stop` | Restore the original signed-in account |
| POST | `/users/settings/impersonation` | `users.settings.impersonation` | Enable or disable impersonation |
| POST | `/search-users` | `search-users` | Search users |
| POST | `/users/email` | `users.email` | Send an authorized email |
| POST | `/users/email/preview` | `users.email.preview` | Preview email content |
| GET | `/users/settings` | `users.settings` | Optional global settings page |
| PUT | `/users/settings` | `users.settings.update` | Save global appearance and access settings |
| PUT | `/users/settings/emails` | `users.settings.emails` | Save global email settings and templates |
| PUT | `/users/settings/cleanup` | `users.settings.cleanup` | Save optional deleted-account cleanup settings |
| PUT | `/users/settings/accounts` | `users.settings.accounts` | Save global account-page access settings |
| POST | `/users/settings/packages` | `users.settings.packages` | Queue an optional package change |
| GET | `/users/settings/packages/{id}` | `users.settings.packages.status` | Read package-change status |
| GET | `/users/account` | `users.account` | Optional signed-in account page |
| PUT | `/users/account` | `users.account.update` | Update signed-in account details |
| DELETE | `/users/account` | `users.account.delete` | Request account deletion |
| GET | `/users/account/email/{token}` | `users.account.email.confirm` | Confirm one side of an email change |
| POST | `/users/account/email/{token}` | `users.account.email.accept` | Complete a confirmed email change |
| GET | `/users/account-link/{token}` | `users.account-link` | Review a deleted-account action |
| POST | `/users/account-link/{token}` | `users.account-link.confirm` | Confirm restore or permanent deletion |

The optional account page and settings endpoints return not found while their feature is disabled. Settings writes require the settings migration and configured settings gate. Per-action permissions can further restrict list, create, edit, delete, restore, email, and settings controls. A hidden control is not a substitute for route authorization; the package validates each action on the server.

Search keeps its existing JSON response contract. Activity metadata is included only when requested by the bundled views. Login IP addresses are never added to search responses.

See [configuration](configuration.md), [roles and permissions](roles.md), [impersonation security](impersonation.md), [settings and access rules](settings.md), [email authorization](emails.md), and [upgrade guidance](upgrading.md) before enabling optional routes.
