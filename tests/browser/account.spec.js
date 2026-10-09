const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

async function openAccount(page, framework) {
    await page.goto(`/__browser/${framework}?settings=1&accounts=1&appearance=1&avatar-preferences=1&published-assets=1`);
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const seeded = await page.request.post('/users/settings', { form: {
        _token: token, _method: 'PUT', avatar_source: 'initials', profile_color: '#2458b7', edit_color: '#705000',
        profile_gradient: '1', profile_gradient_strength: '50', profile_gradient_highlight_color: '#ffffff',
        profile_dark_gradient_highlight_color: '', show_breadcrumbs: '1', notifications_driver: 'alert'
    }, maxRedirects: 0 });
    expect(seeded.status()).toBe(302);
    await page.goto('/users/account');
    await expect(page.locator('.lu-account-card')).toBeVisible();
}

async function section(page, name) {
    await page.locator(`#lu-account-tab-${name}`).click();
    const panel = page.locator(`#lu-account-panel-${name}`);
    await expect(panel).toBeVisible();
    return panel;
}

async function submitAccount(page, panel, successful = true) {
    const response = page.waitForResponse(response => new URL(response.url()).pathname === '/users/account'
        && response.request().method() === 'POST');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        panel.locator('button[type="submit"]').click()
    ]);
    expect((await response).status()).toBe(302);
    if (successful) await expect(page.locator('.lu-notifications')).toContainText(typeof successful === 'string' ? successful : 'Account settings saved.');
}

