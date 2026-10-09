const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

const frameworks = ['bootstrap4', 'bootstrap5', 'tailwind', 'materialize', 'material3', 'bulma', 'foundation'];

test.beforeEach(async ({page}) => {
    await page.route(/^https:\/\/(?:www\.gravatar\.com|api\.dicebear\.com|ui-avatars\.com)\//, route => route.fulfill({status: 404, body: ''}));
});

async function seedAppearance(page, framework, values = {}) {
    await page.goto('/__browser/' + framework + '?settings=1&appearance=1&accounts=1&published-assets=1');
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const initial = {
        _token: token, _method: 'PUT', avatar_source: 'initials', profile_color: '#264e36', edit_color: '#705000',
        profile_gradient: '1', edit_gradient: '1', profile_gradient_strength: '50', edit_gradient_strength: '50',
        profile_dark_color: '#264e36', edit_dark_color: '#705000', profile_dark_gradient: '1', edit_dark_gradient: '1',
        profile_dark_gradient_strength: '50', edit_dark_gradient_strength: '50',
        profile_gradient_highlight_color: '#ffffff', edit_gradient_highlight_color: '#ffffff',
        profile_dark_gradient_highlight_color: '', edit_dark_gradient_highlight_color: '', show_breadcrumbs: '0', ...values
    };
    expect((await page.request.post('/users/settings', {form: initial})).ok()).toBeTruthy();
    const edit = await page.request.get('/users/1/edit');
    const identity = await page.evaluate(html => {
        const form = new DOMParser().parseFromString(html, 'text/html').querySelector('form[action$="/users/1"]');
        return {name: form.querySelector('[name="name"]').value, email: form.querySelector('[name="email"]').value};
    }, await edit.text());
    const inheritance = {
        user_card_color: '', user_card_dark_color: '', user_card_gradient: 'inherit', user_card_dark_gradient: 'inherit',
        user_card_gradient_strength: '', user_card_dark_gradient_strength: '',
        user_card_gradient_highlight_color: '', user_card_dark_gradient_highlight_color: ''
    };
    expect((await page.request.post('/users/1', {form: {...identity, ...inheritance, _token: token, _method: 'PUT'}})).ok()).toBeTruthy();
    return identity;
}

async function saveSettings(page) {
    const submitted = page.waitForResponse(response => new URL(response.url()).pathname === '/users/settings' && response.request().method() === 'POST');
    await page.getByRole('button', { name: 'Save settings', exact: true }).click();
    expect((await submitted).status()).toBe(302);
}

async function saveUser(page, framework) {
    await page.getByRole('button', {name: /^Save changes$/i}).click();
    const submitted = page.waitForResponse(response => new URL(response.url()).pathname === '/users/1' && response.request().method() === 'POST');
    await page.locator(framework === 'bootstrap4' ? '#confirmSave #confirm' : '#lu-confirm-submit').click();
    const response = await submitted;
    expect(response.status()).toBe(302);
    return new URLSearchParams(response.request().postData());
}

async function assertBreadcrumbs(page, labels) {
    const breadcrumbs = page.locator('.lu-breadcrumbs');
    expect((await breadcrumbs.locator('li').allTextContents()).map(label => label.trim())).toEqual(labels);
    await expect(breadcrumbs.locator('[aria-current="page"]')).toHaveText(labels.at(-1));
    const header = await page.locator('nav.navbar, #laravelusers .lu-toolbar').first().boundingBox();
    const path = await breadcrumbs.boundingBox();
    expect(path.y).toBeGreaterThanOrEqual(header.y + header.height);
}

async function setTheme(page, theme) {
    const toggle = page.locator('#lu-theme');
    if (await toggle.locator('svg:not([hidden])').getAttribute('data-theme-icon') !== theme) await toggle.click();
    await expect(toggle.locator('svg:not([hidden])')).toHaveAttribute('data-theme-icon', theme);
}

for (const framework of frameworks) {
    test(framework + ': avatar previews follow unsaved sources and preserve saved defaults', async ({ page }) => {
        await seedAppearance(page, framework);
        await page.goto('/users/settings');
        const source = page.locator('#settings-avatar');
        const previews = page.locator('[data-lu-avatar-preview]');
        await expect(previews).toHaveCount(4);
        expect(await previews.locator('[data-lu-initials]').allTextContents()).toEqual(['JE', 'CM', 'TR', 'AP']);
        await expect(page.locator('#settings-breadcrumbs')).not.toBeChecked();
        await expect(page.locator('.lu-breadcrumbs')).toHaveCount(0);

        await source.selectOption('ui-avatars');
        await expect(previews.locator('img')).toHaveCount(4);
        const localImages = await previews.locator('img').evaluateAll(images => images.map(image => image.src));
        expect(new Set(localImages).size).toBe(4);
        expect(localImages.every(url => url.startsWith('data:image/svg+xml;base64,'))).toBeTruthy();
        await source.selectOption('avatar');
        await expect(previews.locator('img').first()).toHaveAttribute('src', /\/users\/settings\/avatar-preview\/profile$/);
        await expect.poll(() => previews.locator('img').evaluateAll(images => images.every(image => image.complete && image.naturalWidth > 0))).toBe(true);
        expect(new Set(await previews.locator('img').evaluateAll(images => images.map(image => image.src))).size).toBe(4);
        await source.selectOption('initials');
        await expect(previews.locator('img')).toHaveCount(0);
        for (const initials of await previews.locator('[data-lu-initials]').all()) await expect(initials).toBeVisible();
        const providerResponse = page.waitForResponse(response => new URL(response.url()).pathname === '/users/settings/avatar-preview' && response.request().method() === 'POST' && response.request().postDataJSON()?.avatar_source === 'gravatar');
        await source.selectOption('gravatar');
        const providerSamples = (await (await providerResponse).json()).avatars;
        const providerSources = Object.values(providerSamples).map(sample => sample.avatar.src);
        if (providerSources[0]) {
            expect(new Set(providerSources).size).toBe(4);
            for (const url of providerSources) {
                const provider = new URL(url);
                expect(provider.hostname).toBe('www.gravatar.com');
                expect(provider.searchParams.get('d')).toBe('404');
                expect(provider.searchParams.get('r')).toBe('g');
            }
        } else expect(providerSources).toEqual([null, null, null, null]);
        await expect(previews.locator('img')).toHaveCount(0);
        for (const [kind, sample] of Object.entries(providerSamples)) {
            const fallback = sample.avatar.fallback === 'initials' ? '[data-lu-initials]' : '[data-lu-avatar-icon]';
            await expect(page.locator('[data-lu-avatar-preview="' + kind + '"]').locator(fallback)).toBeVisible();
        }
        await source.selectOption('ui-avatars');
        await expect(previews.locator('img')).toHaveCount(4);
        await page.goto('/users');
        await page.goto('/users/settings');
        await expect(source).toHaveValue('initials');
        await expect(previews.locator('img')).toHaveCount(0);
    });

    test(framework + ': global highlights and breadcrumbs retain appearance defaults', async ({ page }) => {
        await seedAppearance(page, framework);
        await page.goto('/users/settings');
        await page.locator('#settings-profile-gradient').check();
        await page.locator('#settings-profile-dark-gradient').check();
        await page.locator('#settings-profile-gradient-highlight-color').fill('#f6be43');
        await expect(page.locator('#settings-profile-color')).toHaveValue('#264e36');
        await expect(page.locator('[data-lu-avatar-preview="profile"]')).toHaveCSS('background-color', 'rgb(38, 78, 54)');
        expect(await page.locator('[data-lu-avatar-preview="profile"]').evaluate(panel => getComputedStyle(panel).backgroundImage)).toContain('246, 190, 67');
        const inheritDark = page.locator('[data-lu-inherit-color="settings-profile-dark-gradient-highlight-color"]');
        const darkHighlight = page.locator('#settings-profile-dark-gradient-highlight-color');
        await expect(inheritDark).toBeChecked();
        await expect(darkHighlight).toBeDisabled();
        await expect(darkHighlight).toHaveValue('#f6be43');
        await inheritDark.uncheck();
        await darkHighlight.fill('#72c5d9');
        expect(await page.locator('[data-lu-avatar-preview="profile_dark"]').evaluate(panel => getComputedStyle(panel).backgroundImage)).toContain('114, 197, 217');
        await page.locator('[data-lu-appearance-reset="settings-profile-dark-gradient-highlight-color"]').click();
        await expect(inheritDark).toBeChecked();
        await expect(darkHighlight).toBeDisabled();
        await expect(darkHighlight).toHaveValue('#f6be43');
        await page.locator('#settings-breadcrumbs').check();
        for (const width of [390, 768, 1440]) {
            await page.setViewportSize({ width, height: 900 });
            expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
        }
        for (const theme of ['light', 'dark']) {
            await setTheme(page, theme);
            const results = await new AxeBuilder({ page }).include('#lu-settings-appearance').withTags(['wcag2a', 'wcag2aa']).analyze();
            expect(results.violations.map(violation => ({ id: violation.id, nodes: violation.nodes.map(node => node.html) }))).toEqual([]);
        }
        await saveSettings(page);
        await page.goto('/users');
        await assertBreadcrumbs(page, ['Home', 'Users']);
        await page.goto('/users/1');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('--lu-profile-glow', '#f6be4348');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('--lu-profile-dark-glow', '#f6be4348');
        await page.goto('/users/settings');
        await assertBreadcrumbs(page, ['Home', 'Users', 'Settings']);
        await page.locator('#settings-breadcrumbs').uncheck();
        await saveSettings(page);
        await page.goto('/users');
        await expect(page.locator('.lu-breadcrumbs')).toHaveCount(0);
    });

    test(framework + ': individual highlights save and reset to effective inheritance', async ({ page }) => {
        const identity = await seedAppearance(page, framework, {profile_gradient_highlight_color: '#f6be43', show_breadcrumbs: '1'});
        await page.goto('/users/1/edit');
        await assertBreadcrumbs(page, ['Home', 'Users', identity.name, 'Edit user']);
        const tabs = page.getByRole('tablist', { name: 'Edit user sections', exact: true });
        if (await tabs.count()) await tabs.getByRole('tab', { name: 'Appearance', exact: true }).click();
        await page.locator('[data-lu-inherit-color="user-card-gradient-highlight-color"]').uncheck();
        await page.locator('#user-card-gradient-highlight-color').fill('#b14c8a');
        await expect(page.locator('#user-card-dark-gradient-highlight-color')).toBeDisabled();
        await expect(page.locator('#user-card-dark-gradient-highlight-color')).toHaveValue('#b14c8a');
        await page.locator('[data-lu-inherit-color="user-card-dark-gradient-highlight-color"]').uncheck();
        await page.locator('#user-card-dark-gradient-highlight-color').fill('#139481');
        const selectedPayload = await saveUser(page, framework);
        expect(selectedPayload.getAll('user_card_gradient_highlight_color')).toEqual(['', '#b14c8a']);
        expect(selectedPayload.getAll('user_card_dark_gradient_highlight_color')).toEqual(['', '#139481']);
        await page.goto('/users/1');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('--lu-profile-glow', '#b14c8a48');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('--lu-profile-dark-glow', '#13948148');
        await page.goto('/users/1/edit');
        if (await tabs.count()) await tabs.getByRole('tab', { name: 'Appearance', exact: true }).click();
        await page.locator('[data-lu-appearance-reset="user-card-gradient-highlight-color"]').click();
        await page.locator('[data-lu-appearance-reset="user-card-dark-gradient-highlight-color"]').click();
        for (const field of ['user-card-gradient-highlight-color', 'user-card-dark-gradient-highlight-color']) {
            await expect(page.locator('[data-lu-inherit-color="' + field + '"]')).toBeChecked();
            await expect(page.locator('#' + field)).toBeDisabled();
            await expect(page.locator('#' + field)).toHaveValue('#f6be43');
        }
        const resetValues = await page.locator('#user-card-gradient-highlight-color').evaluate(input => Object.fromEntries([...new FormData(input.form)].filter(([name]) => name.endsWith('_gradient_highlight_color'))));
        expect(resetValues).toEqual({user_card_gradient_highlight_color: '', user_card_dark_gradient_highlight_color: ''});
        const resetPayload = await saveUser(page, framework);
        expect(resetPayload.getAll('user_card_gradient_highlight_color')).toEqual(['']);
        expect(resetPayload.getAll('user_card_dark_gradient_highlight_color')).toEqual(['']);
        await page.goto('/users/1');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('--lu-profile-glow', '#f6be4348');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('--lu-profile-dark-glow', '#f6be4348');
    });
}
