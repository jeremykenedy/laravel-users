const { test, expect } = require('@playwright/test');
const { startPackageWorker } = require('./package-worker.cjs');

const frameworks = ['bootstrap4', 'bootstrap5', 'tailwind', 'materialize', 'material3', 'bulma', 'foundation'];
const runtimes = ['livewire', 'vue', 'react', 'svelte'];

let packageWorker;
test.beforeAll(async () => { packageWorker = await startPackageWorker(19855); });
test.afterAll(() => { packageWorker?.kill('SIGTERM'); });

async function previewAndSendEmail(page) {
    await page.getByRole('button', { name: 'Send user an email', exact: true }).click();
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await dialog.locator('[name="subject"]').fill('Native preview');
    await dialog.locator('[name="message"]').fill('Preview from the native interface.');
    await dialog.getByRole('button', { name: 'Preview email', exact: true }).click();
    await expect(dialog.locator('iframe')).toBeVisible();
    await expect(dialog.locator('iframe')).toHaveAttribute('sandbox', '');
    await dialog.getByRole('button', { name: 'Back to editing', exact: true }).click();
    await expect(dialog.locator('[name="subject"]')).toHaveValue('Native preview');
    await dialog.getByRole('button', { name: 'Send email', exact: true }).click();
    await expect(page.getByRole('dialog')).toHaveCount(0);
}

async function exerciseBulkLifecycle(page, prefix) {
    const table = page.locator('[data-lu-native-table]');
    await table.getByRole('button', { name: 'Select all visible users', exact: true }).click();
    await table.getByRole('button', { name: 'Delete', exact: true }).first().click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByRole('dialog').getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await page.goto('/users/deleted');
    await expect(page.locator('[data-lu-native-screen="deleted-users"]')).toBeVisible();
    await expect(table.locator('tbody')).toContainText(prefix + 'Edited');
    await expect(table.locator('tbody')).toContainText(prefix + 'Second');
    await table.getByRole('button', { name: 'Select all visible users', exact: true }).click();
    await table.getByRole('button', { name: 'Restore', exact: true }).first().click();
    await page.getByRole('dialog').getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await page.goto(`/users?user_search_box=${prefix}`);
    await expect(table.locator('tbody tr')).toHaveCount(2);
    await table.getByRole('button', { name: 'Select all visible users', exact: true }).click();
    await table.getByRole('button', { name: 'Delete', exact: true }).first().click();
    await page.getByRole('dialog').getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await page.goto('/users/deleted');
    await table.getByRole('button', { name: 'Select all visible users', exact: true }).click();
    await table.getByRole('button', { name: 'Permanently delete', exact: true }).first().click();
    await page.getByRole('dialog').getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await expect(table.locator('tbody')).not.toContainText(prefix);
}

async function exerciseAccountConfirmation(page, prefix) {
    await page.goto('/users/account');
    await expect(page.locator('[data-lu-native-screen="account"]')).toBeVisible();
    const account = page.locator('form[data-lu-native-form="account-profile"]');
    await account.locator('[name="full_name"]').fill(prefix + ' Account');
    await account.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page.locator('.lu-native-profile')).toContainText(prefix + ' Account');
    await page.getByRole('button', { name: 'Delete my account', exact: true }).click();
    const dialog = page.getByRole('dialog');
    await expect(dialog.getByRole('button', { name: 'Save changes', exact: true })).toBeDisabled();
    await dialog.locator('[name="confirmation"]').fill('Delete');
    await expect(dialog.getByRole('button', { name: 'Save changes', exact: true })).toBeDisabled();
    await dialog.locator('[name="confirmation"]').fill('delete');
    await expect(dialog.getByRole('button', { name: 'Save changes', exact: true })).toBeEnabled();
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click();
    await expect(dialog).toHaveCount(0);
    await page.getByRole('button', { name: 'Delete my account', exact: true }).click();
    await expect(dialog.locator('[name="confirmation"]')).toHaveValue('');
    await expect(dialog.getByRole('button', { name: 'Save changes', exact: true })).toBeDisabled();
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click();
    await account.locator('[name="full_name"]').fill('Morgan Hayes');
    await account.getByRole('button', { name: 'Save changes', exact: true }).click();
}

async function verifyRequirementsAndTemplates(page, runtime) {
    const requirementsResponse = page.waitForResponse(response => response.url().endsWith('/users/settings/packages/verify') && response.request().method() === 'POST');
    await page.getByRole('button', { name: /^(?:Re-)?Verify package requirements$/ }).click();
    await requirementsResponse;
    const requirements = page.locator('[data-lu-native-package-requirements]');
    await expect(requirements).toContainText(/required|missing|unavailable|not writable|verify|verified/i);
    await expect(requirements).not.toContainText('Waiting for the package worker', { timeout: 15000 });
    const templates = page.locator('form[data-lu-native-form="email-templates"]');
    await expect(templates.locator('details summary')).toHaveCount(5);
    await expect(templates.locator('details summary').first()).toHaveText(/template$/);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({ path: `tests/browser/runtime/native-${runtime}-settings-mobile.png`, fullPage: true });
}

