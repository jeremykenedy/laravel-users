const { test, expect } = require('@playwright/test');
const { createHash } = require('node:crypto');

const samples = {
    profile: 'jordan.ellis@example.com',
    edit: 'casey.morgan@example.com',
    profile_dark: 'taylor.reed@example.com',
    edit_dark: 'avery.parker@example.com'
};

for (const framework of ['bootstrap4', 'bootstrap5']) {
    test(framework + ': generated avatar previews render immediately without saving', async ({ page }) => {
        await page.route('https://www.gravatar.com/avatar/**', route => {
            const url = new URL(route.request().url());
            if (url.searchParams.get('d') === '404') return route.fulfill({ status: 404, body: '' });
            const color = url.pathname.split('/').pop().slice(0, 6);
            return route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128"><rect width="128" height="128" fill="#' + color + '"/></svg>' });
        });
        await page.goto('/__browser/' + framework + '?settings=1&appearance=1&published-assets=1');
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        const saved = await page.request.post('/users/settings', { form: { _token: token, _method: 'PUT', avatar_source: 'initials', profile_color: '#2458b7', edit_color: '#705000' }, maxRedirects: 0 });
        expect(saved.status()).toBe(302);
        await page.goto('/users/settings');
        await expect(page.locator('.lu-notifications')).toContainText('User settings saved.');
        const source = page.locator('#settings-avatar');
        await expect(source).toHaveValue('initials');
        for (const style of ['identicon', 'monsterid', 'robohash', 'retro', 'wavatar', 'mp']) {
            const updated = page.waitForResponse(response => new URL(response.url()).pathname === '/users/settings/avatar-preview' && response.request().method() === 'POST');
            await source.selectOption(style);
            expect((await updated).ok()).toBeTruthy();
            for (const [kind, email] of Object.entries(samples)) {
                const image = page.locator('[data-lu-avatar-preview="' + kind + '"] img');
                const hash = createHash('sha256').update(email).digest('hex');
                await expect(image).toHaveAttribute('src', new RegExp('/avatar/' + hash + '\\?s=\\d+&d=' + style + '&r=g&f=y$'));
                await expect(image).toHaveAttribute('referrerpolicy', 'no-referrer');
                await expect.poll(() => image.evaluate(node => node.complete && node.naturalWidth > 0)).toBe(true);
            }
        }
        await source.selectOption('gravatar');
        await expect(page.locator('[data-lu-avatar-preview] img')).toHaveCount(0);
        for (const kind of Object.keys(samples)) await expect(page.locator('[data-lu-avatar-preview="' + kind + '"] [data-lu-avatar-icon]')).toBeVisible();
        await page.reload();
        await expect(source).toHaveValue('initials');
        await expect(page.locator('[data-lu-avatar-preview] img')).toHaveCount(0);
    });
}
