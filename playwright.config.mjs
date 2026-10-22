import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Front',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 2 : 4,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: { baseURL: 'http://127.0.0.1:8765', trace: 'retain-on-failure', screenshot: 'only-on-failure' },
    webServer: process.env.PLAYWRIGHT_EXTERNAL_SERVER ? undefined : {
        command: 'node tests/Front/start-server.mjs 2>/tmp/pj-front-server.log',
        url: 'http://127.0.0.1:8765',
        reuseExistingServer: false,
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] }, testIgnore: /visual\.spec/ },
        { name: 'firefox', use: { ...devices['Desktop Firefox'] }, testIgnore: /visual\.spec/ },
        { name: 'webkit', use: { ...devices['Desktop Safari'] }, testIgnore: /visual\.spec/ },
        { name: 'visual', use: { ...devices['Desktop Chrome'] }, testMatch: /visual\.spec/ },
    ],
});
