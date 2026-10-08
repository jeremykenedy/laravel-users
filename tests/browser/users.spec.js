const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

test.use({ timezoneId: 'America/Los_Angeles' });

async function setTheme(page, theme) {
    const toggle = page.locator('#lu-theme');
    if (!await toggle.isVisible()) {
        await page.getByRole('button', { name: 'Toggle navigation' }).click();
    }
    for (let clicks = 0; clicks < 3; clicks++) {
        if (await toggle.locator('svg:not([hidden])').getAttribute('data-theme-icon') === theme) break;
        await toggle.click();
    }
    await expect(toggle.locator('svg:not([hidden])')).toHaveAttribute('data-theme-icon', theme);
}

test('published theme select remains usable after a script update', async ({ page }) => {
    await page.goto('/__browser/bootstrap4');
    await page.route('**/users', async route => {
        const response = await route.fetch();
        const body = (await response.text()).replace(/<button\b[^>]*\bid="lu-theme"[^>]*>[\s\S]*?<\/button>/,
            '<select id="lu-theme" aria-label="Color theme"><option value="light">Light</option><option value="dark">Dark</option><option value="system">System</option></select>');
        await route.fulfill({ response, body });
    });
    await page.reload();
    await page.getByLabel('Color theme', { exact: true }).selectOption('dark');
    await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'dark');
    await page.reload();
    await expect(page.getByLabel('Color theme', { exact: true })).toHaveValue('dark');
    await page.getByLabel('Color theme', { exact: true }).selectOption('system');
    await page.emulateMedia({ colorScheme: 'light' });
    await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'light');
});

