const { test, expect } = require('@playwright/test');

for (const framework of ['bootstrap4', 'bootstrap5']) {
    for (const icons of [0, 1]) {
        test(`${framework}: page header heights and title icons match with icons ${icons}`, async ({ page }) => {
            await page.goto(`/__browser/${framework}?settings=1&accounts=1&appearance=1&soft-deletes=1&icons=${icons}`);
            const measurements = new Map();
            const pages = [
                ['/users', 'users'],
                ['/users/create', 'add-user'],
                ['/users/2/edit', framework === 'bootstrap4' ? 'edit' : 'user'],
                ['/users/2', 'user'],
                ['/users/account', 'user'],
                ['/users/settings', 'settings'],
                ['/users/deleted', 'delete']
            ];

            for (const [path, iconName] of pages) {
                await page.goto(path);
                const header = page.locator('.lu-card-heading, .lu-profile-header, .card > .card-header').first();
                const title = header.locator('.lu-list-title, h1').first();
                await expect(title).toBeVisible();
                expect((await title.innerText()).trim().length).toBeGreaterThan(0);
                const icon = title.locator(`svg[data-lu-icon="${iconName}"]`);
                if (icons) {
                    await expect(icon).toHaveCount(1);
                    await expect(icon).toBeVisible();
                    await expect(icon).toHaveAttribute('aria-hidden', 'true');
                } else await expect(title.locator('svg')).toHaveCount(0);

                for (const theme of ['light', 'dark']) {
                    const toggle = page.locator('#lu-theme');
                    if (await toggle.locator('svg:not([hidden])').getAttribute('data-theme-icon') !== theme) await toggle.click();
                    for (const width of [320, 390, 768, 1440]) {
                        await page.setViewportSize({ width, height: 1000 });
                        const bounds = await header.boundingBox();
                        const titleBox = await title.boundingBox();
                        const textStyle = await title.evaluate(element => {
                            const style = getComputedStyle(element);
                            return { fontSize: style.fontSize, lineHeight: style.lineHeight };
                        });
                        const key = `${theme}:${width}`;
                        const measured = { height: bounds.height, textStyle, icon: icons ? await icon.boundingBox() : null };
                        if (measurements.has(key)) {
                            const reference = measurements.get(key);
                            expect(measured.height, `${path}, ${key}, header height`).toBeCloseTo(reference.height, 1);
                            expect(measured.textStyle, `${path}, ${key}, title typography`).toEqual(reference.textStyle);
                            if (icons) {
                                expect(measured.icon.width, `${path}, ${key}, icon width`).toBeCloseTo(reference.icon.width, 1);
                                expect(measured.icon.height, `${path}, ${key}, icon height`).toBeCloseTo(reference.icon.height, 1);
                            }
                        } else measurements.set(key, measured);
                        if (icons) {
                            expect(Math.abs(measured.icon.y + measured.icon.height / 2 - titleBox.y - titleBox.height / 2), `${path}, ${key}, icon alignment`).toBeLessThanOrEqual(1);
                        }
                        expect(await page.evaluate(() => document.documentElement.scrollWidth), `${path}, ${key}, overflow`).toBeLessThanOrEqual(width);
                    }
                }
            }

            const response = await page.goto('/users/account-link/invalid');
            expect(response.status()).toBe(410);
            const publicTitle = page.getByRole('heading', { name: 'This account link is invalid', exact: true });
            if (icons) await expect(publicTitle.locator('svg[data-lu-icon="warning"]')).toBeVisible();
            else await expect(publicTitle.locator('svg')).toHaveCount(0);
        });
    }

    test(`${framework}: optional role and permission controls stay absent when disabled`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?settings=1&accounts=1`);
        for (const path of ['/users/create', '/users/2/edit']) {
            await page.goto(path);
            await expect(page.locator('[name="role"], [name="permissions[]"], [name="permissions_present"]')).toHaveCount(0);
            await expect(page.getByRole('tab', { name: 'Permissions', exact: true })).toHaveCount(0);
        }
        await page.goto('/users');
        await expect(page.locator('a[href$="/users/roles"]')).toHaveCount(0);
        await page.goto('/users/settings');
        await expect(page.getByRole('tab', { name: 'Access', exact: true })).toHaveCount(0);
    });

    test(`${framework}: all settings tab titles use the same space below the tab strip`, async ({ page }) => {
        await page.goto(`/__browser/${framework}?settings=1&accounts=1&packages=1&soft-deletes=1`);
        await page.goto('/users/settings');
        const tabs = page.getByRole('tablist', { name: 'User settings', exact: true });
        for (const width of [390, 768, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            let reference;
            for (const name of ['appearance', 'notifications', 'accounts', 'emails', 'cleanup', 'packages']) {
                await page.locator(`#lu-tab-${name}`).click();
                const panel = page.locator(`#lu-settings-${name}`);
                await expect(panel).toBeVisible();
                const title = panel.locator('.lu-title-heading').first();
                await expect(title).toBeVisible();
                const strip = await tabs.boundingBox();
                const heading = await title.boundingBox();
                const gap = heading.y - strip.y - strip.height;
                if (reference === undefined) reference = gap;
                expect(gap, `${name}, ${width}px, title top gap`).toBeCloseTo(reference, 1);
            }
        }
    });
}

test('bootstrap4: real search and clear preserve the users heading icon', async ({ page }) => {
    await page.goto('/__browser/bootstrap4');
    const title = page.locator('#card_title');
    const icon = title.locator('svg[data-lu-icon="users"]');
    const initialTitle = await title.innerText();
    await expect(icon).toBeVisible();
    for (const query of ['user0@example.com', 'no-matching-account@example.com']) {
        const response = page.waitForResponse(response => new URL(response.url()).pathname === '/search-users'
            && response.request().method() === 'POST');
        await page.locator('#user_search_box').fill(query);
        await page.locator('#user_search_box').press('Enter');
        expect((await response).status()).toBe(200);
        await expect(page.locator('#users_table')).toBeHidden();
        if (query === 'user0@example.com') await expect(page.locator('#search_results')).toContainText(query);
        else {
            await expect(page.locator('#search_results')).toContainText('No Results');
            await expect(page.locator('#search_results [data-lu-user]')).toHaveCount(0);
        }
        await expect(icon).toBeVisible();
        await expect(title.locator('.lu-title-text')).not.toHaveText(initialTitle.trim());
    }
    await page.locator('.clear-search').click();
    await expect(page.locator('#user_search_box')).toBeEmpty();
    await expect(page.locator('#users_table')).toBeVisible();
    await expect(title).toHaveText(initialTitle.trim());
    await expect(icon).toBeVisible();
});
