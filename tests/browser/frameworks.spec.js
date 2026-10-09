const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const frameworks = ['materialize', 'material3', 'bulma', 'foundation'];

async function enableBreadcrumbs(page) {
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const response = await page.request.post('/users/settings', {form: {
        _token: token, _method: 'PUT', avatar_source: 'initials', profile_color: '#264e36', edit_color: '#705000', show_breadcrumbs: '1'
    }});
    expect(response.ok()).toBeTruthy();
    await page.reload();
    await expect(page.locator('.lu-breadcrumbs')).toBeVisible();
    await expect(page.locator('.lu-breadcrumbs')).toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
    await expect(page.locator('.lu-breadcrumbs')).toHaveCSS('box-shadow', 'none');
}

async function setTheme(page, theme) {
    const toggle = page.locator('#lu-theme');
    for (let attempt = 0; attempt < 3; attempt++) {
        if (await toggle.locator('svg:not([hidden])').getAttribute('data-theme-icon') === theme) break;
        await toggle.click();
    }
    await expect(toggle.locator('svg:not([hidden])')).toHaveAttribute('data-theme-icon', theme);
}

for (const framework of frameworks) {
    test(framework + ': local presentation keeps responsive native forms and table controls usable', async ({ page, baseURL }) => {
        const remoteAssets = [];
        const origin = new URL(baseURL).origin + '/';
        page.on('request', request => {
            if (['stylesheet', 'script', 'font'].includes(request.resourceType()) && !request.url().startsWith(origin)) {
                remoteAssets.push(request.url());
            }
        });
        await page.goto('/__browser/' + framework + '?published-assets=1&settings=1&appearance=1&accounts=1&soft-deletes=1');
        await enableBreadcrumbs(page);
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-css', framework);
        await expect(page.locator('link[href$="/' + framework + '.css"]')).toHaveCount(1);
        await expect(page.locator('script[src$="/material3.js"]')).toHaveCount(framework === 'material3' ? 1 : 0);
        await page.locator('[data-lu-select-visible]').click();
        await expect(page.locator('#lu-bulk-action')).toBeVisible();
        await page.locator('#lu-bulk-action').selectOption('message');
        await expect(page.locator('#lu-bulk-submit')).toBeEnabled();
        await page.locator('[data-lu-deselect-visible]').click();
        await page.getByRole('button', { name: 'Card view', exact: true }).click();
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-table-view', 'cards');
        for (const theme of ['light', 'dark']) {
            await setTheme(page, theme);
            const results = await new AxeBuilder({ page }).include('#laravelusers').withTags(['wcag2a', 'wcag2aa']).analyze();
            expect(results.violations.map(violation => ({ id: violation.id, nodes: violation.nodes.map(node => ({ html: node.html, failure: node.failureSummary })) }))).toEqual([]);
            if (framework === 'foundation') {
                expect(await page.locator('[data-lu-table] tbody').first().evaluate(body => getComputedStyle(body).backgroundColor)).toBe(await page.locator('.lu-panel').first().evaluate(panel => getComputedStyle(panel).backgroundColor));
            }
        }
        await page.getByRole('button', { name: 'Table view', exact: true }).click();
        await page.goto('/users/create');

        for (const width of [390, 768, 1440]) {
            await page.setViewportSize({ width, height: 900 });
            await expect(page.locator('#name')).toBeVisible();
            await expect(page.locator('#email')).toBeVisible();
            expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
            expect(await page.locator('#name').evaluate(input => input.getBoundingClientRect().width)).toBeGreaterThan(150);
        }
        if (framework === 'foundation') {
            await expect(page.locator('.lu-field.grid-x .cell.medium-9').first()).toBeVisible();
            expect(await page.locator('.lu-field.grid-x').first().evaluate(field => getComputedStyle(field).display)).toBe('flex');
        }
        for (const theme of ['light', 'dark']) {
            await setTheme(page, theme);
            const results = await new AxeBuilder({ page }).include('#laravelusers').withTags(['wcag2a', 'wcag2aa']).analyze();
            expect(results.violations.map(violation => ({ id: violation.id, nodes: violation.nodes.map(node => ({ html: node.html, failure: node.failureSummary })) }))).toEqual([]);
        }
        expect(remoteAssets).toEqual([]);
    });

    test(framework + ': settings tabs, confirmation dialogs and email previews retain native behavior', async ({ page }) => {
        await page.goto('/__browser/' + framework + '?settings=1&appearance=1&accounts=1&soft-deletes=1&published-assets=1');
        await enableBreadcrumbs(page);
        await page.goto('/users/settings');
        await page.getByRole('tab', { name: 'Emails', exact: true }).click();
        await expect(page.locator('#lu-settings-emails')).toBeVisible();
        await page.getByRole('tab', { name: 'Accounts', exact: true }).click();
        await expect(page.locator('#lu-settings-accounts')).toBeVisible();
        await page.goto('/users/2/edit');
        await page.getByRole('tab', { name: 'Password', exact: true }).click();
        await expect(page.locator('#password')).toBeVisible();
        await page.getByRole('tab', { name: 'Profile', exact: true }).click();
        await expect(page.locator('#name')).toBeVisible();
        await page.locator('.lu-form-actions button[type="submit"]').click();
        await expect(page.locator('#lu-confirmation')).toBeVisible();
        await page.locator('#lu-confirmation .lu-modal-footer').getByRole('button', { name: 'Cancel', exact: true }).click();
        await expect(page.locator('#lu-confirmation')).toBeHidden();
        await page.goto('/users/2');
        await page.getByRole('button', { name: 'Send user an email', exact: true }).click();
        await page.getByLabel('Subject', { exact: true }).fill('Framework preview');
        await page.getByLabel('Message', { exact: true }).fill('Please review your account.');
        await page.getByRole('button', { name: 'Preview email', exact: true }).click();
        await expect(page.frameLocator('[data-lu-email-frame]').getByText('Please review your account.', { exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Back to editing', exact: true }).click();
        await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('Framework preview');
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeHidden();
    });

    test(framework + ': account, deleted-user and public confirmation pages fit both appearances', async ({ page }) => {
        await page.goto('/__browser/' + framework + '?settings=1&appearance=1&accounts=1&soft-deletes=1&published-assets=1');
        await enableBreadcrumbs(page);
        for (const route of ['/users/account', '/users/deleted', '/users/account/email/invalid']) {
            await page.goto(route);
            await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-css', framework);
            for (const width of [390, 768, 1440]) {
                await page.setViewportSize({ width, height: 900 });
                expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
            }
            for (const theme of ['light', 'dark']) {
                await setTheme(page, theme);
                const results = await new AxeBuilder({ page }).include('#laravelusers').withTags(['wcag2a', 'wcag2aa']).analyze();
                expect(results.violations.map(violation => ({ id: violation.id, nodes: violation.nodes.map(node => node.html) }))).toEqual([]);
            }
        }
        await page.emulateMedia({ colorScheme: 'dark' });
        await page.goto('/users/account-link/invalid');
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-theme', 'dark');
        const results = await new AxeBuilder({ page }).include('#laravelusers').withTags(['wcag2a', 'wcag2aa']).analyze();
        expect(results.violations.map(violation => ({ id: violation.id, nodes: violation.nodes.map(node => node.html) }))).toEqual([]);
    });
}

test('material3: local state layers follow keyboard focus, disabled controls and reduced motion', async ({ page }) => {
    await page.goto('/__browser/material3?published-assets=1');
    const deselect = page.locator('[data-lu-deselect-visible]');
    await expect(deselect).toBeDisabled();
    await expect(deselect.locator('md-ripple')).toHaveAttribute('disabled', '');
    await page.locator('[data-lu-select-visible]').click();
    await expect(deselect).toBeEnabled();
    await expect(deselect.locator('md-ripple')).not.toHaveAttribute('disabled', '');
    await page.goto('/users/create');
    await page.locator('#name').focus();
    await page.keyboard.press('Tab');
    await expect(page.locator('#email')).toBeFocused();
    await expect(page.locator('#email').locator('..').locator('md-focus-ring')).toHaveAttribute('visible', '');
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await expect(page.locator('md-ripple').first()).toHaveAttribute('disabled', '');
    await expect(page.locator('md-ripple').first()).toHaveCSS('display', 'none');
});

test('material3: keyboard focus layers survive navigation and native rerenders', async ({ page }) => {
    await page.goto('/__browser/material3?published-assets=1');
    await page.goto('/users/create');
    await expect(page.locator('#email').locator('..').locator('md-focus-ring')).toHaveCount(1);
    await page.evaluate(() => {
        const root = document.getElementById('laravelusers');
        root.replaceWith(root.cloneNode(true));
        document.dispatchEvent(new Event('livewire:navigated'));
    });
    await page.locator('#name').focus();
    await page.keyboard.press('Tab');
    await expect(page.locator('#email')).toBeFocused();
    const focus = page.locator('#email').locator('..').locator('md-focus-ring');
    await expect(focus).toHaveCount(1);
    await expect(focus).toHaveAttribute('visible', '');
    await page.evaluate(() => document.querySelectorAll('#laravelusers md-ripple, #laravelusers md-focus-ring').forEach(component => component.remove()));
    await expect(focus).toHaveCount(1);
    await page.locator('#name').focus();
    await page.keyboard.press('Tab');
    await expect(page.locator('#email')).toBeFocused();
    await expect(focus).toHaveAttribute('visible', '');
});
