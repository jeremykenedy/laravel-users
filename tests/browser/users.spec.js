const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: search, themes, and responsive layout`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/__browser/${framework}`);
        await expect(page.locator('body')).toContainText('Morgan Hayes');
        await page.getByLabel('Color theme', { exact: true }).selectOption('dark');
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'dark');
        await page.reload();
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'dark');
        await page.getByLabel('Color theme', { exact: true }).selectOption('system');
        await page.emulateMedia({ colorScheme: 'light' });
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'light');
        const search = page.locator('#user_search_box');
        await search.fill('Alex');
        await search.press('Enter');
        const results = page.locator(framework === 'bootstrap4' ? '#search_results' : '#lu-results');
        await expect(results).toContainText('Alex Rivers');
        await search.fill('<img');
        await search.press('Enter');
        await expect(results).toContainText('<img src=x onerror=alert(1)>');
        await expect(results.locator('img')).toHaveCount(0);
        await page.setViewportSize({ width: 390, height: 844 });
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        expect(errors).toEqual([]);
    });
}

for (const framework of ['bootstrap5', 'tailwind']) {
    test(`${framework}: CRUD, validation, cancellation, and accessibility`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        const name = `browser${framework}${Date.now()}`;
        await page.getByRole('link', { name: 'Create New User', exact: true }).click();
        await page.getByLabel('Username', { exact: true }).fill(name);
        await page.getByLabel('User Email', { exact: true }).fill(`${name}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password123');
        await page.getByLabel('Confirm Password', { exact: true }).fill('mismatch');
        await page.getByRole('button', { name: 'Create New User' }).click();
        await expect(page.getByRole('alert')).toBeVisible();
        await expect(page.getByLabel('Username', { exact: true })).toHaveValue(name);
        await page.getByLabel('Password', { exact: true }).fill('password123');
        await page.getByLabel('Confirm Password', { exact: true }).fill('password123');
        await page.getByRole('button', { name: 'Create New User' }).click();
        await expect(page).toHaveURL('/users');
        await page.getByLabel('Search Users', { exact: true }).fill(name);
        await page.getByRole('button', { name: 'Search', exact: true }).click();
        await page.locator('#lu-results').getByRole('link', { name: 'Edit', exact: true }).click();
        await page.getByLabel('Username', { exact: true }).fill(`${name}updated`);
        await page.getByRole('button', { name: 'Save changes' }).click();
        await expect(page.getByRole('status')).toContainText('Successfully updated');
        for (const theme of ['light', 'dark']) {
            await page.getByLabel('Color theme', { exact: true }).selectOption(theme);
            const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(result.violations).toEqual([]);
        }
        await page.getByRole('link', { name: 'Back to users' }).click();
        await page.getByLabel('Search Users', { exact: true }).fill(name);
        await page.getByRole('button', { name: 'Search', exact: true }).click();
        await page.locator('#lu-results').getByRole('link', { name: `${name}updated`, exact: true }).click();
        page.once('dialog', dialog => dialog.dismiss());
        await page.getByRole('button', { name: 'Delete', exact: true }).click();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(`${name}updated`);
        page.once('dialog', dialog => dialog.accept());
        await page.getByRole('button', { name: 'Delete', exact: true }).click();
        await expect(page).toHaveURL('/users');
        await expect(page.getByRole('status')).toContainText('Successfully deleted');
    });
}

test('bootstrap4: create, edit, and delete through existing modals', async ({ page }) => {
    await page.goto('/__browser/bootstrap4');
    const name = `legacy${Date.now()}`;
    await page.goto('/users/create');
    await page.locator('#name').fill(name);
    await page.locator('#email').fill(`${name}@example.com`);
    await page.locator('#password').fill('password123');
    await page.locator('#password_confirmation').fill('password123');
    await page.locator('form button[type="submit"]').click();
    await expect(page).toHaveURL('/users');
    await page.locator('#user_search_box').fill(name);
    await page.locator('#user_search_box').press('Enter');
    await page.locator('#search_results a[href$="/edit"]').click();
    await page.locator('#name').fill(`${name}updated`);
    await page.locator('[data-target="#confirmSave"]').click();
    await page.locator('#confirmSave #confirm').click();
    await expect(page.locator('.alert-success')).toContainText('Successfully updated');
    await expect(page.locator('#name')).toHaveValue(`${name}updated`);
    await page.goto('/users');
    await page.locator('#user_search_box').fill(name);
    await page.locator('#user_search_box').press('Enter');
    await page.locator('#search_results [data-target="#confirmDelete"]').click();
    await page.locator('#confirmDelete #confirm').click();
    await expect(page.locator('.alert-success')).toContainText('Successfully deleted');
    await expect(page).toHaveURL('/users');
    await page.locator('#user_search_box').fill(name);
    await page.locator('#user_search_box').press('Enter');
    await expect(page.locator('#search_results')).toContainText('No Results');
});

test('modern search recovers from server failures and CSRF is enforced', async ({ page }) => {
    await page.goto('/__browser/tailwind');
    const denied = await page.request.post('/users', { form: { name: 'invalid' } });
    expect(denied.status()).toBe(419);
    await page.route('**/search-users', route => route.fulfill({ status: 500, body: '{}' }));
    await page.getByLabel('Search Users', { exact: true }).fill('Morgan');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page.locator('#lu-search-status')).toContainText('Search could not be completed');
    await expect(page.locator('#lu-users')).toBeVisible();
    await page.getByRole('button', { name: 'Clear', exact: true }).click();
    await expect(page.locator('#lu-search-status')).toBeHidden();
});
