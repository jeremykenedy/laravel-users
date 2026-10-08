const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

test.use({ timezoneId: 'America/Los_Angeles' });

test('goodbye email options collapse and expand in settings', async ({page}) => {
    await page.goto('/__browser/bootstrap5?settings=1&soft-deletes=1&account-links=1');
    await page.goto('/users/settings#emails');
    const options = page.locator('#lu-email-templates-form .lu-goodbye-settings');
    await expect(options).toHaveCount(1);
    await expect(options).not.toHaveAttribute('open', '');
    await expect(options.locator('#goodbye-expiry-mode')).not.toBeVisible();
    await options.locator('summary').click();
    await expect(options).toHaveAttribute('open', '');
    await expect(options.locator('#goodbye-expiry-mode')).toBeVisible();
    await options.locator('summary').click();
    await expect(options.locator('#goodbye-expiry-mode')).not.toBeVisible();
});

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: settings fit mobile, tablet and desktop widths`, async ({ page }) => {
        for (const fullWidth of [0, 1]) {
            await page.goto(`/__browser/${framework}?settings=1&packages=1&full-width=${fullWidth}`);
            await page.goto('/users/settings');
            await page.getByRole('tab', {name:'Packages',exact:true}).click();
            await expect(page.getByRole('link', {name: 'Configure a queue', exact: true})).toHaveAttribute('href', /\/docs\/\d+\.x\/queues#introduction$/);
            await expect(page.getByRole('link', {name: 'Run a queue worker', exact: true})).toHaveAttribute('href', /#running-the-queue-worker$/);
            await expect(page.getByRole('link', {name: 'Configure shared cache locks', exact: true})).toHaveAttribute('href', /#atomic-locks$/);
            for (const width of [320, 390, 700, 701, 900, 901, 1024, 1170, 1280]) {
                await page.setViewportSize({width, height: 850});
                expect(await page.evaluate(() => document.documentElement.scrollWidth), `${framework}, full width ${fullWidth}, viewport ${width}`).toBeLessThanOrEqual(width);
                for (const button of await page.locator('.lu-settings-choice button').all()) {
                    expect(await button.evaluate(element => element.getBoundingClientRect().right <= element.closest('.lu-settings-choice').getBoundingClientRect().right)).toBe(true);
                }
            }
        }
    });

    test(`${framework}: dark card appearance saves globally and individually`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?settings=1&appearance=1&soft-deletes=1`);
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        const userData = {name: 'Alex Rivers', email: 'user1@example.com', user_card_color: '', user_card_gradient: 'inherit', user_card_gradient_strength: '', user_card_dark_color: '', user_card_dark_gradient: 'inherit', user_card_dark_gradient_strength: ''};
        await page.request.post('/users/2/restore', {form: {_token: token}});
        await page.request.post('/users/2', {form: {_token: token, _method: 'PUT', ...userData}});
        await page.goto('/users/settings');
        await page.locator('#settings-profile-color').fill('#264e36');
        await page.locator('#settings-profile-dark-color').fill('#19283a');
        await page.locator('#settings-profile-dark-gradient').uncheck();
        await page.locator('#settings-profile-dark-strength').fill('75');
        await page.locator('#settings-edit-dark-color').fill('#49340e');
        await page.locator('#settings-edit-dark-gradient').check();
        await page.locator('#settings-edit-dark-strength').fill('80');
        await page.getByRole('button', {name: 'Save settings', exact: true}).click();
        await page.goto('/users/2');
        await setTheme(page, 'dark');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-color', 'rgb(25, 40, 58)');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-image', 'none');
        await setTheme(page, 'light');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-color', 'rgb(38, 78, 54)');
        await page.goto('/users/2/edit');
        await setTheme(page, 'dark');
        if (framework !== 'bootstrap4') {
            await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-color', 'rgb(73, 52, 14)');
            expect(await page.locator('.lu-profile-identity').evaluate(element => getComputedStyle(element).backgroundImage)).toContain('radial-gradient');
        }
        await openEditSection(page, 'Appearance');
        await page.locator('[data-lu-inherit-color="user-card-dark-color"]').uncheck();
        await page.locator('#user-card-dark-color').fill('#503260');
        await page.locator('#user-card-dark-gradient').selectOption('on');
        await page.locator('[data-lu-inherit-strength="user-card-dark-strength"]').uncheck();
        await page.locator('#user-card-dark-strength').fill('65');
        await page.getByRole('button', {name: /^Save changes$/i}).click();
        if (framework === 'bootstrap4') await page.locator('#confirmSave #confirm').click();
        else await page.locator('#lu-confirm-submit').click();
        await page.goto('/users/2');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-color', 'rgb(80, 50, 96)');
        await page.goto('/users');
        await page.getByRole('button', {name: 'Card view', exact: true}).click();
        await page.locator('#user_search_box').fill('Alex');
        await page.locator('#user_search_box').press('Enter');
        const avatar = page.locator('[data-lu-table] tbody tr:visible').filter({hasText: 'Alex Rivers'}).locator('td:has(.lu-avatar)');
        await expect(avatar).toHaveCSS('background-color', 'rgb(80, 50, 96)');
        await setTheme(page, 'light');
        await expect(avatar).toHaveCSS('background-color', 'rgb(38, 78, 54)');
        await page.request.post('/users/2', {form: {_token: token, _method: 'DELETE'}});
        await page.goto('/users/deleted');
        await page.getByRole('button', {name: 'Card view', exact: true}).click();
        await setTheme(page, 'dark');
        await expect(page.locator('[data-lu-table] tbody tr').filter({hasText: 'Alex Rivers'}).locator('td:has(.lu-avatar)')).toHaveCSS('background-color', 'rgb(80, 50, 96)');
        await page.goto('/users/deleted/2/edit');
        await openEditSection(page, 'Appearance');
        await page.locator('[data-lu-appearance-reset="user-card-dark-color"]').click();
        await expect(page.locator('#user-card-dark-color')).toBeDisabled();
        await expect(page.locator('[data-lu-inherit-color="user-card-dark-color"]')).toBeChecked();
        await page.goto('/users/settings');
        await page.locator('[data-lu-appearance-reset="settings-profile-dark-color"]').click();
        await expect(page.locator('#settings-profile-dark-color')).toHaveValue('#2458b7');
        await page.locator('[data-lu-appearance-reset="settings-profile-dark-strength"]').click();
        await expect(page.locator('#settings-profile-dark-strength')).toHaveValue('50');
        await page.request.post('/users/2/restore', {form: {_token: token}});
        await page.request.post('/users/2', {form: {_token: token, _method: 'PUT', ...userData}});
        await page.request.post('/users/settings', {form: {_token: token, _method: 'PUT', avatar_source: 'initials', profile_color: '#2458b7', edit_color: '#705000', profile_gradient: '1', profile_gradient_strength: '50', edit_gradient: '1', edit_gradient_strength: '50', profile_dark_color: '#2458b7', profile_dark_gradient: '1', profile_dark_gradient_strength: '50', edit_dark_color: '#705000', edit_dark_gradient: '1', edit_dark_gradient_strength: '50'}});
    });

    test(`${framework}: settings, individual card colors, notifications and user menu`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/__browser/${framework}?settings=1&appearance=1&avatar-preferences=1`);
        const setupToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        await page.request.post('/users/2', {form: {_token: setupToken, _method: 'PUT', name: 'Alex Rivers', email: 'user1@example.com', user_card_color: '', user_card_gradient: 'inherit', user_card_gradient_strength: ''}});
        await page.locator('.lu-user-menu summary').click();
        await expect(page.getByRole('button', { name: 'Log out', exact: true })).toBeVisible();
        await page.locator('#user_search_box').click();
        await expect(page.locator('.lu-user-menu')).not.toHaveAttribute('open');
        await expect(page.locator('.lu-user-menu .lu-avatar')).toHaveCSS('border-radius', '50%');
        await page.getByRole('link', { name: 'User settings', exact: true }).click();
        if (framework !== 'bootstrap4') {
            await expect(page.locator('.lu-settings-panel > header')).toHaveClass(/lu-card-heading/);
            await expect(page.locator('.lu-settings-panel > header h1')).toHaveCSS('font-size', '20px');
            await expect(page.locator('.lu-settings-panel > header')).toHaveCSS('padding-left', '24px');
        }
        await page.locator('#settings-profile-strength').fill('80');
        await expect(page.locator('output[for="settings-profile-strength"]')).toHaveText('80%');
        await page.getByRole('button', { name: 'Reset Gradient strength to default', exact: true }).first().click();
        await expect(page.locator('#settings-profile-strength')).toHaveValue('50');
        await page.locator('#settings-profile-color').fill('#123456');
        await page.getByRole('button', { name: 'Reset View user card color to default', exact: true }).click();
        await expect(page.locator('#settings-profile-color')).toHaveValue('#2458b7');
        await page.locator('#settings-avatar').selectOption('ui-avatars');
        await page.locator('#settings-profile-color').fill('#264e36');
        await page.locator('#settings-edit-color').fill('#705000');
        await page.locator('#settings-profile-gradient').uncheck();
        await expect(page.locator('#settings-notifications')).toHaveCount(0);
        await page.getByRole('button', { name: 'Save settings', exact: true }).click();
        await expect(page.locator('.lu-flash')).toContainText('User settings saved.');
        await page.locator('.lu-flash').getByRole('button', { name: 'Close', exact: true }).click();
        await expect(page.locator('.lu-flash')).toHaveCount(0);
        await page.goto('/users/2');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-color', 'rgb(38, 78, 54)');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-image', 'none');
        const image = page.locator('.lu-profile-identity .lu-avatar img');
        await expect(image).toHaveAttribute('src', /^data:image\/svg\+xml;base64,/);
        await expect.poll(() => image.evaluate(element => element.naturalWidth)).toBe(256);
        await page.goto('/users/2/edit');
        await openEditSection(page, 'Appearance');
        await page.getByLabel('Use global color', { exact: true }).first().uncheck();
        await page.locator('#user-card-color').fill('#6b3e79');
        await page.locator('#user-card-gradient').selectOption('on');
        await page.getByLabel('Use global gradient strength', { exact: true }).first().uncheck();
        await page.locator('#user-card-strength').fill('65');
        await page.getByRole('button', { name: /^Save changes$/i }).click();
        if (framework === 'bootstrap4') await page.locator('#confirmSave #confirm').click();
        else await page.locator('#lu-confirm-submit').click();
        await expect(page.locator('.lu-flash')).toContainText('Successfully updated user');
        const profileWidth = await page.locator(framework === 'bootstrap4' ? '.card' : '.lu-profile').first().evaluate(element => element.getBoundingClientRect().width);
        const alertWidth = await page.locator('.lu-flash').evaluate(element => element.getBoundingClientRect().width);
        expect(alertWidth).toBeLessThanOrEqual(profileWidth + 1);
        await page.goto('/users/2');
        await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-color', 'rgb(107, 62, 121)');
        expect(await page.locator('.lu-profile-identity').evaluate(element => getComputedStyle(element).backgroundImage)).toContain('radial-gradient');
        await page.goto('/users');
        await page.getByRole('button', { name: 'Card view', exact: true }).click();
        await page.locator('#user_search_box').fill('Alex');
        await page.locator('#user_search_box').press('Enter');
        const row = page.locator('[data-lu-table] tbody tr:visible').filter({ hasText: 'Alex Rivers' });
        await expect(row.locator('td:has(.lu-avatar)')).toHaveCSS('background-color', 'rgb(107, 62, 121)');
        expect(await row.locator('td:has(.lu-avatar)').evaluate(element => getComputedStyle(element).backgroundImage)).toContain('radial-gradient');
        await page.setViewportSize({ width: 390, height: 844 });
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        const reset = await page.request.post('/users/2', {form: {_token: token, _method: 'PUT', name: 'Alex Rivers', email: 'user1@example.com', user_card_color: '', user_card_gradient: 'inherit', user_card_gradient_strength: ''}});
        expect(reset.ok()).toBe(true);
        await page.goto('/users/settings');
        if (framework !== 'bootstrap4') {
            const accessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(accessibility.violations).toEqual([]);
        }
        await page.locator('#settings-avatar').selectOption('initials');
        await page.locator('#settings-profile-color').fill('#2458b7');
        await page.locator('#settings-profile-gradient').check();
        await page.getByRole('button', { name: 'Save settings', exact: true }).click();
        expect(errors).toEqual([]);
    });
}

test('standalone navigation components work without the package shell', async ({ page }) => {
    await page.goto('/__browser/bootstrap5');
    await setTheme(page, 'light');
    await page.goto('/__components');
    await expect(page.locator('#laravelusers')).toHaveCount(0);
    await expect(page.locator('.lu-user-menu .lu-avatar')).toHaveCSS('border-radius', '50%');
    await page.locator('#dashboard-theme').click();
    await expect(page.locator('html')).toHaveAttribute('data-lu-theme', 'dark');
    await expect(page.locator('html')).toHaveClass(/dark/);
    await expect(page.locator('#secondary-theme svg:not([hidden])')).toHaveAttribute('data-theme-icon', 'dark');
    await page.locator('.lu-user-menu summary').click();
    await expect(page.getByRole('button', {name: 'Log out', exact: true})).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('.lu-user-menu')).not.toHaveAttribute('open');
    await page.locator('.lu-user-menu summary').click();
    await page.getByRole('button', {name: 'Outside menu', exact: true}).click();
    await expect(page.locator('.lu-user-menu')).not.toHaveAttribute('open');
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('data-lu-theme', 'dark');
});

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: package confirmations, removal warning and role conflict`, async ({page}) => {
        await page.goto(`/__browser/${framework}?settings=1&packages=1`);
        await page.goto('/users/settings');
        await expect(page.locator('#lu-tab-appearance [data-lu-icon="appearance"]')).toBeVisible();
        await expect(page.locator('#lu-tab-notifications [data-lu-icon="notifications"]')).toBeVisible();
        await expect(page.locator('#lu-tab-packages [data-lu-icon="package"]')).toBeVisible();
        await page.getByRole('tab', {name:'Packages',exact:true}).click();
        await page.setViewportSize({width:390,height:844});
        await expect(page.locator('.lu-settings-tabs:not(.lu-edit-tabs):not(.lu-account-tabs)')).toHaveCSS('display','grid');
        await page.setViewportSize({width:900,height:844});
        await expect(page.locator('.lu-settings-tabs:not(.lu-edit-tabs):not(.lu-account-tabs)')).toHaveCSS('display','flex');
        await page.setViewportSize({width:390,height:844});
        const setupRequirements = page.locator('[data-lu-package-operation="setup"]');
        await expect(setupRequirements).toBeVisible();
        const requirementsComplete = await setupRequirements.isDisabled();
        await expect(setupRequirements).toContainText(requirementsComplete ? 'Package requirements completed' : 'Set up package requirements');
        const requirementActions = page.locator('.lu-package-requirement-actions');
        const verifyRequirements = page.getByRole('button', {name:'Verify package requirements',exact:true});
        await expect(verifyRequirements).toBeVisible();
        await expect(verifyRequirements.locator('[data-lu-icon="verify"]')).toBeVisible();
        await expect(requirementActions).toHaveCSS('flex-direction', 'column');
        await page.setViewportSize({width:900,height:844});
        await expect(requirementActions).toHaveCSS('flex-direction', 'row');
        await page.setViewportSize({width:390,height:844});
        await page.route('**/users/settings/packages/verify', route => route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({status:'completed',queue_ready:true,message:'Package requirements verified.'})}));
        await verifyRequirements.click();
        await expect(setupRequirements).toBeDisabled();
        await expect(setupRequirements.locator('[data-lu-package-requirements-label]')).toHaveText('Package requirements completed');
        await expect(page.locator('[data-lu-package-requirements-icon="complete"] [data-lu-icon="check"]')).toBeVisible();
        await page.unroute('**/users/settings/packages/verify');
        const cards = page.locator('.lu-package-settings .lu-settings-choice');
        const toastInstall = cards.filter({hasText: 'Laravel Toast'}).getByRole('button', {name:'Install',exact:true});
        await expect(toastInstall).toBeVisible();
        await expect(toastInstall).toContainText('Install');
        await expect(toastInstall).not.toHaveCSS('font-size','0px');
        await expect(toastInstall.locator('[data-lu-icon="install"]')).toBeVisible();
        await expect(cards.filter({hasText:'Laravel Toast'}).locator('.lu-package-badge-available')).toContainText('Not installed');
        await expect(cards.filter({hasText: 'Laravel Roles'}).getByRole('button', {name:'Install',exact:true})).toBeDisabled();
        await cards.filter({hasText: 'Spatie Permissions'}).getByRole('button', {name:'Remove',exact:true}).click();
        const modal = page.locator('#lu-package-dialog');
        await expect(modal).toBeVisible();
        await expect(modal.locator('[data-lu-package-role-warning]')).toContainText('can break sign-in');
        const submit = modal.getByRole('button', {name:'Confirm package change',exact:true});
        await expect(submit.locator('[data-lu-icon="check"]')).toBeVisible();
        await modal.locator('[name="confirmation"]').fill('continue');
        await modal.locator('[name="acknowledgement"]').check();
        await expect(submit).toBeDisabled();
        await modal.locator('[name="confirmation"]').fill('remove');
        await expect(submit).toBeEnabled();
        await expect(submit).toHaveCSS('background-color', 'rgb(180, 35, 50)');
        await expect(submit).toHaveCSS('color', 'rgb(255, 255, 255)');
        await expect(modal.locator('[data-lu-package-remove-icon]')).toBeVisible();
        await page.route('**/users/settings/packages', route => route.fulfill({status:422,contentType:'application/json',body:JSON.stringify({errors:{package:['Removal is blocked while your user model uses this package.']}})}));
        await submit.click();
        await expect(modal.locator('[data-lu-package-error]')).toContainText('Removal is blocked');
        await modal.getByRole('button', {name:'Cancel',exact:true}).click();
        await cards.filter({hasText: 'Spatie Permissions'}).getByRole('button', {name:'Remove',exact:true}).click();
        await expect(modal.locator('[name="confirmation"]')).toHaveValue('');
        await expect(modal.locator('[name="acknowledgement"]')).not.toBeChecked();
        await expect(submit).toBeDisabled();
        if (framework !== 'bootstrap4') {
            const result = await new AxeBuilder({page}).include('#lu-package-dialog').withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();
            expect(result.violations).toEqual([]);
        }
        await page.setViewportSize({width:390,height:844});
        await expect(submit).toHaveCSS('border-radius','5px');
        await expect(submit).toHaveCSS('cursor','not-allowed');
        await expect(modal.locator('[data-lu-package-remove-icon]')).toBeVisible();
        const bounds = await modal.boundingBox();
        expect(bounds.width).toBeLessThanOrEqual(390);
        expect(bounds.height).toBeLessThanOrEqual(844);
        await modal.getByRole('button', {name:'Cancel',exact:true}).click();
    });

    test(`${framework}: long names and emails scroll within table columns`, async ({page}) => {
        await page.goto(`/__browser/${framework}`);
        const name = 'A very long user name '.repeat(8).trim();
        const email = 'extremelylongemailaddress'.repeat(3) + '@example.com';
        await page.route('**/search-users', route => route.fulfill({contentType:'application/json',body:JSON.stringify([{id:2,name,email}])}));
        await page.locator('#user_search_box').fill('long');
        await page.locator('#user_search_box').press('Enter');
        const row = page.locator('[data-lu-table] tbody tr:visible').filter({hasText:name});
        const columns = row.locator('.lu-table-text');
        await expect(columns).toHaveCount(2);
        for (const column of await columns.all()) {
            await expect(column).toHaveCSS('max-width','240px');
            await expect(column).toHaveCSS('overflow-x','auto');
            expect(await column.evaluate(element => element.scrollWidth > element.clientWidth)).toBe(true);
            await expect(column).toHaveAttribute('tabindex','0');
        }
        await expect(row.getByRole('link',{name:email,exact:true})).toHaveAttribute('href','mailto:'+email);
        await page.getByRole('button',{name:'Card view',exact:true}).click();
        await expect(columns.first()).toHaveCSS('white-space','normal');
        await page.setViewportSize({width:390,height:844});
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    });
}

