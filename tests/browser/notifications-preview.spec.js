const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const path = require('node:path');

const toastInstalled = execFileSync('php', ['-r', 'require $argv[1]; echo class_exists(Jeremykenedy\\LaravelToast\\Providers\\ToastServiceProvider::class) ? "1" : "0";', path.join(__dirname, '../../vendor/autoload.php')], { encoding: 'utf8' }) === '1';
const defaults = {
    position: 'bottom-left', dir: 'ltr', duration: '5000', max_visible: '5', opacity: '1',
    enter_animation: 'none', enter_duration: '0.1', exit_animation: 'none', exit_duration: '0.1',
    progress_direction: 'rtl', progress_position: 'top', auto_dismiss: '0', pause_on_hover: '1',
    stack: '1', show_icons: '1', show_border: '1', show_close: '1', show_progress: '1', convert_flash: '0',
};
const previewAlert = page => page.locator('.lu-notifications [data-lu-notification-preview]');
const previewToasts = page => page.locator('[data-lu-preview-toast-stack] [data-lu-toast]');
const previewButton = page => page.locator('[data-lu-preview-notification]');

function storedSettings() {
    const script = 'require $argv[1]; $table = (new jeremykenedy\\laravelusers\\Models\\UserSetting())->getTable(); $database = new PDO("sqlite:".$argv[2]); echo json_encode($database->query("SELECT value, updated_at FROM ".$table." WHERE key = \'global\'")->fetch(PDO::FETCH_ASSOC));';
    return execFileSync('php', ['-r', script, path.join(__dirname, '../../vendor/autoload.php'), path.join(__dirname, 'runtime/database.sqlite')], { encoding: 'utf8' });
}

async function openSettings(page, framework) {
    await page.goto('/__browser/' + framework + '?settings=1&published-assets=1');
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const form = {
        _token: token, _method: 'PUT', avatar_source: 'initials', profile_color: '#2458b7', edit_color: '#705000',
        notifications_driver: toastInstalled ? 'both' : 'alert', notifications_dismissible: '1',
    };
    if (toastInstalled) for (const [key, value] of Object.entries(defaults)) form['toast[' + key + ']'] = value;
    const response = await page.request.post('/users/settings', { form, maxRedirects: 0 });
    expect(response.status()).toBe(302);
    await page.goto('/users/settings');
    await expect(page.locator('.lu-notifications')).toContainText('User settings saved.');
    await page.getByRole('tab', { name: 'Notifications', exact: true }).click();
    await expect(previewButton(page)).toBeVisible();
    expect(await page.locator('#settings-notifications').count()).toBe(toastInstalled ? 1 : 0);
    const stored = storedSettings();
    const writes = [];
    page.on('request', request => {
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(request.method()) && new URL(request.url()).pathname === '/users/settings') writes.push(request.url());
    });
    return { stored, writes };
}

async function option(page, key, value) {
    const control = page.locator('[name="toast[' + key + ']"]:not([type="hidden"])');
    if (typeof value === 'boolean') await control.setChecked(value);
    else if (await control.evaluate(node => node.tagName === 'SELECT')) await control.selectOption(String(value));
    else await control.fill(String(value));
}

async function options(page, values) {
    for (const [key, value] of Object.entries(values)) await option(page, key, value);
}

async function unchanged(page, baseline) {
    expect(baseline.writes).toEqual([]);
    expect(storedSettings()).toBe(baseline.stored);
    await page.reload();
    await page.getByRole('tab', { name: 'Notifications', exact: true }).click();
    await expect(page.locator('[name="notifications_dismissible"][type="checkbox"]')).toBeChecked();
    if (toastInstalled) {
        await expect(page.locator('#settings-notifications')).toHaveValue('both');
        for (const key of ['position', 'dir', 'duration', 'max_visible', 'opacity', 'enter_animation', 'exit_animation']) {
            await expect(page.locator('[name="toast[' + key + ']"]:not([type="hidden"])')).toHaveValue(defaults[key]);
        }
        await expect(page.locator('[name="toast[auto_dismiss]"][type="checkbox"]')).not.toBeChecked();
    }
    await expect(previewAlert(page)).toHaveCount(0);
    await expect(previewToasts(page)).toHaveCount(0);
    expect(storedSettings()).toBe(baseline.stored);
    expect(baseline.writes).toEqual([]);
}

