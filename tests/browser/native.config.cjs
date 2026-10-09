const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    testDir: __dirname,
    testMatch: ['native.spec.js', 'native-roles.spec.js', 'native-package.spec.js'],
    fullyParallel: false,
    workers: 1,
    retries: 0,
    use: { baseURL: 'http://127.0.0.1:19855', trace: 'retain-on-failure' },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
        { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
        { name: 'webkit', use: { ...devices['Desktop Safari'] } },
    ],
    outputDir: './runtime/native-results',
    webServer: {
        command: 'php -S 127.0.0.1:19855 tests/browser/native-server.php',
        cwd: __dirname + '/../..',
        url: 'http://127.0.0.1:19855/login',
        reuseExistingServer: !process.env.CI,
        timeout: 30000,
    },
});
