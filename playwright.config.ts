import { defineConfig, devices } from '@playwright/test';

/**
 * Configuração Playwright para os E2E do site público do CETSADP.
 *
 * Regras importantes:
 * - Roda contra o servidor Laravel local (`php artisan serve`).
 * - A base URL pode ser sobrescrita via `E2E_BASE_URL` para apontar
 *   para outra porta ou host, sem alterar o código.
 * - Por padrão, sobe o servidor apenas para a execução dos testes
 *   (`webServer`). Se o usuário já mantém um `php artisan serve`
 *   próprio na mesma porta, defina `E2E_REUSE_SERVER=1` para não
 *   iniciar outro nem matar o processo dele.
 * - Todos os artefatos (relatório, traces, screenshots) ficam dentro
 *   de `tests/e2e/.artifacts` e `tests/e2e/.report`, que estão no
 *   `.gitignore`.
 */

const BASE_URL = process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8000';
const REUSE_SERVER = process.env.E2E_REUSE_SERVER === '1';
const BASE_URL_PORT = new URL(BASE_URL).port || '80';

export default defineConfig({
    testDir: './tests/e2e',
    testMatch: /.*\.spec\.ts/,

    // Sem paralelismo entre workers para não colidir com o servidor local
    // do Laravel (SQLite/estado compartilhado); dentro do arquivo os testes
    // ainda rodam em série na mesma sessão.
    fullyParallel: false,
    workers: 1,

    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,

    reporter: [
        ['list'],
        ['html', { outputFolder: 'tests/e2e/.report', open: 'never' }],
    ],
    outputDir: 'tests/e2e/.artifacts',

    timeout: 30_000,
    expect: { timeout: 5_000 },

    use: {
        baseURL: BASE_URL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'off',
        actionTimeout: 5_000,
        navigationTimeout: 15_000,
        locale: 'pt-BR',
        timezoneId: 'America/Sao_Paulo',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    // Inicia o servidor Laravel só quando E2E_REUSE_SERVER não estiver setado.
    // `reuseExistingServer: true` evita matar um servidor já rodando pelo
    // usuário; se a porta estiver livre, o Playwright sobe um próprio e o
    // encerra ao fim da execução.
    webServer: REUSE_SERVER
        ? undefined
        : {
              command: `php artisan serve --host=127.0.0.1 --port=${BASE_URL_PORT}`,
              url: BASE_URL,
              reuseExistingServer: true,
              timeout: 60_000,
              stdout: 'ignore',
              stderr: 'pipe',
          },
});