for (const framework of ['bootstrap4', 'bootstrap5']) {
    test(`${framework}: account profile and avatar changes save through their visible forms`, async ({ page }) => {
        await openAccount(page, framework);
        const profile = await section(page, 'profile');
        const username = await profile.locator('#username').inputValue();
        const fullName = await profile.locator('#full_name').inputValue();
        await profile.locator('#full_name').fill(`${fullName} Updated`);
        await submitAccount(page, profile);
        await page.reload();
        await expect(profile.locator('#username')).toHaveValue(username);
        await expect(profile.locator('#full_name')).toHaveValue(`${fullName} Updated`);
        await profile.locator('#full_name').fill(fullName);
        await submitAccount(page, profile);

        const avatar = await section(page, 'avatar');
        await avatar.locator('#avatar_source').selectOption('initials');
        await submitAccount(page, avatar);
        await page.reload();
        await section(page, 'avatar');
        await expect(avatar.locator('#avatar_source')).toHaveValue('initials');
        await expect(page.locator('.lu-account-identity [data-lu-initials]')).toBeVisible();
        await avatar.locator('#avatar_source').selectOption('inherit');
        await submitAccount(page, avatar);
        await page.reload();
        await section(page, 'avatar');
        await expect(avatar.locator('#avatar_source')).toHaveValue('inherit');
    });

    test(`${framework}: self-account light and dark highlights save and reset to inheritance`, async ({ page }) => {
        await openAccount(page, framework);
        const appearance = await section(page, 'appearance');
        const light = appearance.locator('#user-card-gradient-highlight-color');
        const dark = appearance.locator('#user-card-dark-gradient-highlight-color');
        const inheritLight = appearance.locator('[data-lu-inherit-color="user-card-gradient-highlight-color"]');
        const inheritDark = appearance.locator('[data-lu-inherit-color="user-card-dark-gradient-highlight-color"]');
        await inheritLight.uncheck();
        await light.fill('#b14c8a');
        await inheritDark.uncheck();
        await dark.fill('#139481');
        await submitAccount(page, appearance);
        await page.reload();
        await section(page, 'appearance');
        await expect(light).toHaveValue('#b14c8a');
        await expect(dark).toHaveValue('#139481');
        await expect(inheritLight).not.toBeChecked();
        await expect(inheritDark).not.toBeChecked();
        await expect(page.locator('.lu-account-card')).toHaveCSS('--lu-profile-glow', '#b14c8a48');
        for (const target of ['user-card-gradient-highlight-color', 'user-card-dark-gradient-highlight-color']) {
            await appearance.locator(`[data-lu-appearance-reset="${target}"]`).click();
        }
        await submitAccount(page, appearance);
        await page.reload();
        await section(page, 'appearance');
        await expect(inheritLight).toBeChecked();
        await expect(inheritDark).toBeChecked();
        await expect(light).toBeDisabled();
        await expect(dark).toBeDisabled();
        await expect(light).toHaveValue('#ffffff');
        await expect(dark).toHaveValue('#ffffff');
    });

    test(`${framework}: account changes reject incorrect passwords and deletion requires confirmation`, async ({ page }) => {
        await openAccount(page, framework);
        const email = await section(page, 'account');
        const currentEmail = await email.locator('#email').inputValue();
        await email.locator('#email').fill('unapproved-change@example.com');
        await email.locator('#email-current-password').fill('incorrect-password');
        await submitAccount(page, email, false);
        await expect(email.locator('.lu-field-error')).toHaveText('Your current password is incorrect.');
        await page.reload();
        await expect(email.locator('#email')).toHaveValue(currentEmail);
        await section(page, 'account');
        await email.locator('#email').fill('unapproved-change@example.com');
        await email.locator('#email-current-password').fill('password');
        await submitAccount(page, email, 'Confirmation emails sent. Confirm both addresses to finish changing your email.');
        await expect(page.locator('.lu-account-summary')).toContainText('unapproved-change@example.com');
        await page.reload();
        await expect(email.locator('#email')).toHaveValue(currentEmail);

        await test.step('Reject incorrect current passwords and accept verified password changes', async () => {
            const security = await section(page, 'security');
            await security.locator('#password-current-password').fill('incorrect-password');
            await security.locator('#password').fill('RejectedChange9!');
            await security.locator('#password_confirmation').fill('RejectedChange9!');
            await submitAccount(page, security, false);
            await expect(security.locator('.lu-field-error')).toHaveText('Your current password is incorrect.');
            await security.locator('#password-current-password').fill('password');
            await security.locator('#password').fill('BrowserAccount9!');
            await security.locator('#password_confirmation').fill('BrowserAccount9!');
            await submitAccount(page, security);
            await expect(page.locator('.lu-account-summary')).not.toContainText('unapproved-change@example.com');
            await security.locator('#password-current-password').fill('BrowserAccount9!');
            await security.locator('#password').fill('password');
            await security.locator('#password_confirmation').fill('password');
            await submitAccount(page, security);

        });

        const admin = await section(page, 'admin');
        await admin.locator('[data-lu-account-delete]').click();
        const dialog = page.locator('#lu-account-delete-dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog.locator('#delete-current-password')).toBeFocused();
        const confirm = dialog.locator('[data-lu-account-delete-confirm]');
        await expect(confirm).toBeDisabled();
        await dialog.locator('[name="confirmation"]').fill('Delete');
        await expect(confirm).toBeDisabled();
        await dialog.locator('[name="confirmation"]').fill('delete');
        await expect(confirm).toBeEnabled();
        await dialog.getByRole('button', { name: 'Cancel', exact: true }).click();
        await expect(dialog).toBeHidden();
        await admin.locator('[data-lu-account-delete]').click();
        await expect(dialog.locator('[name="confirmation"]')).toBeEmpty();
        await expect(confirm).toBeDisabled();
        await page.keyboard.press('Escape');
        await expect(dialog).toBeHidden();
        await expect(page.locator('.lu-account-card')).toBeVisible();
    });

    test(`${framework}: account tabs and invalid public confirmations remain accessible in both themes`, async ({ page }) => {
        await openAccount(page, framework);
        const tabs = page.locator('[data-lu-account-tabs]');
        for (const theme of ['light', 'dark']) {
            await page.emulateMedia({ colorScheme: theme });
            if (await page.locator('#lu-theme svg:not([hidden])').getAttribute('data-theme-icon') !== theme) await page.locator('#lu-theme').click();
            for (const width of [320, 390, 768, 1440]) {
                await page.setViewportSize({ width, height: 1000 });
                for (const name of ['profile', 'avatar', 'appearance', 'account', 'security', 'admin']) {
                    const panel = await section(page, name);
                    if (name === 'admin') {
                        await expect(panel.locator('.lu-account-danger')).toHaveCSS('border-top-width', '0px');
                        await expect(panel.locator('.lu-account-danger')).toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
                        if (theme === 'dark') {
                            await expect(panel.locator('h2')).toHaveCSS('color', 'rgb(255, 255, 255)');
                            await expect(panel.locator('p')).toHaveCSS('color', 'rgb(255, 255, 255)');
                        }
                    }
                    if (name === 'appearance') await expect(panel.locator('.lu-user-appearance')).toHaveCSS('border-top-width', '0px');
                    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
                    for (const control of await panel.locator('input:visible, select:visible, button:visible').all()) {
                        const bounds = await control.boundingBox();
                        expect(bounds.x).toBeGreaterThanOrEqual(0);
                        expect(bounds.x + bounds.width).toBeLessThanOrEqual(width);
                    }
                }
            }
            await section(page, 'profile');
            const result = await new AxeBuilder({ page }).include('.lu-account-card').include('.lu-breadcrumbs').withTags(['wcag2a', 'wcag2aa']).analyze();
            expect(result.violations.map(violation => ({ id: violation.id, nodes: violation.nodes.map(node => node.html) }))).toEqual([]);
        }
        await page.setViewportSize({ width: 1280, height: 900 });
        await tabs.locator('#lu-account-tab-profile').focus();
        await page.keyboard.press('ArrowRight');
        await expect(tabs.locator('#lu-account-tab-avatar')).toBeFocused();
        await page.keyboard.press('End');
        await expect(tabs.locator('#lu-account-tab-admin')).toBeFocused();
        await page.keyboard.press('Home');
        await expect(tabs.locator('#lu-account-tab-profile')).toBeFocused();

        for (const theme of ['light', 'dark']) {
            await page.emulateMedia({ colorScheme: theme });
            const response = await page.goto('/users/account-link/invalid');
            expect(response.status()).toBe(410);
            await expect(page.getByRole('heading', { name: 'This account link is invalid', exact: true })).toBeVisible();
            await expect(page.locator('form')).toHaveCount(0);
            const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa']).analyze();
            expect(result.violations).toEqual([]);
        }
    });
}
