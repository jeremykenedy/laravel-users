const { test, expect } = require('@playwright/test');

for (const framework of ['bootstrap4', 'bootstrap5']) {
    for (const fullWidth of [0, 1]) {
        test(`${framework}: saved alerts, breadcrumbs and header stay within page content at full width ${fullWidth}`, async ({ page }) => {
            await page.goto(`/__browser/${framework}?settings=1&accounts=1&appearance=1&soft-deletes=1&full-width=${fullWidth}`);
            const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
            const legacy = framework === 'bootstrap4';
            const pages = [
                ['/users/settings', legacy ? '.container > .card' : '.lu-settings-panel'],
                ['/users', legacy ? '.container .card' : '.lu-panel'],
                ['/users/create', legacy ? '.container .card' : '.lu-form-card'],
                ['/users/2/edit', legacy ? '.container .card' : '.lu-edit-card'],
                ['/users/2', '.lu-profile'],
                ['/users/account', '.lu-account-card'],
                ['/users/deleted', legacy ? '.users-table .card' : '.lu-panel'],
            ];

            for (const [path, contentSelector] of pages) {
                const response = await page.request.post('/users/settings', {
                    form: {
                        _token: token,
                        _method: 'PUT',
                        avatar_source: 'initials',
                        profile_color: '#2458b7',
                        edit_color: '#705000',
                        notifications_driver: 'alert',
                        notifications_dismissible: '1',
                        show_breadcrumbs: '1',
                    },
                    maxRedirects: 0,
                });
                expect(response.status()).toBe(302);
                await page.goto(path);
                const alert = page.locator('.lu-notifications .lu-flash').first();
                await expect(alert).toContainText('User settings saved.');
                await expect(page.locator('.lu-breadcrumbs [aria-current="page"]')).toBeVisible();

                for (const width of [390, 768, 1024, 1200, 1440]) {
                    await page.setViewportSize({ width, height: 1000 });
                    const content = await page.locator(contentSelector).first().boundingBox();
                    const header = await page.locator('#laravelusers.lu-shell > .lu-toolbar, #app > .navbar-laravel > .container').evaluate(element => {
                        const box = element.getBoundingClientRect();
                        const style = getComputedStyle(element);
                        const left = parseFloat(style.paddingLeft);
                        const right = parseFloat(style.paddingRight);

                        return { x: box.x + left, width: box.width - left - right };
                    });
                    const bounds = {
                        alert: await alert.boundingBox(),
                        breadcrumbs: await page.locator('.lu-breadcrumbs').boundingBox(),
                        header,
                    };

                    for (const [element, box] of Object.entries(bounds)) {
                        expect(box, `${path}, ${width}px, ${element}`).not.toBeNull();
                        expect(Math.abs(box.x - content.x), `${path}, ${width}px, ${element} left`).toBeLessThanOrEqual(2);
                        expect(Math.abs(box.width - content.width), `${path}, ${width}px, ${element} width`).toBeLessThanOrEqual(2);
                    }
                    expect(await page.evaluate(() => document.documentElement.scrollWidth), `${path}, ${width}px`).toBeLessThanOrEqual(width);
                }

                await alert.getByRole('button', { name: 'Close', exact: true }).click();
                await expect(alert).toBeHidden();
            }
        });
    }
}