for (const framework of ['bootstrap4', 'bootstrap5']) {
    test(framework + ': preview and dismissal preserve scroll position and settings layout', async ({ page }) => {
        const baseline = await openSettings(page, framework);
        if (toastInstalled) await option(page, 'position', 'top-right');
        for (const width of [390, 1440]) {
            await page.setViewportSize({ width, height: 600 });
            for (const driver of toastInstalled ? ['alert', 'both', 'toast'] : ['alert']) {
                if (toastInstalled) await page.locator('#settings-notifications').selectOption(driver);
                await previewButton(page).scrollIntoViewIfNeeded();
                await previewButton(page).evaluate(button => button.scrollIntoView({ block: 'center', behavior: 'instant' }));
                await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
                const measure = () => page.evaluate(() => {
                    const button = document.querySelector('[data-lu-preview-notification]').getBoundingClientRect();
                    const card = document.querySelector('.lu-settings-panel, .container > .card').getBoundingClientRect();
                    return { scrollX, scrollY, buttonY: button.y, cardY: card.y, cardHeight: card.height };
                });
                const before = await measure();
                await previewButton(page).click();
                await expect(previewAlert(page)).toHaveCount(driver === 'toast' ? 0 : 1);
                expect(await measure(), `${width}px, ${driver}, preview`).toEqual(before);
                await previewButton(page).click();
                expect(await measure(), `${width}px, ${driver}, repeated preview`).toEqual(before);
                if (driver !== 'toast') {
                    await previewAlert(page).getByRole('button', { name: 'Close', exact: true }).click();
                    await expect(previewAlert(page)).toHaveCount(0);
                    expect(await measure(), `${width}px, ${driver}, dismissal`).toEqual(before);
                }
            }
        }
        await unchanged(page, baseline);
    });

    test(framework + ': notification preview works with Toast absent and keeps unsaved alert choices local', async ({ page }) => {
        test.skip(toastInstalled, 'Requires the core install without the optional Toast package.');
        const baseline = await openSettings(page, framework);
        await expect(page.locator('[data-lu-toast-settings]')).toHaveCount(0);
        await expect(page.locator('[data-lu-preview-toast-template]')).toHaveCount(0);
        const dismiss = page.locator('[name="notifications_dismissible"][type="checkbox"]');
        for (const width of [390, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            for (const dismissible of [false, true]) {
                await dismiss.setChecked(dismissible);
                await previewButton(page).click();
                await expect(previewAlert(page)).toHaveCount(1);
                await expect(previewAlert(page)).toContainText('This is a preview notification.');
                const alert = await previewAlert(page).boundingBox();
                const card = await page.locator(framework === 'bootstrap4' ? '.container > .card' : '.lu-settings-panel').evaluate((element, framework) => {
                    const box = element.getBoundingClientRect();
                    const style = getComputedStyle(element);
                    const left = framework === 'bootstrap4' ? parseFloat(style.borderLeftWidth) : 0;
                    const right = framework === 'bootstrap4' ? parseFloat(style.borderRightWidth) : 0;
                    return { x: box.x + left, width: box.width - left - right };
                }, framework);
                expect(alert.x).toBeCloseTo(card.x, 1);
                expect(alert.width).toBeCloseTo(card.width, 1);
                const close = previewAlert(page).getByRole('button', { name: 'Close', exact: true });
                expect(await close.count()).toBe(dismissible ? 1 : 0);
                if (dismissible) { await close.click(); await expect(previewAlert(page)).toHaveCount(0); }
                await expect(previewToasts(page)).toHaveCount(0);
            }
        }
        await unchanged(page, baseline);
    });

    test(framework + ': denied notification settings disable preview and reject forged changes', async ({ page }) => {
        const baseline = await openSettings(page, framework);
        await page.goto('/__browser/' + framework + '?settings=1&published-assets=1&deny-notifications=1');
        await page.goto('/users/settings');
        await page.getByRole('tab', { name: 'Notifications', exact: true }).click();
        await expect(page.locator('.lu-settings-notifications')).toHaveAttribute('disabled', '');
        await expect(previewButton(page)).toBeDisabled();
        await previewButton(page).dispatchEvent('click');
        await expect(previewAlert(page)).toHaveCount(0);
        await expect(previewToasts(page)).toHaveCount(0);
        expect(baseline.writes).toEqual([]);
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        const response = await page.request.post('/users/settings', {
            form: { _token: token, _method: 'PUT', profile_color: '#2458b7', edit_color: '#705000', notifications_dismissible: '0' },
            headers: { Accept: 'application/json' },
        });
        expect(response.status()).toBe(422);
        expect((await response.json()).errors).toHaveProperty('notifications_dismissible');
        expect(storedSettings()).toBe(baseline.stored);
        await page.reload();
        await page.getByRole('tab', { name: 'Notifications', exact: true }).click();
        await expect(page.locator('[name="notifications_dismissible"][type="checkbox"]')).toBeChecked();
        await expect(previewButton(page)).toBeDisabled();
    });

    test(framework + ': saved Toast and alert dismiss on their first clicks after a real settings save', async ({ page }) => {
        test.skip(!toastInstalled, 'Requires the optional Toast package installed in this isolated worktree.');
        await openSettings(page, framework);
        await option(page, 'convert_flash', true);
        const response = page.waitForResponse(response => new URL(response.url()).pathname === '/users/settings' && response.request().method() === 'POST');
        await page.getByRole('button', { name: 'Save settings', exact: true }).click();
        expect((await response).status()).toBe(302);
        const toast = page.locator('[data-lu-toast]:not([data-lu-notification-preview])');
        const alert = page.locator('.lu-notifications .lu-flash');
        await expect(toast).toContainText('User settings saved.');
        await toast.getByRole('button', { name: 'Close', exact: true }).click();
        await expect(toast).toHaveCount(0);
        await expect(alert).toContainText('User settings saved.');
        await alert.getByRole('button', { name: 'Close', exact: true }).click();
        await expect(alert).toHaveCount(0);
    });

    test(framework + ': installed Toast preview uses unsaved visual controls without saving them', async ({ page }) => {
        test.skip(!toastInstalled, 'Requires the optional Toast package installed in this isolated worktree.');
        const baseline = await openSettings(page, framework);
        await page.locator('#settings-notifications').selectOption('toast');
        await options(page, { position: 'top-right', dir: 'rtl', opacity: 0.65, enter_animation: 'fade', enter_duration: 0.1, stack: false, show_icons: false, show_border: false, show_close: false });
        await previewButton(page).click();
        const toast = previewToasts(page);
        await expect(toast).toHaveCount(1);
        await expect(toast).toHaveCSS('opacity', '0.65');
        await expect(toast).toHaveAttribute('dir', 'rtl');
        await expect(toast).toHaveCSS('border-top-width', '0px');
        await expect(toast.locator('.lu-toast-icon, [data-lu-dismiss-toast], .lu-toast-progress')).toHaveCount(0);
        await expect(previewAlert(page)).toHaveCount(0);
        await options(page, { auto_dismiss: true, duration: 3000, show_icons: true, show_border: true, show_close: true, show_progress: true, progress_position: 'bottom', progress_direction: 'rtl' });
        await previewButton(page).click();
        await expect(toast).toHaveCSS('border-top-width', '1px');
        await expect(toast.locator('.lu-toast-icon svg[data-lu-icon="check"]')).toBeVisible();
        await expect(toast.getByRole('button', { name: 'Close', exact: true })).toBeVisible();
        await expect(toast.locator('.lu-toast-progress')).toHaveAttribute('data-position', 'bottom');
        await expect(toast.locator('.lu-toast-progress')).toHaveCSS('order', '1');
        await expect(toast.locator('.lu-toast-progress')).toHaveAttribute('data-direction', 'rtl');
        await expect.poll(() => toast.locator('[data-lu-toast-progress]').evaluate(bar => bar.getBoundingClientRect().width < bar.parentElement.getBoundingClientRect().width)).toBe(true);
        for (const direction of ['ltr', 'rtl']) {
            for (const position of ['top', 'bottom']) {
                await options(page, { dir: direction, progress_direction: direction, progress_position: position });
                await previewButton(page).click();
                const progress = toast.locator('.lu-toast-progress');
                await expect(toast).toHaveAttribute('dir', direction);
                await expect(progress).toHaveAttribute('data-direction', direction);
                await expect(progress).toHaveAttribute('data-position', position);
                await expect(progress).toHaveCSS('order', position === 'bottom' ? '1' : '0');
                await expect.poll(() => progress.locator('span').evaluate(bar => {
                    const fill = bar.getBoundingClientRect();
                    const track = bar.parentElement.getBoundingClientRect();
                    if (fill.width >= track.width) return false;
                    return Math.abs(bar.parentElement.dataset.direction === 'rtl' ? fill.right - track.right : fill.left - track.left) < 1;
                })).toBe(true);
            }
        }
        await options(page, { show_progress: false, exit_animation: 'fade', exit_duration: 0.3 });
        await previewButton(page).click();
        await expect(toast.locator('.lu-toast-progress')).toHaveCount(0);
        await toast.getByRole('button', { name: 'Close', exact: true }).click();
        await expect(toast).toHaveCSS('animation-name', 'toast-fade');
        await expect(toast).toHaveCount(0);
        await unchanged(page, baseline);
    });

    test(framework + ': Toast preview positions fit mobile and desktop in both themes and respect reduced motion', async ({ page }) => {
        test.skip(!toastInstalled, 'Requires the optional Toast package installed in this isolated worktree.');
        const baseline = await openSettings(page, framework);
        await page.locator('#settings-notifications').selectOption('toast');
        await options(page, { stack: false, opacity: 0.65, enter_animation: 'fade', enter_duration: 0.1 });
        for (const theme of ['light', 'dark']) {
            await page.evaluate(theme => { localStorage.setItem('laravelusers-theme', theme); document.getElementById('laravelusers').dataset.luTheme = theme; }, theme);
            for (const width of [390, 1440]) {
                await page.setViewportSize({ width, height: 1000 });
                for (const position of ['top-right', 'top-left', 'top-center', 'bottom-right', 'bottom-left', 'bottom-center']) {
                    await option(page, 'position', position);
                    await previewButton(page).click();
                    await expect(previewToasts(page)).toHaveCount(1);
                    await expect(previewToasts(page)).toHaveCSS('opacity', '0.65');
                    const box = await page.locator('[data-lu-preview-toast-stack][data-lu-toast-position="' + position + '"]').boundingBox();
                    expect(box.x).toBeGreaterThanOrEqual(0);
                    expect(box.x + box.width).toBeLessThanOrEqual(width);
                    if (position.endsWith('-left')) expect(box.x).toBeCloseTo(12, 1);
                    if (position.endsWith('-right')) expect(width - box.x - box.width).toBeCloseTo(12, 1);
                    if (position.endsWith('-center')) expect(box.x + box.width / 2).toBeCloseTo(width / 2, 1);
                    if (position.startsWith('top-')) expect(box.y).toBeCloseTo(12, 1);
                    else expect(1000 - box.y - box.height).toBeCloseTo(12, 1);
                    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
                }
            }
        }
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await option(page, 'enter_duration', 0.5);
        await previewButton(page).click();
        await expect(previewToasts(page)).toHaveCSS('animation-name', 'none');
        await expect(previewToasts(page)).toHaveCSS('opacity', '0.65');
        await unchanged(page, baseline);
    });

    test(framework + ': Toast previews honor stack limits and alert toast and both switches', async ({ page }) => {
        test.skip(!toastInstalled, 'Requires the optional Toast package installed in this isolated worktree.');
        const baseline = await openSettings(page, framework);
        await options(page, { position: 'top-right', stack: true, max_visible: 2 });
        for (let i = 0; i < 3; i++) await previewButton(page).click();
        await expect(previewToasts(page)).toHaveCount(2);
        await expect(previewAlert(page)).toHaveCount(1);
        await previewAlert(page).getByRole('button', { name: 'Close', exact: true }).click();
        await expect(previewAlert(page)).toHaveCount(0);
        await previewToasts(page).first().getByRole('button', { name: 'Close', exact: true }).click();
        await expect(previewToasts(page)).toHaveCount(1);
        await option(page, 'stack', false);
        for (let i = 0; i < 3; i++) await previewButton(page).click();
        await expect(previewToasts(page)).toHaveCount(1);
        await options(page, { stack: true, max_visible: 0 });
        for (let i = 0; i < 3; i++) await previewButton(page).click();
        await expect(previewToasts(page)).toHaveCount(4);
        await page.locator('#settings-notifications').selectOption('toast');
        await previewButton(page).click();
        await expect(previewAlert(page)).toHaveCount(0);
        await expect(previewToasts(page)).toHaveCount(5);
        await page.locator('#settings-notifications').selectOption('alert');
        await expect(page.locator('[data-lu-toast-settings]')).toBeHidden();
        await previewButton(page).click();
        await expect(previewToasts(page)).toHaveCount(0);
        await expect(previewAlert(page)).toHaveCount(1);
        await page.locator('[name="notifications_dismissible"][type="checkbox"]').uncheck();
        await previewButton(page).click();
        await expect(previewAlert(page).getByRole('button', { name: 'Close', exact: true })).toHaveCount(0);
        await unchanged(page, baseline);
    });

    test(framework + ': Toast preview timers pause on hover and keyboard focus and allow immediate dismissal', async ({ page }) => {
        test.skip(!toastInstalled, 'Requires the optional Toast package installed in this isolated worktree.');
        const baseline = await openSettings(page, framework);
        await page.locator('#settings-notifications').selectOption('toast');
        await options(page, { stack: false, auto_dismiss: true, duration: 800, pause_on_hover: true });
        await previewButton(page).click();
        await page.mouse.move(1, 1);
        await expect(previewToasts(page)).toHaveCount(0, { timeout: 3000 });
        await previewButton(page).click();
        await previewToasts(page).hover();
        await page.waitForTimeout(1100);
        await expect(previewToasts(page)).toHaveCount(1);
        await page.mouse.move(1, 1);
        await expect(previewToasts(page)).toHaveCount(0, { timeout: 3000 });
        await previewButton(page).click();
        await previewToasts(page).getByRole('button', { name: 'Close', exact: true }).focus();
        await page.mouse.move(1, 1);
        await page.waitForTimeout(1100);
        await expect(previewToasts(page)).toHaveCount(1);
        await previewButton(page).focus();
        await expect(previewToasts(page)).toHaveCount(0, { timeout: 3000 });
        await option(page, 'pause_on_hover', false);
        await previewButton(page).click();
        await previewToasts(page).hover();
        await expect(previewToasts(page)).toHaveCount(0, { timeout: 3000 });
        await options(page, { duration: 0, pause_on_hover: true });
        await previewButton(page).click();
        await expect(previewToasts(page).locator('.lu-toast-progress')).toHaveCount(0);
        await page.waitForTimeout(1100);
        await expect(previewToasts(page)).toHaveCount(1);
        await previewToasts(page).getByRole('button', { name: 'Close', exact: true }).click();
        await expect(previewToasts(page)).toHaveCount(0);
        await unchanged(page, baseline);
    });
}
