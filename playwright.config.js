const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/browser',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    use: { baseURL: 'http://127.0.0.1:19847', trace: 'retain-on-failure' },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
        { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
        { name: 'webkit', use: { ...devices['Desktop Safari'] } }
    ],
    webServer: {
        command: 'php -S 127.0.0.1:19847 tests/browser/server.php',
        url: 'http://127.0.0.1:19847/login',
        reuseExistingServer: !process.env.CI,
        timeout: 30000
    }
});
