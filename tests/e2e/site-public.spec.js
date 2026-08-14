import { expect, test } from '@playwright/test';

async function abrirPagina(page, pagina) {
    await page.locator(`[data-go="${pagina}"]:visible`).first().click();
    await expect(page.locator(`.page[data-page="${pagina}"].active`)).toBeVisible();
}

test.describe('site público CETSADP', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/');
        await expect(page.locator('.page[data-page="home"].active')).toBeVisible();
    });

    test('navega pelo catálogo e abre o formulário de inscrição', async ({ page }) => {
        await abrirPagina(page, 'teologia');
        await expect(page.locator('.page[data-page="teologia"].active')).toContainText('Curso Médio em Teologia');

        await abrirPagina(page, 'inscricao');
        await expect(page.locator('#inscForm')).toBeVisible();
        await expect(page.locator('#inscCursosList .insc-curso-item')).toHaveCount(1);
        await expect(page.locator('#inscForm')).toContainText('Selecione de 1 a 3 cursos');
    });

    test('acompanha inscrição sem vazar dados quando a consulta não existe', async ({ page }) => {
        await page.route('**/api/v1/applications/acompanhamento', async route => {
            await route.fulfill({
                status: 404,
                contentType: 'application/json',
                body: JSON.stringify({ success: false, error: { message: 'Não foi possível localizar a inscrição.' } }),
            });
        });

        await abrirPagina(page, 'acompanhar-inscricao');
        await page.locator('#acompProtocolo').fill('INSC-E2E-NAO-EXISTE');
        await page.locator('#acompEmail').fill('e2e@example.test');
        await page.getByRole('button', { name: /CONSULTAR INSCRIÇÃO/i }).click();

        await expect(page.locator('#acompErro')).toBeVisible();
        await expect(page.locator('#acompErro')).toContainText('NÃO ENCONTRAMOS SUA INSCRIÇÃO');
        await expect(page.locator('#acompErro')).not.toContainText('INSC-E2E-NAO-EXISTE');
    });

    test('envia contato pelo formulário sem depender de SMTP real', async ({ page }) => {
        let recebeuContato = false;
        await page.route('**/contato', async route => {
            recebeuContato = route.request().method() === 'POST';
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ success: true, message: 'Mensagem enviada! Retornaremos em até 24h úteis.' }),
            });
        });

        await abrirPagina(page, 'contato');
        const form = page.locator('#contactForm');
        await form.locator('[name="nome"]').fill('Visitante E2E');
        await form.locator('[name="email"]').fill('e2e@example.test');
        await form.locator('[name="assunto"]').selectOption({ label: 'Dúvidas sobre cursos' });
        await form.locator('[name="mensagem"]').fill('Mensagem de teste do formulário público.');
        await form.locator('[name="consentimento"]').check();
        await form.getByRole('button', { name: /ENVIAR MENSAGEM/i }).click();

        await expect(page.locator('#toastMsg')).toContainText('Mensagem enviada');
        expect(recebeuContato).toBe(true);
    });

    test('assina newsletter sem gravar dados persistentes no ambiente do teste', async ({ page }) => {
        let recebeuNewsletter = false;
        await page.route('**/newsletter', async route => {
            recebeuNewsletter = route.request().method() === 'POST';
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ success: true, message: 'Inscrição na newsletter confirmada! Que Deus abençoe.' }),
            });
        });

        await abrirPagina(page, 'noticias');
        const form = page.locator('#nlForm');
        await form.locator('[name="email"]').fill('newsletter-e2e@example.test');
        await form.scrollIntoViewIfNeeded();
        await form.locator('[name="email"]').press('Enter');

        await expect(page.locator('#toastMsg')).toContainText('newsletter confirmada');
        expect(recebeuNewsletter).toBe(true);
    });
});
