const { test, expect } = require('@playwright/test');

async function rejectInvalidPermissionsAndClearSelection(page, edit, user) {
    const {target, name, email, runtime, administrator, editor, inherited} = user;
    const selectedRoles = edit.locator('select[name="role[]"]');
    const selectedPermissions = edit.locator('select[name="permissions[]"]');
    const confirmation = page.getByRole('dialog');
    const invalid = await page.evaluate(async ({ target, name, email, runtime, administrator }) => {
        const body = new URLSearchParams({ _token: document.querySelector('meta[name="csrf-token"]').content, _method: 'PUT', name: name + 'Invalid', email, role: administrator, permissions_present: '1', 'permissions[]': '99999999' });
        const response = await fetch(`/users/${target}`, { method: 'POST', headers: { Accept: 'application/json', 'X-LaravelUsers-Runtime': runtime }, body });
        const result = { status: response.status, data: await response.json() };
        return result;
    }, { target, name, email, runtime, administrator });
    expect(invalid.status).toBe(422);
    expect(invalid.data.errors.permissions).toBeDefined();
    await page.reload();
    await expect(edit.locator('[name="name"]')).toHaveValue(name);
    await expect(selectedRoles).toHaveValues([administrator, editor]);
    await expect(selectedPermissions).toHaveValues([inherited]);
    await edit.getByRole('tab', { name: 'Roles', exact: true }).click();
    await selectedPermissions.selectOption([]);
    await edit.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(confirmation).toBeVisible();
    await Promise.all([
        page.waitForResponse(response => new URL(response.url()).pathname === `/users/${target}` && response.request().method() === 'POST' && response.status() < 400),
        confirmation.getByRole('button', { name: 'Save changes', exact: true }).click(),
    ]);
    await expect(confirmation).toHaveCount(0);
    await page.reload();
    await expect(selectedPermissions).toHaveValues([]);
}

async function createRoleUser(page, runtime, integration) {
    await page.getByRole('link', { name: 'Create New User', exact: true }).click();
    const name = `Roles${runtime}${integration.replace('-', '')}${Date.now()}`;
    const email = `${name.toLowerCase()}@example.com`;
    const form = page.locator('[data-lu-native-form="user"]').first();
    await expect(form.getByRole('button', { name: 'Save changes', exact: true }).locator('[data-lu-icon="save"]')).toBeVisible();
    await form.locator('[name="name"]').fill(name);
    await form.locator('[name="email"]').fill(email);
    await form.locator('[name="password"]').fill('NativePass123!');
    await form.locator('[name="password_confirmation"]').fill('NativePass123!');
    const roles = form.locator('select[name="role"]');
    const permissions = form.locator('select[name="permissions[]"]');
    const administrator = await roles.locator('option').filter({ hasText: 'Administrator' }).getAttribute('value');
    const editor = await roles.locator('option').filter({ hasText: 'Editor' }).getAttribute('value');
    const direct = await permissions.locator('option').filter({ hasText: 'Direct permission' }).getAttribute('value');
    const inherited = await permissions.locator('option').filter({ hasText: 'Inherited permission' }).getAttribute('value');
    await expect(form).not.toContainText('API role');
    await expect(form).not.toContainText('API permission');
    await roles.selectOption(administrator);
    await permissions.selectOption([direct]);
    await form.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page.locator('[data-lu-native-screen="users"]')).toBeVisible();
    await page.locator('#lu-native-search').fill(name);
    await page.locator('.lu-search').getByRole('button', { name: 'Search', exact: true }).click();
    const row = page.locator('[data-lu-native-table] tbody tr').filter({ hasText: name });
    await expect(row).toContainText('Administrator');
    await page.getByRole('link', { name, exact: true }).click();
    await expect(page.locator('[data-lu-native-screen="show-user"]')).toBeVisible();
    await expect(page.locator('.lu-profile-body')).toContainText('Administrator');
    await expect(page.locator('.lu-profile-body')).toContainText('Direct permission');
    await expect(page.locator('.lu-profile-body')).not.toContainText('Direct permissions: Inherited permission');
    return {name, email, administrator, editor, direct, inherited};
}

