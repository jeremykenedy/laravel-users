const { test, expect } = require('@playwright/test');

const viewports = [
    { width: 375, height: 812 },
    { width: 768, height: 1024 },
    { width: 1440, height: 900 }
];

for (const framework of ['bootstrap5', 'tailwind']) {
    test(`${framework}: published assets retain tabs, search and layout`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/__browser/${framework}?published-assets=1&accounts=1&settings=1`);
        await page.goto('/users/settings');
        const script = page.locator('script[src*="/vendor/laravelusers/releases/"][src$="/users.js"]');
        await expect(script).toHaveCount(1);
        const response = await page.request.get(await script.getAttribute('src'));
        expect(response.ok()).toBe(true);
        expect(response.headers()['content-type']).toMatch(/javascript/);
        const styles = page.locator('link[href*="/vendor/laravelusers/releases/"]');
        for (const stylesheet of await styles.all()) {
            const css = await page.request.get(await stylesheet.getAttribute('href'));
            expect(css.ok()).toBe(true);
            expect(css.headers()['content-type']).toMatch(/text\/css/);
        }
        await expect(page.locator('.lu-settings-panel > header h1')).toHaveCSS('font-size', '20px');
        for (const viewport of viewports) {
            await page.setViewportSize(viewport);
            const card = page.locator('.laravel-users-main-card');
            await expect(card).toBeVisible();
            const bounds = await card.boundingBox();
            expect(bounds.x).toBeGreaterThanOrEqual(0);
            expect(bounds.x + bounds.width).toBeLessThanOrEqual(viewport.width);
            expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(viewport.width);
            await page.getByRole('tab', { name: 'Appearance', exact: true }).click();
            await expect(page.locator('#settings-profile-color')).toBeVisible();
        }
        await page.goto('/users');
        const search = page.locator('#user_search_box');
        await search.fill('user0@example.com');
        await page.locator('#lu-search, #search_users').evaluate(form => form.requestSubmit());
        await expect(page.locator('#lu-results, #search_results')).toContainText('user0@example.com');
        expect(errors).toEqual([]);
    });
}

test('bootstrap4: publication preserves the legacy scripts and responsive layout', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/__browser/bootstrap4?published-assets=1&settings=1');
    for (const viewport of viewports) {
        await page.setViewportSize(viewport);
        const card = page.locator('.laravel-users-main-card');
        await expect(card).toBeVisible();
        const bounds = await card.boundingBox();
        expect(bounds.x + bounds.width).toBeLessThanOrEqual(viewport.width);
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(viewport.width);
    }
    await expect(page.locator('script[src$="/users.js"]')).toHaveCount(0);
    await page.locator('#user_search_box').fill('user0@example.com');
    await page.locator('#user_search_box').press('Enter');
    await expect(page.locator('#search_results')).toContainText('user0@example.com');
    expect(errors).toEqual([]);
});
