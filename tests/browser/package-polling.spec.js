const { test, expect } = require('@playwright/test');

async function openQueuedPackage(page, framework, handleStatus) {
    await page.route('**/users/settings/packages/verify', route => route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({status: 'completed', queue_ready: true, message: 'Worker verified.'})
    }));
    const statusUrl = '/users/settings/packages/11111111-1111-1111-1111-111111111111';
    await page.route('**/users/settings/packages', route => route.fulfill({
        status: 202,
        contentType: 'application/json',
        body: JSON.stringify({status: 'queued', message: 'Package change queued.', status_url: statusUrl})
    }));
    await page.route('**' + statusUrl, handleStatus);
    if (process.env.LARAVEL_USERS_PACKAGE_HOST === '1') {
        await page.goto('/login');
        await page.getByLabel('Email').fill('morgan@example.com');
        await page.getByLabel('Password').fill('fixture-password');
        await page.getByRole('button', {name: 'Sign in', exact: true}).click();
    } else {
        await page.goto(`/__browser/${framework}?settings=1&packages=1`);
    }
    await page.goto('/users/settings#packages');
    await page.getByRole('tab', {name: 'Packages', exact: true}).click();
    await page.locator('[data-lu-package-verify]').click();
    const install = page.locator('[data-lu-package="toast"][data-lu-package-operation="install"]');
    await expect(install).toBeEnabled();
    await install.click();
    const dialog = page.locator('#lu-package-dialog');
    await dialog.locator('[name="confirmation"]').fill('continue');
    await dialog.locator('[name="acknowledgement"]').check();
    await dialog.getByRole('button', {name: 'Confirm package change', exact: true}).click();
}

for (const framework of ['bootstrap4', 'bootstrap5']) {
    test(`${framework}: package polling recovers from discovery errors and refreshes automatically`, async ({page}) => {
        let requests = 0;
        let refreshed;
        await openQueuedPackage(page, framework, async route => {
            requests++;
            if (requests === 1) {
                refreshed = page.waitForResponse(response => response.request().isNavigationRequest()
                    && new URL(response.url()).pathname === '/users/settings' && response.status() === 200);
                await route.fulfill({status: 500, contentType: 'text/html', body: 'Package discovery is refreshing.'});
                return;
            }
            await route.fulfill({status: 200, contentType: 'application/json', body: JSON.stringify({
                status: 'completed', message: 'Package installed and setup completed.'
            })});
        });
        await expect(page.locator('[data-lu-package-status-retry]')).toBeVisible();
        await expect.poll(() => requests).toBe(2);
        await refreshed;
        await expect(page.getByRole('tab', {name: 'Packages', exact: true})).toHaveAttribute('aria-selected', 'true');
        expect(requests).toBe(2);
    });

    for (const status of [401, 403, 404]) {
        test(`${framework}: package polling stops after HTTP ${status}`, async ({page}) => {
            let requests = 0;
            await openQueuedPackage(page, framework, async route => {
                requests++;
                await route.fulfill({status, contentType: 'application/json', body: JSON.stringify({message: 'Unavailable.'})});
            });
            await expect(page.locator('[data-lu-package-status-retry]')).toBeVisible();
            await page.waitForTimeout(2500);
            expect(requests).toBe(1);
        });
    }
}