for (const integration of ['laravel-roles', 'spatie']) {
    for (const runtime of ['livewire', 'vue', 'react', 'svelte']) {
        test(`${runtime} with ${integration} saves and reads roles and direct permissions`, async ({ page, context }) => {
            test.setTimeout(90000);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await context.addCookies([{ name: 'lu-native-roles', value: integration, url: 'http://127.0.0.1:19855' }]);
            await page.goto(`/__browser/bootstrap5?runtime=${runtime}&soft-deletes=1&search-debounce=0`);
            await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-runtime', runtime);
            const {name, email, administrator, editor, direct, inherited} = await createRoleUser(page, runtime, integration);
            const target = Number(new URL(page.url()).pathname.split('/').pop());
            await page.getByRole('link', { name: 'Edit', exact: true }).click();
            await expect(page.locator('[data-lu-native-screen="edit-user"]')).toBeVisible();
            const edit = page.locator('[data-lu-native-form="user"]').first();
            const selectedRoles = edit.locator('select[name="role[]"]');
            const selectedPermissions = edit.locator('select[name="permissions[]"]');
            await expect(selectedRoles).toHaveValues([administrator]);
            await expect(selectedPermissions).toHaveValues([direct]);
            await edit.getByRole('tab', { name: 'Roles', exact: true }).click();
            await selectedRoles.selectOption([administrator, editor]);
            await selectedPermissions.selectOption([inherited]);
            await edit.getByRole('button', { name: 'Save changes', exact: true }).click();
            const confirmation = page.getByRole('dialog');
            await expect(confirmation).toBeVisible();
            await Promise.all([
                page.waitForResponse(response => new URL(response.url()).pathname === `/users/${target}` && response.request().method() === 'POST' && response.status() < 400),
                confirmation.getByRole('button', { name: 'Save changes', exact: true }).click(),
            ]);
            await expect(confirmation).toHaveCount(0);
            await expect(page.locator('[data-lu-native-screen="edit-user"]')).toBeVisible();
            await expect(selectedRoles).toHaveValues([administrator, editor]);
            await expect(selectedPermissions).toHaveValues([inherited]);
            await page.reload();
            await expect(selectedRoles).toHaveValues([administrator, editor]);
            await expect(selectedPermissions).toHaveValues([inherited]);
            await rejectInvalidPermissionsAndClearSelection(page, edit, {target, name, email, runtime, administrator, editor, inherited});
            await context.addCookies([{ name: 'lu-native-permissions-denied', value: '1', url: 'http://127.0.0.1:19855' }]);
            await page.reload();
            const forbidden = await page.evaluate(async ({ target, name, email, runtime, administrator, direct }) => {
                const body = new URLSearchParams({ _token: document.querySelector('meta[name="csrf-token"]').content, _method: 'PUT', name: name + 'Unauthorized', email, role: administrator, permissions_present: '1', 'permissions[]': direct });
                const response = await fetch(`/users/${target}`, { method: 'POST', headers: { Accept: 'application/json', 'X-LaravelUsers-Runtime': runtime }, body });
                return response.status;
            }, { target, name, email, runtime, administrator, direct });
            expect(forbidden).toBe(403);
            await page.reload();
            await expect(edit.locator('[name="name"]')).toHaveValue(name);
            await expect(selectedRoles).toHaveValues([administrator, editor]);
            await expect(selectedPermissions).toHaveValues([]);
            await edit.getByRole('tab', { name: 'Roles', exact: true }).click();
            await page.evaluate(() => window.scrollTo(0, 0));
            await page.screenshot({ path: `tests/browser/runtime/native-${runtime}-${integration}-roles.png` });
            expect(errors).toEqual([]);
        });
    }
}