async function setTheme(page, theme) {
    const toggle = page.locator('#lu-theme');
    if (!await toggle.isVisible()) {
        await page.getByRole('button', { name: 'Toggle navigation' }).click();
    }
    for (let clicks = 0; clicks < 3; clicks++) {
        const current = await toggle.locator('svg:not([hidden])').getAttribute('data-theme-icon');
        if (current === theme) break;
        await toggle.click();
        await expect(toggle.locator('svg:not([hidden])')).not.toHaveAttribute('data-theme-icon', current);
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

test('bootstrap5: configurable profile gradient, contrast, and mobile back button', async ({ page }) => {
    for (const [color, background] of [['#264e36', 'rgb(38, 78, 54)'], ['#e2d5bb', 'rgb(226, 213, 187)']]) {
        await page.goto(`/__browser/bootstrap5?profile-color=${encodeURIComponent(color)}`);
        await page.getByRole('button', { name: 'Card view', exact: true }).click();
        const header = page.locator('#lu-users td:has(.lu-avatar)').first();
        await expect(header).toHaveCSS('background-color', background);
        const gradient = await header.evaluate(element => getComputedStyle(element).backgroundImage);
        expect(gradient).toContain('radial-gradient');
        await page.goto('/users/1');
        const panel = page.locator('.lu-profile-identity');
        await expect(panel).toHaveCSS('background-color', background);
        expect(await panel.evaluate(element => getComputedStyle(element).backgroundImage)).toBe(gradient);
        for (const theme of ['light', 'dark']) {
            await setTheme(page, theme);
            const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(result.violations).toEqual([]);
        }
    }
    await page.setViewportSize({ width: 390, height: 844 });
    const back = page.getByRole('link', { name: 'Back to users', exact: true });
    await expect(back).toHaveCSS('font-size', '0px');
    await expect(back).toHaveAttribute('title', 'Back to users');
    await expect(back.locator('svg')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await back.click();
    await expect(page.getByRole('heading', { name: 'Showing All Users', exact: true })).toBeVisible();
});

test('bootstrap5: edit profile, password meter, and email dialog', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/__browser/bootstrap5');
    await page.goto('/users/2/edit');
    await expect(page.locator('.lu-profile-header h1 svg')).toBeVisible();
    await expect(page.locator('.lu-profile-identity .lu-avatar')).toBeVisible();
    await expect(page.locator('.lu-profile-identity')).toHaveCSS('background-color', 'rgb(112, 80, 0)');
    expect((await page.locator('.lu-profile-header .lu-actions a').allTextContents()).map(text => text.trim())).toEqual(['View user', 'Back to users']);
    await openEditSection(page, 'Password');
    await expect(page.locator('[data-lu-password-meter]')).toBeHidden();
    await page.locator('#password').fill('abc');
    await expect(page.locator('[data-lu-password-strength]')).toHaveText('Weak');
    await page.locator('#password').fill('LongerPassword123!');
    await expect(page.locator('[data-lu-password-strength]')).toHaveText('Strong');
    await expect(page.locator('[data-lu-password-rule="length"]')).toHaveAttribute('data-lu-met', 'true');
    await page.locator('#password').fill('');
    await expect(page.locator('[data-lu-password-meter]')).toBeHidden();
    await page.getByRole('button', { name: 'Send user an email', exact: true }).click();
    const dialog = page.getByRole('dialog', { name: 'Send user an email', exact: true });
    await expect(dialog).toBeVisible();
    expect((await dialog.locator('footer').boundingBox()).y + (await dialog.locator('footer').boundingBox()).height).toBeLessThanOrEqual(page.viewportSize().height);
    await expect(dialog.locator('[data-lu-email-recipient]')).toContainText('Alex Rivers');
    await expect(dialog.getByLabel('Use greeting', { exact: true })).toBeChecked();
    await expect(dialog.getByLabel('Use closing', { exact: true })).toBeChecked();
    await expect(dialog.getByLabel('Greeting', { exact: true })).toHaveValue('Hi');
    await expect(dialog.getByLabel('Closing', { exact: true })).toHaveValue('Thanks');
    for (const theme of ['light', 'dark']) {
        await dialog.getByRole('button', { name: 'Cancel', exact: true }).click();
        await setTheme(page, theme);
        await page.getByRole('button', { name: 'Send user an email', exact: true }).click();
        const result = await new AxeBuilder({ page }).include('#lu-email-dialog').withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
        expect(result.violations).toEqual([]);
    }
    await dialog.getByLabel('Subject', { exact: true }).fill('Account update');
    await dialog.getByLabel('Message', { exact: true }).fill('Please review your account details.');
    await dialog.getByLabel('Name below closing (optional)', { exact: true }).fill('Admin');
    await dialog.getByRole('button', { name: 'Send email', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('1 email');
    await page.setViewportSize({ width: 390, height: 844 });
    for (const name of ['View user', 'Back to users']) {
        const button = page.getByRole('link', { name, exact: true });
        await expect(button).toHaveCSS('font-size', '0px');
        await expect(button).toHaveAttribute('title', name);
    }
    for (const button of await page.locator('.lu-form-actions .lu-button').all()) {
        await expect(button).toHaveCSS('font-size', '0px');
        const box = await button.boundingBox();
        const icon = await button.locator('svg').boundingBox();
        expect(Math.abs(box.x + box.width / 2 - icon.x - icon.width / 2)).toBeLessThan(1);
    }
    const accessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    expect(accessibility.violations).toEqual([]);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    expect(errors).toEqual([]);
});

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: edit icons focus inputs and password confirmation checks blur and debounce`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        await page.goto('/users/2/edit');
        if (framework === 'bootstrap4') await page.locator('.btn-change-pw').click();
        for (const field of ['name', 'email', 'password', 'password_confirmation']) {
            await openEditSection(page, field.includes('password') ? 'Password' : 'Profile');
            const icon = page.locator(`label[for="${field}"]`).filter({ has: page.locator('svg, i') });
            await icon.click();
            await expect(page.locator(`#${field}`)).toBeFocused();
        }
        const password = page.locator('#password');
        const confirmation = page.locator('#password_confirmation');
        const error = page.locator('#lu-password-confirmation-error');
        await password.fill('Abcdef123!');
        await confirmation.fill('different');
        await expect(error).toBeHidden();
        await page.getByRole('button', {name:/^Save changes$/i}).focus();
        await expect(error).toHaveText("Passwords don't match.");
        await expect(error).toBeVisible();
        await expect(confirmation).toHaveAttribute('aria-invalid', 'true');
        await confirmation.fill('Abcdef123!');
        await expect(error).toBeHidden();
        await confirmation.fill('different');
        await expect(error).toBeHidden();
        await expect(error).toBeVisible({ timeout: 3500 });
        await password.fill('');
        await confirmation.fill('');
        await expect(error).toBeHidden();
        if (framework !== 'bootstrap4') {
            const accessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(accessibility.violations).toEqual([]);
        }
    });

    test(`${framework}: create password feedback and optional per-user avatar choices`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?avatar-preferences=1`);
        await page.goto('/users/create');
        await expect(page.locator('#lu-email-dialog')).toBeHidden();
        await expect(page.getByLabel('Avatar source', { exact: true })).toHaveValue('inherit');
        await page.locator('#password').fill('abc');
        await expect(page.locator('[data-lu-password-strength]')).toHaveText('Weak');
        await page.locator('#password').fill('LongerPassword123!');
        await expect(page.locator('[data-lu-password-strength]')).toHaveText('Strong');
        await page.locator('#password_confirmation').fill('different');
        await page.locator('#email').click();
        await expect(page.locator('#lu-password-confirmation-error')).toBeVisible();
        await page.locator('#password_confirmation').fill('LongerPassword123!');
        await expect(page.locator('#lu-password-confirmation-error')).toBeHidden();
        await page.goto('/users/2/edit');
        await openEditSection(page, 'Appearance');
        await page.getByLabel('Avatar source', { exact: true }).selectOption('gravatar');
        await page.locator(framework === 'bootstrap4' ? '.btn-save' : 'form[action$="/users/2"] button[type="submit"]').click();
        await page.locator(framework === 'bootstrap4' ? '#confirmSave #confirm' : '#lu-confirm-submit').click();
        await openEditSection(page, 'Appearance');
        await expect(page.getByLabel('Avatar source', { exact: true })).toHaveValue('gravatar');
        await page.reload();
        await openEditSection(page, 'Appearance');
        await expect(page.getByLabel('Avatar source', { exact: true })).toHaveValue('gravatar');
        await page.getByLabel('Avatar source', { exact: true }).selectOption('inherit');
        await page.locator(framework === 'bootstrap4' ? '.btn-save' : 'form[action$="/users/2"] button[type="submit"]').click();
        await page.locator(framework === 'bootstrap4' ? '#confirmSave #confirm' : '#lu-confirm-submit').click();
        await openEditSection(page, 'Appearance');
        await expect(page.getByLabel('Avatar source', { exact: true })).toHaveValue('inherit');
    });

    test(`${framework}: bulk email recipient chips remove users and scroll`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        await page.route('**/search-users', route => route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify([{ id: 2, name: 'Alex Rivers', email: 'user1@example.com' }, { id: 3, name: 'Sam Parker', email: 'user2@example.com' }])
        }));
        await page.locator('#user_search_box').fill('recipients');
        await page.locator('#user_search_box').press('Enter');
        const results = page.locator(framework === 'bootstrap4' ? '#search_results' : '#lu-results');
        await expect(results.locator('tr')).toHaveCount(2);
        await page.getByRole('button', { name: 'Select all', exact: true }).click();
        await page.locator('#lu-bulk-action').selectOption('message');
        await page.locator('#lu-bulk-submit').click();
        await expect(page.locator('[data-lu-email-recipient]')).toHaveText('Recipients: 2');
        await page.getByRole('button', { name: 'Remove Sam Parker', exact: true }).click();
        await expect(page.locator('[data-lu-email-recipient]')).toHaveText('Recipients: 1');
        await expect(page.locator('#lu-email-form [name="ids[]"]')).toHaveValue('2');
        await page.getByLabel('Subject', { exact: true }).fill('Account update');
        await page.getByLabel('Message', { exact: true }).fill('Please review your account details.');
        const sent = page.waitForResponse(response => response.url().endsWith('/users/email') && response.request().method() === 'POST');
        await page.getByRole('dialog').getByRole('button', { name: 'Send email', exact: true }).click();
        const response = await sent;
        expect(new URLSearchParams(response.request().postData()).getAll('ids[]')).toEqual(['2']);
        await expect(page.getByRole('status').filter({ hasText: '1 email queued.' })).toBeVisible();
        await page.route('**/search-users', route => route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify(Array.from({ length: 25 }, (_, index) => ({ id: 10000 + index, name: `Bulk recipient number ${index + 1}`, email: `recipient${index}@example.com` })))
        }));
        await setTheme(page, 'dark');
        await page.locator('#user_search_box').fill('many recipients');
        await page.locator('#user_search_box').press('Enter');
        await expect(results.locator('tr')).toHaveCount(25);
        await page.getByRole('button', { name: 'Select all', exact: true }).click();
        await page.locator('#lu-bulk-action').selectOption('message');
        await page.locator('#lu-bulk-submit').click();
        const chips = page.locator('[data-lu-email-chips]');
        await expect(chips.getByRole('listitem')).toHaveCount(25);
        for (const width of [1280, 390]) {
            await page.setViewportSize({ width, height: 844 });
            const dimensions = await chips.evaluate(element => ({ height: getComputedStyle(element).height, overflow: getComputedStyle(element).overflowY, client: element.clientHeight, scroll: element.scrollHeight }));
            expect(dimensions.height).toBe('96px');
            expect(dimensions.overflow).toBe('auto');
            expect(dimensions.scroll).toBeGreaterThan(dimensions.client);
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        }
        if (framework !== 'bootstrap4') {
            const accessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(accessibility.violations).toEqual([]);
        }
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
    });

    test(`${framework}: reusable individual and bulk email actions`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/__browser/${framework}`);
        const row = page.locator(framework === 'bootstrap4' ? '#users_table tr' : '#lu-users tr').filter({ hasText: 'Alex Rivers' });
        await row.locator('.lu-email-toggle').click();
        await row.getByRole('button', { name: 'Send welcome email', exact: true }).click();
        await expect(page.getByRole('dialog')).toContainText('welcome');
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await row.locator('.lu-email-toggle').click();
        await row.getByRole('button', { name: 'Send password reset email', exact: true }).click();
        await expect(page.getByRole('dialog')).toContainText('60 minutes');
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await row.locator('[data-lu-select]').check();
        await page.locator('#lu-bulk-action').selectOption('message');
        await page.locator('#lu-bulk-submit').click();
        await expect(page.getByRole('dialog', { name: 'Send user an email' })).toBeVisible();
        await expect(page.locator('#lu-email-form [name="ids[]"]')).toHaveValue('2');
        await expect(page.locator('[data-lu-email-chips]')).toContainText('Alex Rivers');
        await page.getByRole('button', { name: 'Remove Alex Rivers', exact: true }).click();
        await expect(page.locator('#lu-email-form [name="ids[]"]')).toHaveCount(0);
        await expect(page.locator('[data-lu-email-recipient]')).toHaveText('No recipients selected');
        await expect(page.getByRole('dialog').getByRole('button', { name: 'Send email', exact: true })).toBeDisabled();
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await page.locator('#lu-bulk-submit').click();
        await expect(page.locator('#lu-email-form [name="ids[]"]')).toHaveValue('2');
        await expect(page.getByRole('dialog').getByRole('button', { name: 'Send email', exact: true })).toBeEnabled();
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await page.locator('#user_search_box').fill('user1@example.com');
        await page.locator('#user_search_box').press('Enter');
        const result = page.locator(framework === 'bootstrap4' ? '#search_results tr' : '#lu-results tr');
        await expect(result.locator('.lu-email-toggle')).toBeVisible();
        await expect(result.getByText('No logins', { exact: true })).toBeVisible();
        await result.locator('.lu-email-toggle').click();
        await result.getByRole('button', { name: 'Send user an email', exact: true }).click();
        await expect(page.locator('#lu-email-form [name="ids[]"]')).toHaveValue('2');
        await expect(page.locator('[data-lu-email-recipient]')).toContainText('Alex Rivers');
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await page.goto('/users/2');
        await expect(page.getByText('No logins', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Send user an email', exact: true })).toBeVisible();
        await page.goto('/users/2/edit');
        await expect(page.getByRole('button', { name: 'Send welcome email', exact: true })).toBeVisible();
        expect(errors).toEqual([]);
    });
}

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: card view grid, labels, and saved view choices`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        const body = page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users');
        await page.getByRole('button', { name: 'Card view', exact: true }).click();
        await expect(body.getByText('No logins', { exact: true }).first()).toBeVisible();
        expect(await body.locator('.lu-online').first().evaluate(element => parseFloat(getComputedStyle(element).fontSize))).toBeLessThanOrEqual(11);
        const currentUser = body.locator('tr').filter({ hasText: 'Morgan Hayes' });
        await expect(currentUser.getByRole('button', { name: /Delete/ })).toHaveCount(0);
        await expect(currentUser.locator('[data-lu-select]')).toHaveCount(0);
        await expect(currentUser.locator('[data-lu-selection-cell]')).toHaveCSS('position', 'absolute');
        for (const [width, columns] of [[390, 1], [768, 2], [1025, 3], [1100, 3], [1600, 4]]) {
            await page.setViewportSize({ width, height: 1000 });
            expect(await body.evaluate(element => getComputedStyle(element).gridTemplateColumns.split(' ').length)).toBe(columns);
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        }
        await setTheme(page, 'dark');
        const colors = await body.locator('tr').first().evaluate(element => ({
            card: getComputedStyle(element).backgroundColor,
            container: getComputedStyle(element.closest('.lu-panel, .card')).backgroundColor
        }));
        expect(colors.card).not.toBe(colors.container);
        if (framework !== 'bootstrap4') {
            const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(result.violations).toEqual([]);
        }
        if (framework !== 'bootstrap4') {
            for (const user of ['Alex Rivers', 'Morgan Hayes']) {
                const row = body.locator('tr').filter({ hasText: user });
                const actions = row.locator('.lu-button, .lu-email-toggle');
                const widths = await actions.evaluateAll(items => items.map(item => item.getBoundingClientRect().width));
                expect(Math.max(...widths) - Math.min(...widths)).toBeLessThan(1);
                for (const button of await actions.all()) {
                    expect(await button.evaluate(element => element.scrollWidth <= element.clientWidth)).toBe(true);
                }
            }
        }
        await page.setViewportSize({ width: 390, height: 1000 });
        const controls = page.locator('.lu-table-toolbar').locator(':scope > button, :scope > details > summary, :scope > .lu-mobile-sort');
        expect(await controls.evaluateAll(items => items.map(item => item.getAttribute('aria-label') || item.querySelector('label')?.textContent.trim()))).toEqual(['Table view', 'Card view', 'Columns', 'Filters', 'Sort', 'Select all', 'Deselect all']);
        const sizes = await controls.evaluateAll(items => items.map(item => ({ y: item.getBoundingClientRect().y, width: item.getBoundingClientRect().width, font: getComputedStyle(item).fontSize })));
        expect(Math.max(...sizes.map(item => item.y)) - Math.min(...sizes.map(item => item.y))).toBeLessThan(1);
        expect(Math.max(...sizes.map(item => item.width)) - Math.min(...sizes.map(item => item.width))).toBeLessThan(1);
        expect(sizes.every(item => item.font === '0px')).toBe(true);
        for (const button of await page.locator('.lu-button:visible').all()) {
            if (await button.evaluate(element => getComputedStyle(element).fontSize) !== '0px') continue;
            const box = await button.boundingBox();
            const icon = await button.locator('svg').boundingBox();
            expect(Math.abs(box.x + box.width / 2 - icon.x - icon.width / 2)).toBeLessThan(1);
            expect(Math.abs(box.y + box.height / 2 - icon.y - icon.height / 2)).toBeLessThan(1);
        }
        const accessibility = await new AxeBuilder({ page }).include('[data-lu-table]').include('.lu-table-toolbar').withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
        expect(accessibility.violations).toEqual([]);
        await page.setViewportSize({ width: 768, height: 1000 });
        for (const row of await body.locator('tr').all()) {
            const card = await row.boundingBox();
            for (const action of await row.locator(framework === 'bootstrap4' ? '.btn' : '.lu-button, .lu-email-toggle').all()) {
                const box = await action.boundingBox();
                expect(Math.abs(card.y + card.height - box.y - box.height - 17)).toBeLessThan(2);
            }
        }
        if (framework !== 'bootstrap4') {
            for (const row of await body.locator('tr').all()) {
                const card = await row.boundingBox();
                const actions = row.locator(framework === 'bootstrap4' ? '.btn' : '.lu-button, .lu-email-toggle');
                const first = await actions.first().boundingBox();
                const last = await actions.last().boundingBox();
                expect(Math.abs((first.x + last.x + last.width) / 2 - card.x - card.width / 2)).toBeLessThan(1);
            }
        }
        const avatarCell = body.locator('td:has(.lu-avatar)').first();
        if (framework === 'bootstrap5') {
            await expect(avatarCell).toHaveCSS('background-color', 'rgb(36, 88, 183)');
            expect(await avatarCell.evaluate(element => getComputedStyle(element).backgroundImage)).toContain('linear-gradient');
        }
        await expect(avatarCell).not.toContainText('Avatar');
        for (const field of await body.locator('.lu-card-last-field').all()) await expect(field).toHaveCSS('border-bottom-width', '0px');
        const label = body.locator('.lu-card-label').filter({ has: page.locator('svg') }).first();
        await expect(label).toBeVisible();
        const sort = page.getByLabel('Sort', { exact: true });
        expect(await sort.evaluate(element => parseFloat(getComputedStyle(element).paddingRight))).toBeGreaterThanOrEqual(30);
        await sort.selectOption({ label: 'Name (Descending)' });
        await expect(body.locator('tr').first()).toContainText('Morgan Hayes');
        await page.reload();
        await expect(page.getByRole('button', { name: 'Card view', exact: true })).toHaveAttribute('aria-pressed', 'true');
        await page.locator('#user_search_box').fill('user0@example.com');
        await page.locator('#user_search_box').press('Enter');
        const results = page.locator(framework === 'bootstrap4' ? '#search_results' : '#lu-results');
        await expect(results.locator('.lu-card-label').first()).toBeVisible();
        await expect(results.locator('.lu-login-details svg')).toHaveCount(4);
        await expect(results.locator('[data-lu-select]')).toHaveCount(0);
        await expect(results.getByRole('button', { name: /Delete/ })).toHaveCount(0);
        await page.getByRole('button', { name: 'Table view', exact: true }).click();
        await page.reload();
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-table-view', 'table');
        await expect(page.locator('.lu-mobile-filters')).toBeHidden();
        expect(await body.locator('tr').first().evaluate(row => getComputedStyle(row).display)).toBe('table-row');
        await page.getByRole('button', { name: 'Card view', exact: true }).click();
        await page.goto(`/__browser/${framework}?view-toggle=0`);
        await expect(page.getByRole('group', { name: 'User list view' })).toHaveCount(0);
        await expect(page.locator('#laravelusers')).toHaveAttribute('data-lu-table-view', 'table');
    });

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
        const directory = page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users');
        await expect(directory.locator('.lu-login-details').first()).toContainText('127.0.0.1');
        async function expectStackedDetails(details) {
            const positions = await details.locator(':scope > span').evaluateAll(items => items.map(item => item.getBoundingClientRect().top));
            expect(positions.length).toBeGreaterThan(1);
            expect(positions.every((top, index) => index === 0 || top > positions[index - 1])).toBe(true);
            expect(await details.evaluate(element => parseFloat(getComputedStyle(element).fontSize))).toBeLessThan(11);
        }
        await expectStackedDetails(directory.locator('.lu-login-details').first());
        await page.getByRole(framework === 'bootstrap4' ? 'textbox' : 'searchbox', { name: 'Search Users', exact: true }).fill('user0@example.com');
        await page.locator(framework === 'bootstrap4' ? '#search_users' : '#lu-search').locator('button[type="submit"]').click();
        const results = page.locator(framework === 'bootstrap4' ? '#search_results' : '#lu-results');
        await expect(results.locator('.lu-login-details').first()).toContainText('127.0.0.1');
        await expectStackedDetails(results.locator('.lu-login-details').first());
        await page.goto('/users/1');
        await expect(page.locator('.lu-profile-identity .lu-avatar')).toBeVisible();
        await expect(page.locator('.lu-profile-details dt svg')).toHaveCount(11);
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
        await page.locator('#password').fill('password123');
        await page.getByLabel('Confirm Password', { exact: true }).fill('mismatch');
        await page.getByRole('button', { name: 'Create New User' }).click();
        await expect(page.getByRole('alert')).toBeVisible();
        await expect(page.getByLabel('Username', { exact: true })).toHaveValue(name);
        await page.locator('#password').fill('password123');
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
        await expect(page.getByRole('heading', { level: 2, name: `${name}updated`, exact: true })).toBeVisible();

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
    await page.getByRole('button', { name: 'Create New User', exact: true }).click();
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
        const clear = framework === 'bootstrap4' ? page.locator('.clear-search') : page.getByRole('button', { name: 'Clear', exact: true });
        await expect(clear).toBeHidden();
        let requests = 0;
        await page.route('**/search-users', route => {
            requests++;
            return route.fulfill({ contentType: 'application/json', body: JSON.stringify([{ id: 2, name: 'Alex Rivers', email: 'alex@example.com' }]) });
        });
        await input.fill('Al');
        await expect(clear).toBeVisible();
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
        await expect(clear).toBeHidden();
        await input.fill('a');
        await input.fill('');
        await expect(clear).toBeHidden();
        await expect(page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users')).toBeVisible();
    });

    test(`${framework}: search delay can be changed or disabled`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?search-debounce=0&search-delay=100`);
        let requests = 0;
        await page.route('**/search-users', route => {
            requests++;
            return route.fulfill({ contentType: 'application/json', body: '[]' });
        });
        const input = page.locator('#user_search_box');
        await input.fill('Alex');
        await page.waitForTimeout(300);
        expect(requests).toBe(0);
        await input.press('Enter');
        await expect.poll(() => requests).toBe(1);
        await page.goto(`/__browser/${framework}?search-delay=100`);
        await input.fill('Alex');
        await expect.poll(() => requests).toBe(2);
        await page.waitForTimeout(200);
        expect(requests).toBe(2);
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
        const actions = row.locator(framework === 'bootstrap4' ? '.btn' : '.lu-button, .lu-email-toggle');
        const desktop = await actions.evaluateAll(buttons => buttons.map(button => button.getBoundingClientRect().y));
        expect(Math.max(...desktop) - Math.min(...desktop)).toBeLessThan(2);
        await page.setViewportSize({ width: 768, height: 1024 });
        for (const action of await actions.all()) {
            expect(await action.evaluate(button => getComputedStyle(button).fontSize)).toBe('0px');
            const buttonBox = await action.boundingBox();
            const iconBox = await action.locator(framework === 'bootstrap4' ? 'i' : 'svg').boundingBox();
            expect(buttonBox.width).toBeLessThanOrEqual(44);
            expect(Math.abs(buttonBox.x + buttonBox.width / 2 - iconBox.x - iconBox.width / 2)).toBeLessThan(1);
            expect(Math.abs(buttonBox.y + buttonBox.height / 2 - iconBox.y - iconBox.height / 2)).toBeLessThan(1);
        }
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
    test(`${framework}: icon-only actions and delete confirmation retain labels and cancel safely`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?icons-only=1`);
        const body = page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users');
        const row = body.locator('tr').filter({ hasText: 'Alex Rivers' });
        const buttons = row.locator(framework === 'bootstrap4' ? '.btn' : '.lu-button, .lu-email-toggle');
        for (const button of await buttons.all()) {
            await expect(button).toHaveAttribute('aria-label', /\S/);
            expect(await button.evaluate(element => getComputedStyle(element).fontSize)).toBe('0px');
            await expect(button.locator(framework === 'bootstrap4' ? 'i' : 'svg')).toBeVisible();
            expect(await button.getAttribute('title') || await button.getAttribute('data-original-title')).toBeTruthy();
        }
        await row.getByRole('button', { name: /Delete/ }).click();
        const modal = page.locator(framework === 'bootstrap4' ? '#confirmDelete' : '#lu-confirmation');
        await expect(modal).toBeVisible();
        const header = modal.locator(framework === 'bootstrap4' ? '.modal-header' : '.lu-card-heading');
        expect(await header.evaluate(element => getComputedStyle(element).backgroundColor)).toBe('rgb(180, 35, 50)');
        expect(await header.evaluate(element => getComputedStyle(element).color)).toBe('rgb(255, 255, 255)');
        const confirm = modal.locator(framework === 'bootstrap4' ? '#confirm' : '#lu-confirm-submit');
        await expect(confirm.locator('svg:visible')).toBeVisible();
        expect(await confirm.evaluate(element => getComputedStyle(element).backgroundColor)).toBe('rgb(180, 35, 50)');
        await modal.getByRole('button', { name: /Cancel/ }).last().click();
        await expect(modal).toBeHidden();
        await expect(row).toBeVisible();
        await page.goto(`/__browser/${framework}?icons-only=0`);
        expect(await buttons.first().evaluate(element => parseFloat(getComputedStyle(element).fontSize))).toBeGreaterThan(0);
    });

    test(`${framework}: readable local dates, persistent columns, and optional mobile entries`, async ({ page }) => {
        await page.emulateMedia({ colorScheme: 'light' });
        await page.goto(`/__browser/${framework}`);
        const body = page.locator(framework === 'bootstrap4' ? '#users_table' : '#lu-users');
        const time = body.locator('time').first();
        const utc = await time.getAttribute('datetime');
        const expected = await page.evaluate(value => new Intl.DateTimeFormat(document.documentElement.lang, { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)), utc);
        await expect(time).toHaveText(expected);
        expect(await time.evaluate(element => parseFloat(getComputedStyle(element).fontSize))).toBeLessThan(14);
        await expect(body).not.toContainText('Not recorded');
        const headers = await page.locator('[data-lu-table] thead tr').first().locator('th').allTextContents();
        expect(headers.findIndex(value => value.includes('Status'))).toBeLessThan(headers.findIndex(value => value.includes('Created')));
        await expect(page.locator('th[data-lu-avatar-label]')).toHaveAttribute('colspan', '2');
        await page.locator('.lu-columns summary').click();
        await page.locator('#user_search_box').click();
        await expect(page.locator('.lu-columns')).not.toHaveAttribute('open', '');
        await page.locator('.lu-columns summary').click();
        await page.locator('.lu-column-options').getByLabel('Avatar', { exact: true }).uncheck();
        await expect(body.locator('.lu-avatar').first()).toBeHidden();
        await expect(page.locator('th[data-lu-avatar-label]')).toHaveAttribute('colspan', '1');
        await page.locator('.lu-column-options').getByLabel('Avatar', { exact: true }).check();
        await expect(page.locator('th[data-lu-avatar-label]')).toHaveAttribute('colspan', '2');
        await page.locator('.lu-column-options').getByLabel('Email', { exact: true }).uncheck();
        await expect(body.locator('a[href="mailto:user1@example.com"]')).toBeHidden();
        await expect(page.getByRole('button', { name: 'Sort by Email', exact: true })).toBeHidden();
        await page.reload();
        await expect(body.locator('a[href="mailto:user1@example.com"]')).toBeHidden();
        await expect(page.getByRole('button', { name: 'Sort by Email', exact: true })).toBeHidden();
        const headerX = await page.locator('th[data-lu-label="Name"]').evaluate(element => element.getBoundingClientRect().x);
        const cellX = await body.locator('td[data-lu-column="Name"]').first().evaluate(element => element.getBoundingClientRect().x);
        expect(Math.abs(headerX - cellX)).toBeLessThan(1);
        await page.locator('.lu-columns summary').click();
        await page.locator('.lu-column-options').getByLabel('Email', { exact: true }).check();
        await page.setViewportSize({ width: 390, height: 844 });
        await page.getByRole('button', { name: 'Select all', exact: true }).click();
        await expect(page.getByRole('button', { name: 'Select all', exact: true })).toBeDisabled();
        await expect(page.locator('[data-lu-select-visible] [data-lu-checkmark]')).toBeVisible();
        await expect(page.locator('[data-lu-selected-count]')).toHaveText('1 selected');
        await page.getByRole('button', { name: 'Deselect all', exact: true }).click();
        await expect(page.getByRole('button', { name: 'Deselect all', exact: true })).toBeDisabled();
        await expect(page.locator('[data-lu-select-visible] [data-lu-checkmark]')).toBeHidden();
        await expect(page.getByRole('button', { name: 'Deselect all', exact: true })).toHaveCSS('cursor', 'not-allowed');
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
        await page.goto(`/__browser/${framework}?date-style=long`);
        const longDate = await page.evaluate(value => new Intl.DateTimeFormat(document.documentElement.lang, { dateStyle: 'long', timeStyle: 'short' }).format(new Date(value)), utc);
        await expect(time).toHaveText(longDate);
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

    test(`${framework}: deleted card actions fit, stay centered, and retain confirmation`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?soft-deletes=1&full-width=1&settings=1`);
        const name = `DeletedCard${Date.now()}`;
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        expect((await page.request.post('/users', { form: { _token: token, name, email: `${name}@example.com`, password: 'password123', password_confirmation: 'password123' } })).ok()).toBe(true);
        const users = await page.request.post('/search-users', { form: { _token: token, user_search_box: name } });
        const [{ id }] = await users.json();
        expect((await page.request.post(`/users/${id}`, { form: { _token: token, _method: 'DELETE' } })).ok()).toBe(true);
        await page.goto('/users/deleted');
        await page.getByRole('button', { name: 'Card view', exact: true }).click();
        const row = page.locator('[data-lu-view="deleted"] tbody tr').filter({ hasText: name });
        const actions = row.locator('.lu-actions').locator(':scope > form > button, :scope > a, :scope > details > summary');
        await expect(actions).toHaveCount(4);
        for (const width of [390, 768, 1161, 1280, 1440, 1600, 1920, 2400, 3200]) {
            await page.setViewportSize({ width, height: 1000 });
            const card = await row.boundingBox();
            const sizes = await actions.evaluateAll(items => items.map(item => {
                const box = item.getBoundingClientRect();
                const icon = item.querySelector('svg, i').getBoundingClientRect();
                return { x: box.x, y: box.y, width: box.width, height: box.height, iconX: icon.x, iconY: icon.y, iconWidth: icon.width, iconHeight: icon.height, fits: item.scrollWidth <= item.clientWidth, font: getComputedStyle(item).fontSize, title: item.getAttribute('title') || item.getAttribute('data-original-title') };
            }));
            expect(sizes.every(item => item.fits && item.title), `${width}px: ${JSON.stringify(sizes)}`).toBe(true);
            expect(Math.max(...sizes.map(item => item.y)) - Math.min(...sizes.map(item => item.y))).toBeLessThan(1);
            expect(Math.max(...sizes.map(item => item.width)) - Math.min(...sizes.map(item => item.width))).toBeLessThan(1);
            for (const size of sizes) {
                expect(size.x).toBeGreaterThan(card.x);
                expect(size.x + size.width).toBeLessThan(card.x + card.width);
                expect(Math.abs(card.y + card.height - size.y - size.height - 17)).toBeLessThan(2);
                if (size.font === '0px') {
                    expect(Math.abs(size.x + size.width / 2 - size.iconX - size.iconWidth / 2)).toBeLessThan(1);
                    expect(Math.abs(size.y + size.height / 2 - size.iconY - size.iconHeight / 2)).toBeLessThan(1);
                }
            }
            const last = sizes[sizes.length - 1];
            expect(Math.abs((sizes[0].x + last.x + last.width) / 2 - card.x - card.width / 2)).toBeLessThan(1);
            if (width === 1161) expect(sizes.every(item => item.font === '0px')).toBe(true);
            if (width === 3200) expect(sizes.every(item => parseFloat(item.font) > 0)).toBe(true);
        }
        await row.getByRole('button', { name: 'Permanently delete', exact: true }).click();
        const modal = page.locator(framework === 'bootstrap4' ? '#confirmDelete' : '#lu-confirmation');
        await expect(modal).toBeVisible();
        await modal.getByRole('button', { name: /Cancel/ }).last().click();
        await expect(modal).toBeHidden();
        await expect(row).toBeVisible();
        expect((await page.request.post(`/users/${id}/restore`, { form: { _token: token } })).ok()).toBe(true);
    });

    test(`${framework}: deleted-account email presets are editable and preview the selected link`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?soft-deletes=1&settings=1`);
        const cog = page.locator('.lu-settings-button');
        const dimensions = await cog.boundingBox();
        expect(dimensions.width).toBe(dimensions.height);
        await page.goto('/users/deleted');
        const row = page.locator('[data-lu-view="deleted"] tbody tr').filter({ has: page.locator('.lu-email-menu') }).first();
        const modal = page.locator('#lu-email-dialog');
        for (const [preset, title, subject, link] of [
            ['restore', 'Send restore account email', 'Restore your Laravel Users account', 'Restore my account'],
            ['force_delete', 'Send permanent deletion email', 'Permanently delete your Laravel Users account', 'Permanently delete my account']
        ]) {
            await row.locator('.lu-email-toggle').click();
            await row.getByRole('button', { name: title, exact: true }).click();
            await expect(modal).toBeVisible();
            await expect(modal.locator('[name="subject"]')).toHaveValue(subject);
            await expect(modal.locator(`[type="checkbox"][name="include_${preset}"]`)).toBeChecked();
            const other = preset === 'restore' ? 'force_delete' : 'restore';
            await expect(modal.locator(`[type="checkbox"][name="include_${other}"]`)).not.toBeChecked();
            await modal.locator('[name="message"]').fill('An updated account notice.');
            await modal.getByRole('button', { name: 'Preview email', exact: true }).click();
            const preview = modal.frameLocator('[data-lu-email-frame]');
            await expect(preview.getByText('An updated account notice.', { exact: true })).toBeVisible();
            await expect(preview.getByRole('link', { name: link, exact: true })).toHaveAttribute('href', `#${preset}`);
            await modal.getByRole('button', { name: 'Back to editing', exact: true }).click();
            await expect(modal.locator('[name="message"]')).toHaveValue('An updated account notice.');
            await modal.getByRole('button', { name: 'Close', exact: true }).click();
            await expect(modal).toBeHidden();
        }
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
            await expect(page.locator('#lu-bulk')).toBeHidden();
            await page.getByRole('button', { name: 'Select all', exact: true }).click();
            await expect(page.locator('#lu-bulk')).toBeVisible();
            await page.getByRole('button', { name: 'Deselect all', exact: true }).click();
            await expect(page.locator('#lu-bulk')).toBeHidden();
            await page.getByRole('button', { name: 'Select all', exact: true }).click();
            await page.locator('#lu-bulk-action').selectOption(action);
            await expect(page.locator('[data-lu-selected-count]')).toHaveText('2 selected');
            await page.locator('#lu-bulk-submit').click();
            const confirmed = page.waitForResponse(response => response.url().endsWith('/users/bulk') && response.request().method() === 'POST');
            if (framework === 'bootstrap4') await page.locator('#confirmDelete #confirm').click();
            else await page.locator('#lu-confirm-submit').click();
            expect((await confirmed).status()).toBe(302);
        }
        await search();
        await apply('delete');
        await expect(page).toHaveURL('/users');
        await expect(page.getByRole('link', { name: 'Create New User', exact: true })).toBeVisible();
        await page.getByRole('link', { name: 'Show Deleted Users', exact: true }).click();
        await expect(page).toHaveURL('/users/deleted');
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

