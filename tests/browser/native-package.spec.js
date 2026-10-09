const { test, expect } = require('@playwright/test');
const fs = require('node:fs/promises');
const path = require('node:path');
const { startPackageWorker } = require('./package-worker.cjs');

const toastConfig = path.join(__dirname, 'runtime/config/toast.php');
let packageWorker;
let originalToastConfig;

test.beforeAll(async () => {
    originalToastConfig = await fs.readFile(toastConfig).catch(() => null);
    packageWorker = await startPackageWorker(19855);
});
test.afterAll(async () => {
    packageWorker?.kill('SIGTERM');
    if (originalToastConfig) await fs.writeFile(toastConfig, originalToastConfig);
});

for (const runtime of ['livewire', 'vue', 'react', 'svelte']) {
    test(`${runtime} automatically refreshes actual setup and preserves independent requirement status`, async ({ page }) => {
        test.setTimeout(60000);
        await fs.rm(toastConfig, { force: true });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/__browser/bootstrap5?runtime=${runtime}&accounts=1&settings=1&packages=1&search-debounce=0`);
        await page.getByRole('link', { name: 'User settings', exact: true }).click();
        const toast = page.locator('.lu-settings-choice').filter({ has: page.getByRole('heading', { name: 'Laravel Toast', exact: true }) });
        const configure = toast.getByRole('button', { name: 'Complete setup', exact: true });
        await expect(configure).toBeEnabled({ timeout: 20000 });
        await expect(configure.locator('[data-lu-icon="settings"]')).toBeVisible();
        const requirements = page.locator('[data-lu-native-requirement-actions]');
        const reverify = requirements.getByRole('button', { name: 'Re-Verify package requirements', exact: true });
        await expect(reverify).toBeEnabled();
        await expect(reverify.locator('[data-lu-icon="verify"]')).toBeVisible();
        await expect(requirements.locator('[data-lu-icon="check"]')).toBeVisible();
        for (const width of [390, 768]) {
            await page.setViewportSize({ width, height: 950 });
            const buttons = await requirements.locator('button').evaluateAll(nodes => nodes.map(node => {
                const box = node.getBoundingClientRect();
                return { x: box.x, y: box.y, width: box.width, height: box.height, fontSize: parseFloat(getComputedStyle(node).fontSize), text: node.textContent.trim(), labelVisible: node.querySelector('span').getBoundingClientRect().width > 0 };
            }));
            if (width === 390) {
                expect(buttons[0].x).toBe(buttons[1].x);
                expect(buttons[1].y).toBeGreaterThanOrEqual(buttons[0].y + buttons[0].height);
                expect(buttons[0].width).toBe(buttons[1].width);
            } else expect(buttons[0].y).toBe(buttons[1].y);
            expect(buttons.every(button => button.labelVisible && button.fontSize >= 12)).toBe(true);
            expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
            await page.locator('#packages').screenshot({ path: `tests/browser/runtime/native-${runtime}-package-actions-${width}.png` });
        }
        await configure.click();
        const dialog = page.getByRole('dialog');
        await dialog.locator('[name="confirmation"]').fill('continue');
        await dialog.locator('[name="acknowledgement"]:not([type="hidden"])').check();
        const queued = page.waitForResponse(response => response.url().endsWith('/users/settings/packages') && response.request().method() === 'POST');
        const refreshed = page.waitForResponse(response => response.request().isNavigationRequest() && new URL(response.url()).pathname === '/users/settings');
        await dialog.getByRole('button', { name: 'Save changes', exact: true }).click();
        const response = await queued;
        expect(response.status()).toBe(202);
        expect((await response.json()).operation).toBe('configure');
        await refreshed;
        await expect(page).toHaveURL(/\/users\/settings#packages$/);
        await expect(toast.locator('.lu-setup-completed')).toHaveText('Setup completed.');
        await expect(toast.locator('.lu-setup-completed [data-lu-icon="check"]')).toBeVisible();
        await expect(configure).toHaveCount(0);
        await expect(toast.getByRole('button', { name: 'Remove', exact: true })).toBeEnabled();
        await expect(page.locator('[data-lu-package-state]')).toHaveAttribute('data-lu-package-state', 'completed');
        await expect(page.locator('[data-lu-native-package-requirements]')).toContainText(/verified|ready/i);
        let additionalReloads = 0;
        page.on('request', request => { if (request.isNavigationRequest() && new URL(request.url()).pathname === '/users/settings') additionalReloads++; });
        await page.waitForTimeout(2200);
        expect(additionalReloads).toBe(0);
        const manualRefresh = page.waitForResponse(response => response.request().isNavigationRequest() && new URL(response.url()).pathname === '/users/settings');
        await page.locator('[data-lu-package-state]').getByRole('link', { name: 'Refresh settings', exact: true }).click();
        await manualRefresh;
        await expect(toast.locator('.lu-setup-completed')).toHaveText('Setup completed.');
        await expect(configure).toHaveCount(0);
        await page.locator('#packages').screenshot({ path: `tests/browser/runtime/native-${runtime}-package-automatic-completed.png` });
        expect(errors).toEqual([]);
    });
}
