import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL || 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: {
        timeout: 5_000,
    },
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: process.env.CI
        ? [['line'], ['html', { outputFolder: 'tests/e2e/.report', open: 'never' }]]
        : [['list']],
    use: {
        baseURL,
        locale: 'pt-BR',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    outputDir: 'tests/e2e/.artifacts',
    webServer: process.env.E2E_BASE_URL
        ? undefined
        : {
              command: 'php artisan serve --host=127.0.0.1 --port=8000',
              url: baseURL,
              reuseExistingServer: !process.env.CI,
              timeout: 120_000,
          },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