async function assertBreadcrumbLayout(page) {
    await expect(page.locator('.lu-breadcrumbs')).toBeVisible();
    await expect(page.locator('.lu-breadcrumbs li').last().locator('a')).toHaveCount(0);
    const toolbarBox = await page.locator('.lu-toolbar').boundingBox();
    const pathBox = await page.locator('.lu-breadcrumbs').boundingBox();
    expect(pathBox.y).toBeGreaterThanOrEqual(toolbarBox.y + toolbarBox.height);
}

for (const runtime of runtimes) {
    for (const framework of frameworks) {
        test(`${runtime} with ${framework} renders the native directory`, async ({ page }) => {
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.goto(`/__browser/${framework}?runtime=${runtime}&accounts=1&settings=1&soft-deletes=1&appearance=1&search-debounce=0`);
            await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-runtime', runtime);
            await expect(page.locator('h1')).toHaveText('Showing All Users');
            await expect(page.locator('[data-lu-native-table]')).toBeVisible();
            await expect(page.locator('[data-lu-native-table] tbody tr').first()).toBeVisible();
            await expect(page.locator('.lu-breadcrumbs')).toHaveCount(0);
            await page.getByRole('button', { name: 'Card view', exact: true }).click();
            await expect(page.locator('.lu-native-user-card').first()).toBeVisible();
            await page.getByRole('button', { name: 'Table view', exact: true }).click();
            await expect(page.locator('[data-lu-native-table] table')).toBeVisible();
            await page.locator('#lu-native-table-filter').fill('Morgan');
            await expect(page.locator('[data-lu-native-table] tbody tr')).toHaveCount(1);
            await page.locator('#lu-native-table-filter').fill('');
            await expect(page.locator('[data-lu-native-table] tbody tr')).toHaveCount(2);

            await page.getByRole('link', { name: 'Create New User', exact: true }).click();
            const name = `Native${runtime}${framework}${Date.now()}`;
            const form = page.locator('form[data-lu-native-form="user"]').first();
            await form.locator('[name="name"]').fill(name);
            await form.locator('[name="email"]').fill('user0@example.com');
            await form.locator('[name="password"]').fill('NativePass123!');
            await form.locator('[name="password_confirmation"]').fill('NativePass123!');
            await form.getByRole('button', { name: 'Save changes', exact: true }).click();
            await expect(page.locator('.lu-field-error').first()).toContainText(/email.*taken/i);
            await form.locator('[name="email"]').fill(`${name.toLowerCase()}@example.com`);
            if (runtime === 'livewire') {
                await form.locator('[name="password"]').fill('NativePass123!');
                await form.locator('[name="password_confirmation"]').fill('NativePass123!');
            }
            await form.getByRole('button', { name: 'Save changes', exact: true }).click();
            await expect(page.locator('[data-lu-native-screen="users"]')).toBeVisible();
            await page.locator('#lu-native-search').fill(name);
            await page.locator('.lu-search').getByRole('button', { name: 'Search', exact: true }).click();
            await expect(page.getByRole('link', { name, exact: true })).toBeVisible();
            await page.getByRole('link', { name, exact: true }).click();
            await expect(page.locator('[data-lu-native-screen="show-user"]')).toBeVisible();
            await expect(page.locator('.lu-native-profile')).toContainText(name);
            await previewAndSendEmail(page);
            await page.getByRole('link', { name: 'User settings', exact: true }).click();
            await expect(page.locator('[data-lu-native-screen="settings"]')).toBeVisible();
            await expect(page.locator('[name="show_breadcrumbs"]:not([type="hidden"])')).not.toBeChecked();
            expect(errors).toEqual([]);
        });
    }
}