for (const framework of ['bootstrap4', 'bootstrap5', 'tailwind']) {
    test(`${framework}: email previews keep edits and never send mail`, async ({ page }) => {
        await page.goto(`/__browser/${framework}`);
        await page.goto('/users/2');
        await page.getByRole('button', { name: 'Send user an email', exact: true }).click();
        await page.getByLabel('Subject', { exact: true }).fill('Preview subject');
        await page.getByLabel('Message', { exact: true }).fill('Please review your account.');
        const response = page.waitForResponse(response => response.url().endsWith('/users/email/preview') && response.request().method() === 'POST');
        await page.getByRole('button', { name: 'Preview email', exact: true }).click();
        expect((await response).status()).toBe(200);
        await expect(page.getByRole('button', { name: 'Back to editing', exact: true })).toBeVisible();
        await expect(page.frameLocator('[data-lu-email-frame]').getByText('Please review your account.', { exact: true })).toBeVisible();
        await expect(page.frameLocator('[data-lu-email-frame]').locator('table.wrapper')).toBeVisible();
        await expect(page.locator('[data-lu-email-frame]')).toHaveAttribute('sandbox', '');
        await page.getByRole('button', { name: 'Back to editing', exact: true }).click();
        await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('Preview subject');
        await expect(page.getByLabel('Message', { exact: true })).toHaveValue('Please review your account.');
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await page.getByRole('button', { name: 'Send user an email', exact: true }).click();
        await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('');
        await expect(page.getByLabel('Message', { exact: true })).toHaveValue('');
        await page.getByRole('dialog').getByRole('button', { name: 'Close', exact: true }).click();
        await page.getByRole('button', { name: 'Send password reset email', exact: true }).click();
        await page.locator('#lu-reset-unit').selectOption('hours');
        await page.locator('#lu-reset-duration').fill('2');
        await expect(page.getByRole('dialog')).toContainText('120 minutes');
        await page.getByRole('button', { name: 'Preview email', exact: true }).click();
        await expect(page.frameLocator('[data-lu-email-frame]').getByText(/120 minutes/)).toBeVisible();
        await page.getByRole('button', { name: 'Back to editing', exact: true }).click();
        await expect(page.locator('#lu-reset-duration')).toHaveValue('2');
        await expect(page.locator('#lu-reset-unit')).toHaveValue('hours');
        await page.getByLabel('Message', { exact: true }).fill('Choose a new password when you are ready.');
        await page.locator('[data-lu-reset-duration] input[type="checkbox"]').check();
        await expect(page.locator('#lu-reset-duration')).toBeHidden();
        await expect(page.locator('#lu-reset-duration')).toBeDisabled();
        await page.getByRole('button', { name: 'Preview email', exact: true }).click();
        await expect(page.frameLocator('[data-lu-email-frame]').getByText('Choose a new password when you are ready.')).toBeVisible();
        await expect(page.frameLocator('[data-lu-email-frame]').getByText(/does not expire/)).toBeVisible();
        await page.getByRole('button', { name: 'Back to editing', exact: true }).click();
        await page.locator('[data-lu-reset-duration] input[type="checkbox"]').uncheck();
        await expect(page.locator('#lu-reset-duration')).toHaveValue('2');
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await page.getByRole('button', { name: 'Send password reset email', exact: true }).click();
        await expect(page.locator('#lu-reset-unit')).toHaveValue('minutes');
        await expect(page.locator('#lu-reset-duration')).toHaveValue('60');
        await expect(page.locator('[data-lu-reset-duration] input[type="checkbox"]')).not.toBeChecked();
        await page.locator('#lu-reset-unit').selectOption('hours');
        await expect(page.locator('#lu-reset-duration')).toHaveValue('1');
        await page.getByRole('dialog').press('Escape');
        await page.getByRole('button', { name: 'Send welcome email', exact: true }).click();
        await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('Welcome to Laravel Users');
        await page.getByLabel('Message', { exact: true }).fill('Welcome back to your account.');
        await page.getByLabel('Use greeting', { exact: true }).uncheck();
        await page.getByRole('button', { name: 'Preview email', exact: true }).click();
        await expect(page.frameLocator('[data-lu-email-frame]').getByText('Welcome back to your account.')).toBeVisible();
        await expect(page.frameLocator('[data-lu-email-frame]').getByRole('link', { name: 'Sign in', exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Back to editing', exact: true }).click();
        await expect(page.getByLabel('Message', { exact: true })).toHaveValue('Welcome back to your account.');
        await expect(page.getByLabel('Use greeting', { exact: true })).not.toBeChecked();
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await page.getByRole('button', { name: 'Send welcome email', exact: true }).click();
        await expect(page.getByLabel('Message', { exact: true })).toHaveValue('Your account at Laravel Users is ready.');
        await expect(page.getByLabel('Use greeting', { exact: true })).toBeChecked();
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
    });

    test(`${framework}: deleted-user emails offer expiring account links and preview`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?soft-deletes=1`);
        const name = `DeletedEmail${Date.now()}`;
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        const created = await page.request.post('/users', { form: { _token: token, name, email: `${name}@example.com`, password: 'password123', password_confirmation: 'password123' } });
        expect(created.ok()).toBe(true);
        await page.locator('#user_search_box').fill(name);
        await page.locator('#user_search_box').press('Enter');
        const row = page.locator(framework === 'bootstrap4' ? '#search_results tr' : '#lu-results tr').filter({ hasText: name });
        const editUrl = await row.locator('a[href$="/edit"]').getAttribute('href');
        const userUrl = editUrl.replace(/\/edit$/, '');
        const removed = await page.request.post(userUrl, { form: { _token: token, _method: 'DELETE' } });
        expect(removed.ok()).toBe(true);
        await page.goto('/users/deleted');
        const deletedRow = page.locator('[data-lu-table] tbody tr').filter({ hasText: name });
        await deletedRow.locator('.lu-email-toggle').click();
        await deletedRow.getByRole('button', { name: 'Send user an email', exact: true }).click();
        await page.getByLabel('Subject', { exact: true }).fill('Deleted account options');
        await page.getByLabel('Message', { exact: true }).fill('Choose whether you want to restore your account.');
        await page.getByLabel('Include a restore account button', { exact: true }).check();
        await page.getByLabel('Include a permanently delete account button', { exact: true }).check();
        await page.locator('#lu-account-unit').selectOption('days');
        await page.locator('#lu-account-duration').fill('2');
        await page.getByRole('button', { name: 'Preview email', exact: true }).click();
        const frame = page.frameLocator('[data-lu-email-frame]');
        await expect(frame.getByRole('link', { name: 'Restore my account', exact: true })).toBeVisible();
        await expect(frame.getByRole('link', { name: 'Permanently delete my account', exact: true })).toBeVisible();
        await expect(frame.getByText(/2880 minutes/)).toBeVisible();
        await page.getByRole('button', { name: 'Back to editing', exact: true }).click();
        await expect(page.locator('#lu-account-duration')).toHaveValue('2');
        await expect(page.getByLabel('Include a restore account button', { exact: true })).toBeChecked();
        await page.locator('[data-lu-account-duration] input[type="checkbox"]').check();
        await expect(page.locator('#lu-account-duration')).toBeHidden();
        await page.getByRole('button', { name: 'Preview email', exact: true }).click();
        await expect(frame.getByText(/do not expire/)).toBeVisible();
        await page.getByRole('button', { name: 'Back to editing', exact: true }).click();
        await page.locator('[data-lu-account-duration] input[type="checkbox"]').uncheck();
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        await page.getByRole('checkbox', { name: `Select ${name}`, exact: true }).check();
        await page.locator('#lu-bulk-action').selectOption('message');
        await page.locator('#lu-bulk-submit').click();
        await expect(page.locator('#lu-email-form [name="deleted"]')).toHaveValue('1');
        await expect(page.getByRole('button', { name: `Remove ${name}`, exact: true })).toBeVisible();
        await expect(page.getByLabel('Include a restore account button', { exact: true })).toBeVisible();
        await expect(page.getByLabel('Include a restore account button', { exact: true })).not.toBeChecked();
        await expect(page.getByLabel('Include a permanently delete account button', { exact: true })).not.toBeChecked();
        await page.getByLabel('Include a restore account button', { exact: true }).check();
        await expect(page.locator('#lu-account-unit')).toHaveValue('minutes');
        await expect(page.locator('#lu-account-duration')).toHaveValue('60');
        await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('');
        await expect(page.getByLabel('Message', { exact: true })).toHaveValue('');
        await page.setViewportSize({ width: 390, height: 844 });
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        if (framework !== 'bootstrap4') {
            const accessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(accessibility.violations).toEqual([]);
        }
        await page.getByRole('dialog').getByRole('button', { name: 'Cancel', exact: true }).click();
        const purged = await page.request.post(`${userUrl}/force`, { form: { _token: token, _method: 'DELETE' } });
        expect(purged.ok()).toBe(true);
    });
}

async function openEditSection(page, name) {
    const tabs = page.getByRole('tablist', {name: 'Edit user sections', exact: true});
    if (await tabs.count()) await tabs.getByRole('tab', {name, exact: true}).click();
}

for (const framework of ['bootstrap5', 'tailwind']) {
    test(`${framework}: edit tabs fit all widths and retain fields across sections`, async ({page}) => {
        await page.goto(`/__browser/${framework}?appearance=1&accounts=1&avatar-preferences=1`);
        await page.goto('/users/1/edit');
        const tabs = page.getByRole('tablist', {name: 'Edit user sections', exact: true});
        await expect(tabs.getByRole('tab', {name: 'Profile', exact: true})).toHaveAttribute('aria-selected', 'true');
        const username = await page.locator('#name').inputValue();
        for (const theme of ['light', 'dark']) {
            await setTheme(page, theme);
            for (const width of [320, 390, 760, 1024, 1440]) {
                await page.setViewportSize({width, height: 900});
                for (const name of ['Profile', 'Password', 'Appearance', 'Account access']) {
                    await openEditSection(page, name);
                    await expect(page.getByRole('tabpanel', {name, exact: true})).toBeVisible();
                    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
                    for (const input of await page.locator('[role="tabpanel"]:visible input:visible, [role="tabpanel"]:visible select').all()) {
                        const box = await input.boundingBox();
                        expect(box.x + box.width).toBeLessThanOrEqual(width);
                    }
                }
            }
            await openEditSection(page, 'Profile');
            await expect(page.locator('#name')).toHaveValue(username);
            const result = await new AxeBuilder({page}).include('.lu-edit-card').withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();
            expect(result.violations).toEqual([]);
        }
        await page.setViewportSize({width: 1280, height: 900});
        await tabs.getByRole('tab', {name:'Profile',exact:true}).focus();
        await page.keyboard.press('ArrowRight');
        await expect(tabs.getByRole('tab', {name:'Password',exact:true})).toBeFocused();
        await page.keyboard.press('Home');
        await expect(tabs.getByRole('tab', {name:'Profile',exact:true})).toBeFocused();
        await page.locator('#name').fill('');
        await openEditSection(page, 'Password');
        await page.getByRole('button', {name:'Save changes',exact:true}).click();
        await expect(page.getByRole('tabpanel', {name:'Profile',exact:true})).toBeVisible();
        await expect(page.locator('#name')).toBeFocused();
    });
}