for (const contentType of ['application/json', 'text/html']) {
    test(`bootstrap4: search accepts ${contentType} responses`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto('/__browser/bootstrap4');
        await page.route('**/search-users', route => route.fulfill({
            contentType,
            body: JSON.stringify([{ id: 2, name: 'Search result', email: 'result@example.com', created_at: null, updated_at: null }])
        }));
        await page.locator('#user_search_box').fill('Search');
        await page.locator('#user_search_box').press('Enter');
        await expect(page.locator('#search_results')).toContainText('Search result');
        await expect(page.locator('#search_results a[href="users/2/edit"]')).toBeVisible();
        await page.route('**/search-users', route => route.fulfill({ contentType, body: '[]' }));
        await page.locator('#user_search_box').fill('Missing');
        await page.locator('#user_search_box').press('Enter');
        await expect(page.locator('#search_results')).toContainText('No Results');
        expect(errors).toEqual([]);
    });
}

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: disabled theme control ignores saved preferences`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        await setTheme(page, 'dark');
        await page.goto(`/__browser/${framework}?theme-toggle=0`);
        await expect(page.locator('#lu-theme')).toHaveCount(0);
        await page.emulateMedia({ colorScheme: 'light' });
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'light');
        await page.emulateMedia({ colorScheme: 'dark' });
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'dark');
        await page.goto(`/__browser/${framework}`);
        await expect(page.getByRole('button', { name: 'Change theme: Dark theme', exact: true })).toBeVisible();
    });

    test(`${framework}: login details and online status`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        await page.goto('/users/1');
        await expect(page.getByText('Last login', { exact: true })).toBeVisible();
        await expect(page.getByText('127.0.0.1', { exact: true })).toBeVisible();
        await expect(page.getByText('Online', { exact: true })).toBeVisible();
        await expect(page.getByText('Operating system', { exact: true })).toBeVisible();
        for (const theme of ['light', 'dark']) {
            await setTheme(page, theme);
            await page.setViewportSize({ width: 390, height: 844 });
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
            if (framework !== 'bootstrap4') {
                const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
                expect(result.violations).toEqual([]);
            }
        }
    });

    test(`${framework}: search, themes, and responsive layout`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/__browser/${framework}`);
        await expect(page.locator('body')).toContainText('Morgan Hayes');
        await setTheme(page, 'dark');
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'dark');
        await expect(page.getByRole('button', { name: 'Change theme: Dark theme', exact: true })).toBeVisible();
        await page.reload();
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'dark');
        await setTheme(page, 'system');
        await page.emulateMedia({ colorScheme: 'dark' });
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'dark');
        await expect(page.getByRole('button', { name: 'Change theme: System theme', exact: true })).toBeVisible();
        await page.emulateMedia({ colorScheme: 'light' });
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'light');
        await page.locator('#lu-theme').press('Space');
        await expect(page.getByRole('button', { name: 'Change theme: Light theme', exact: true })).toBeVisible();
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
        await expect(page.getByRole('dialog')).toBeVisible();
        await page.locator('#lu-confirm-submit').click();
        await expect(page.getByRole('status')).toContainText('Successfully updated');
        for (const theme of ['light', 'dark']) {
            await setTheme(page, theme);
            const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(result.violations).toEqual([]);
        }
        await page.getByRole('link', { name: 'Back to users' }).click();
        await page.getByLabel('Search Users', { exact: true }).fill(name);
        await page.getByRole('button', { name: 'Search', exact: true }).click();
        await page.locator('#lu-results').getByRole('link', { name: `${name}updated`, exact: true }).click();
        page.on('dialog', () => { throw new Error('Unexpected browser dialog'); });
        await page.getByRole('button', { name: 'Delete', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).last().click();
        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(`${name}updated`);

        await page.getByRole('button', { name: 'Delete', exact: true }).click();
        await page.locator('#lu-confirm-submit').click();
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

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: debounced search submits once and clear cancels it`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        const input = page.locator('#user_search_box');
        const results = page.locator(framework === 'bootstrap4' ? '#search_results' : '#lu-results');
        let requests = 0;
        await page.route('**/search-users', route => {
            requests++;
            return route.fulfill({ contentType: 'application/json', body: JSON.stringify([{ id: 2, name: 'Alex Rivers', email: 'alex@example.com' }]) });
        });
        await input.fill('Al');
        await page.waitForTimeout(1000);
        expect(requests).toBe(0);
        await input.fill('Alex');
        await page.waitForTimeout(1200);
        expect(requests).toBe(0);
        await expect(results).toContainText('Alex Rivers');
        expect(requests).toBe(1);
        await input.fill('Alex R');
        await input.press('Enter');
        await expect.poll(() => requests).toBe(2);
        await page.waitForTimeout(2100);
        expect(requests).toBe(2);
        await input.fill('Morgan');
        if (framework === 'bootstrap4') await page.locator('.clear-search').click();
        else await page.getByRole('button', { name: 'Clear', exact: true }).click();
        await page.waitForTimeout(2100);
        expect(requests).toBe(2);
        await expect(page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users')).toBeVisible();
    });

    test(`${framework}: column sort, filter, mail links, and disabled controls`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        const body = page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users');
        await page.getByRole('button', { name: 'Sort by Name', exact: true }).click();
        await expect(body.locator('tr').first()).toContainText('Alex Rivers');
        await page.getByRole('button', { name: 'Sort by Name', exact: true }).click();
        await expect(body.locator('tr').first()).toContainText('Morgan Hayes');
        await page.getByLabel('Filter Name on this page', { exact: true }).fill('Alex');
        await expect(body.locator('tr:visible')).toHaveCount(1);
        await expect(body.locator('tr:visible')).toContainText('Alex Rivers');
        await page.getByLabel('Filter Name on this page', { exact: true }).fill('');
        await page.getByLabel('Filter Status on this page', { exact: true }).fill('offline');
        await expect(body.locator('tr:visible')).toHaveCount(1);
        await expect(body.locator('tr:visible')).toContainText('Alex Rivers');
        await expect(body.locator('a[href="mailto:user1@example.com"]')).toBeVisible();
        await expect(body.locator('tr:visible')).not.toContainText('Offline');
        await page.goto(`/__browser/${framework}?table-controls=0`);
        await expect(page.getByRole('button', { name: 'Sort by Name', exact: true })).toHaveCount(0);
        await expect(page.getByLabel('Filter Name on this page', { exact: true })).toHaveCount(0);
    });
}

for (const framework of ['bootstrap5', 'tailwind']) {
    test(`${framework}: form input groups, button icons, and matching search heights`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        const input = await page.locator('#user_search_box').boundingBox();
        const button = await page.getByRole('button', { name: 'Search', exact: true }).boundingBox();
        expect(Math.abs(input.height - button.height)).toBeLessThan(1);
        await expect(page.getByRole('button', { name: 'Search', exact: true }).locator('svg')).toBeVisible();
        await page.getByRole('link', { name: 'Create New User', exact: true }).hover();
        expect(await page.getByRole('link', { name: 'Create New User', exact: true }).evaluate(link => getComputedStyle(link).textDecorationLine)).toBe('none');
        await page.getByRole('link', { name: 'Create New User', exact: true }).click();
        await expect(page.locator('.lu-input-icon')).toHaveCount(4);
        await page.getByLabel('Require password setup before sign-in', { exact: true }).check();
        await expect(page.getByLabel('Send a welcome email', { exact: true })).toBeChecked();
        await expect(page.locator('#password')).not.toHaveAttribute('required');
        await page.getByLabel('Send a welcome email', { exact: true }).uncheck();
        await expect(page.getByLabel('Require password setup before sign-in', { exact: true })).not.toBeChecked();
        await expect(page.locator('#password')).toHaveAttribute('required');
    });
}

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: action buttons stay on one row with mobile icons and link tooltips`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        const body = page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users');
        const name = body.getByRole('link', { name: 'Alex Rivers', exact: true });
        const email = body.getByRole('link', { name: 'user1@example.com', exact: true });
        await expect(name).toHaveAttribute(framework === 'bootstrap4' ? 'data-original-title' : 'title', 'View user');
        await expect(email).toHaveAttribute(framework === 'bootstrap4' ? 'data-original-title' : 'title', 'Send user an email');
        const row = body.locator('tr').filter({ hasText: 'Alex Rivers' });
        const actions = row.locator(framework === 'bootstrap4' ? '.btn' : '.lu-button');
        const desktop = await actions.evaluateAll(buttons => buttons.map(button => button.getBoundingClientRect().y));
        expect(Math.max(...desktop) - Math.min(...desktop)).toBeLessThan(2);
        await page.setViewportSize({ width: 390, height: 844 });
        const mobile = await actions.evaluateAll(buttons => buttons.map(button => button.getBoundingClientRect().y));
        expect(Math.max(...mobile) - Math.min(...mobile)).toBeLessThan(2);
        if (framework !== 'bootstrap4') {
            const show = row.getByRole('link', { name: 'Show', exact: true });
            await expect(show).toHaveAttribute('title', 'Show');
            await expect(show).toHaveAttribute('aria-label', 'Show');
            expect(await show.evaluate(button => getComputedStyle(button).fontSize)).toBe('0px');
            await expect(show.locator('svg')).toBeVisible();
        }
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: readable local dates, persistent columns, and optional mobile entries`, async ({ page }) => {
        await page.emulateMedia({ colorScheme: 'light' });
        await page.goto(`/__browser/${framework}`);
        const body = page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users');
        const time = body.locator('time').first();
        const utc = await time.getAttribute('datetime');
        const expected = await page.evaluate(value => new Intl.DateTimeFormat(document.documentElement.lang, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)), utc);
        await expect(time).toHaveText(expected);
        expect(await time.evaluate(element => parseFloat(getComputedStyle(element).fontSize))).toBeLessThan(14);
        await expect(body).not.toContainText('Not recorded');
        const headers = await page.locator('[data-lu-table] thead tr').first().locator('th').allTextContents();
        expect(headers.findIndex(value => value.includes('Status'))).toBeLessThan(headers.findIndex(value => value.includes('Created')));
        await page.locator('.lu-columns summary').click();
        await page.locator('.lu-column-options').getByLabel('Email', { exact: true }).uncheck();
        await expect(body.locator('a[href="mailto:user1@example.com"]')).toBeHidden();
        await page.reload();
        await expect(body.locator('a[href="mailto:user1@example.com"]')).toBeHidden();
        await page.locator('.lu-columns summary').click();
        await page.locator('.lu-column-options').getByLabel('Email', { exact: true }).check();
        await page.setViewportSize({ width: 390, height: 844 });
        await page.locator('[data-lu-select-all-mobile]').check();
        await expect(page.locator('[data-lu-selected-count]')).toHaveText('1 selected');
        await page.locator('[data-lu-select-all-mobile]').uncheck();
        await page.locator('.lu-mobile-filters summary').click();
        await page.getByLabel('Filter Name on mobile', { exact: true }).fill('Alex');
        await expect(body.locator('tr:visible')).toHaveCount(1);
        await page.getByLabel('Filter Name on mobile', { exact: true }).fill('');
        await page.locator('.lu-mobile-filters summary').click();
        expect(await body.locator('tr').first().evaluate(row => getComputedStyle(row).display)).toBe('block');
        await expect(body.getByRole('link', { name: 'user1@example.com', exact: true })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        await page.goto(`/__browser/${framework}?responsive-table=0`);
        expect(await body.locator('tr').first().evaluate(row => getComputedStyle(row).display)).toBe('table-row');
    });

    test(`${framework}: avatar image failures fall back to the user icon`, async ({ page }) => {
        await page.route('https://www.gravatar.com/avatar/**', route => route.fulfill({ status: 404, body: '' }));
        await page.goto(`/__browser/${framework}?avatar=gravatar`);
        const body = page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users');
        const avatar = body.locator('.lu-avatar').first();
        await expect(avatar.locator('img')).toBeHidden();
        await expect(avatar.locator('svg')).toBeVisible();
        await page.goto(`/__browser/${framework}?avatar=initials`);
        await expect(body.locator('.lu-avatar').first()).toHaveText('MH');
        await page.goto(`/__browser/${framework}?avatar=0`);
        await expect(body.locator('.lu-avatar')).toHaveCount(0);
    });

    test(`${framework}: bulk delete, separate deleted table, restore, and permanent deletion`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?soft-deletes=1`);
        const prefix = `bulk${framework}${Date.now()}`;
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        for (const suffix of ['one', 'two']) {
            const response = await page.request.post('/users', { form: { _token: token, name: prefix + suffix, email: `${prefix}${suffix}@example.com`, password: 'password123', password_confirmation: 'password123' } });
            expect(response.ok()).toBe(true);
        }
        async function search() {
            await page.goto('/users');
            await page.locator('#user_search_box').fill(prefix);
            await page.locator('#user_search_box').press('Enter');
            await expect(page.locator(framework === 'bootstrap4' ? '#search_results' : '#lu-results')).toContainText(prefix + 'two');
        }
        async function apply(action) {
            await page.locator('#lu-bulk-action').selectOption(action);
            await page.locator('[data-lu-select-all]').check();
            await expect(page.locator('[data-lu-selected-count]')).toHaveText('2 selected');
            await page.locator('#lu-bulk-submit').click();
            if (framework === 'bootstrap4') await page.locator('#confirmDelete #confirm').click();
            else await page.locator('#lu-confirm-submit').click();
        }
        await search();
        await apply('delete');
        await expect(page).toHaveURL('/users');
        await page.goto('/users/deleted');
        await expect(page.locator('[data-lu-table]')).toContainText(prefix + 'one');
        await expect(page.locator('[data-lu-table]')).toContainText(prefix + 'two');
        await apply('restore');
        await expect(page).toHaveURL('/users/deleted');
        await expect(page.locator('[data-lu-table]')).not.toContainText(prefix + 'one');
        await search();
        await apply('delete');
        await page.goto('/users/deleted');
        await apply('force_delete');
        await expect(page.locator('[data-lu-table]')).not.toContainText(prefix + 'one');
        const response = await page.request.post('/search-users', { form: { _token: token, user_search_box: prefix } });
        expect(await response.json()).toEqual([]);
    });
}
