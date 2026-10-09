const { test, expect } = require('@playwright/test');

for (const fullWidth of [0, 1]) {
    for (const bootstrapAvailable of [true, false]) {
        test(`bootstrap5: every page keeps its container width with Bootstrap CSS ${bootstrapAvailable} and full width ${fullWidth}`, async ({ page }) => {
            if (!bootstrapAvailable) {
                await page.route('**/*bootstrap*.css', route => route.abort());
            }
            await page.goto(`/__browser/bootstrap5?settings=1&accounts=1&appearance=1&soft-deletes=1&published-assets=1&full-width=${fullWidth}`);
            const paths = ['/users', '/users/deleted', '/users/create', '/users/2/edit', '/users/2', '/users/account', '/users/settings', '/users/account-link/invalid'];

            for (const path of paths) {
                await page.goto(path);
                const publicPage = path.endsWith('/invalid');
                const content = page.locator(publicPage ? '.lu-public-container > main' : '#laravelusers > .lu-panel, #laravelusers > .lu-profile').first();
                await expect(content).toBeVisible();

                for (const width of [320, 390, 640, 768, 1024, 1200, 1440]) {
                    await page.setViewportSize({ width, height: 1000 });
                    const gutter = width <= 640 ? 14 : 24;
                    const containerWidth = fullWidth ? width : Math.min(width, 1160);
                    const expected = { x: (width - containerWidth) / 2 + gutter, width: containerWidth - gutter * 2 };
                    const bounds = await content.boundingBox();
                    expect(bounds.x, `${path}, ${width}px, left edge`).toBeCloseTo(expected.x, 1);
                    expect(bounds.width, `${path}, ${width}px, width`).toBeCloseTo(expected.width, 1);
                    expect(await page.evaluate(() => document.documentElement.scrollWidth), `${path}, ${width}px, overflow`).toBeLessThanOrEqual(width);

                    if (!publicPage) {
                        const header = await page.locator('.lu-toolbar').boundingBox();
                        expect(header.x, `${path}, ${width}px, header left edge`).toBeCloseTo(expected.x, 1);
                        expect(header.width, `${path}, ${width}px, header width`).toBeCloseTo(expected.width, 1);
                    }
                }
            }
        });
    }
}