for (const runtime of runtimes) {
    test(`${runtime} with bootstrap5 verifies edits bulk actions and account confirmations`, async ({ page }) => {
        test.setTimeout(90000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/__browser/bootstrap5?runtime=${runtime}&accounts=1&settings=1&soft-deletes=1&appearance=1&avatar-preferences=1&search-debounce=0`);
        const prefix = `Lifecycle${runtime}${Date.now()}`;
        for (const suffix of ['First', 'Second']) {
            await page.getByRole('link', { name: 'Create New User', exact: true }).click();
            const form = page.locator('form[data-lu-native-form="user"]').first();
            await form.locator('[name="name"]').fill(prefix + suffix);
            await form.locator('[name="email"]').fill(`${prefix.toLowerCase()}${suffix.toLowerCase()}@example.com`);
            await form.locator('[name="password"]').fill('NativePass123!');
            await form.locator('[name="password_confirmation"]').fill('NativePass123!');
            await form.getByRole('button', { name: 'Save changes', exact: true }).click();
            await expect(page.locator('[data-lu-native-screen="users"]')).toBeVisible();
        }
        await page.locator('#lu-native-search').fill(prefix);
        await page.locator('.lu-search').getByRole('button', { name: 'Search', exact: true }).click();
        await page.getByRole('link', { name: prefix + 'First', exact: true }).click();
        await expect(page.locator('[data-lu-native-screen="show-user"]')).toBeVisible();
        await page.getByRole('link', { name: 'Edit', exact: true }).click();
        await expect(page.locator('[data-lu-native-screen="edit-user"]')).toBeVisible();
        const editor = page.locator('form[data-lu-native-form="user"]').first();
        await editor.locator('[name="name"]').fill(prefix + 'Edited');
        await editor.getByRole('button', { name: 'Save changes', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await page.getByRole('dialog').getByRole('button', { name: 'Save changes', exact: true }).click();
        await expect(page.getByRole('dialog')).toHaveCount(0);
        await page.goto(`/users?user_search_box=${prefix}`);
        await expect(page.getByRole('link', { name: prefix + 'Edited', exact: true })).toBeVisible();
        await exerciseBulkLifecycle(page, prefix);
        await exerciseAccountConfirmation(page, prefix);
        expect(errors).toEqual([]);
    });
}

for (const runtime of runtimes) {
    test(`${runtime} with bootstrap5 checks saved settings and actual Toast`, async ({ page }) => {
        test.setTimeout(60000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/__browser/bootstrap5?runtime=${runtime}&accounts=1&settings=1&soft-deletes=1&appearance=1&avatar-preferences=1&packages=1&search-debounce=0`);
        await page.getByRole('link', { name: 'User settings', exact: true }).click();
        const form = page.locator('form[data-lu-native-form="settings"]');
        const breadcrumbs = form.locator('[name="show_breadcrumbs"]:not([type="hidden"])');
        const lightHighlight = form.locator('[name="profile_gradient_highlight_color"]:not([type="hidden"])');
        const darkHighlight = form.locator('[name="profile_dark_gradient_highlight_color"]:not([type="hidden"])');
        await expect(breadcrumbs).not.toBeChecked();
        await expect(darkHighlight).toBeDisabled();
        await expect(darkHighlight).toHaveValue('#ffffff');
        await lightHighlight.fill('#b14c8a');
        await expect(darkHighlight).toHaveValue('#b14c8a');
        await breadcrumbs.check();
        await expect(form.locator('.lu-native-profile')).toHaveCount(4);
        const previewResponse = page.waitForResponse(response => response.url().endsWith('/users/settings/avatar-preview') && response.request().method() === 'POST');
        await form.locator('[name="avatar_source"]').selectOption('avatar');
        const avatars = await (await previewResponse).json();
        expect(Object.keys(avatars.avatars)).toEqual(['profile', 'edit', 'profile_dark', 'edit_dark']);
        await expect(form.locator('.lu-native-profile img')).toHaveCount(4);
        await expect(form.locator('.lu-native-profile img').first()).toHaveAttribute('src', /avatar-preview\/profile/);
        await form.getByRole('tab', { name: 'Notifications', exact: true }).click();
        await form.locator('[name="notifications_driver"]').selectOption('toast');
        await expect(form.locator('[name="toast[position]"]')).toBeVisible();
        await form.locator('[name="toast[auto_dismiss]"]:not([type="hidden"])').uncheck();
        await form.getByRole('button', { name: 'Save changes', exact: true }).click();
        await expect(page.locator('[data-lu-toast]')).toBeVisible();
        await expect(page.locator('[data-lu-toast]')).toContainText(/saved/i);
        await expect(page.locator('[data-lu-toast]')).toHaveAttribute('data-auto-dismiss', 'false');
        await assertBreadcrumbLayout(page);
        await page.locator('[data-lu-dismiss-toast]').click();
        await expect(page.locator('[data-lu-toast]')).toHaveCount(0);
        await page.reload();
        await expect(form.locator('[name="show_breadcrumbs"]:not([type="hidden"])')).toBeChecked();
        await expect(darkHighlight).toBeDisabled();
        await expect(darkHighlight).toHaveValue('#b14c8a');
        await verifyRequirementsAndTemplates(page, runtime);
        await form.getByRole('tab', { name: 'Appearance', exact: true }).click();
        await breadcrumbs.uncheck();
        await lightHighlight.fill('#ffffff');
        await form.locator('[name="avatar_source"]').selectOption('initials');
        await form.getByRole('tab', { name: 'Notifications', exact: true }).click();
        await form.locator('[name="notifications_driver"]').selectOption('alert');
        await form.getByRole('button', { name: 'Save changes', exact: true }).click();
        await expect(page.locator('.lu-breadcrumbs')).toHaveCount(0);
        expect(errors).toEqual([]);
    });
}
