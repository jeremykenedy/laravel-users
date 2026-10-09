const { expect } = require('@playwright/test');

async function submitUserForm(page, form, runtime) {
    const submit = () => form.getByRole('button', { name: 'Save changes', exact: true }).click();
    if (runtime === 'livewire') {
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), submit()]);
        return;
    }
    await submit();
}

async function searchUsers(page, value) {
    await page.locator('#lu-native-search').fill(value);
    await page.locator('.lu-search').getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page).toHaveURL(url => url.searchParams.get('user_search_box') === value);
}

module.exports = { submitUserForm, searchUsers };
