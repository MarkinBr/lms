import { expect, test } from '@playwright/test';
import { cursosFixture } from './fixtures/cursos';
import {
    blockRealNetwork,
    esperarCatalogoCarregado,
    esperarPaginaAtiva,
    itensDeCurso,
    mockBatchApplications,
    mockCatalogoCursos,
    preencherDadosMinimos,
} from './support/helpers';

/**
 * Cenário 3 da Issue #27 — Erro de validação no envio.
 *
 * Objetivos:
 *  - Confirmar que uma resposta 422 do backend gera mensagem visível
 *    ao usuário.
 *  - Garantir que os dados preenchidos não são apagados após o erro
 *    (regra atual do `app.js`: só a lista de sucesso é escondida).
 *  - Assegurar que nenhum request real sai para APIs, e-mail ou
 *    WhatsApp — todo tráfego externo é bloqueado por `blockRealNetwork`.
 */
test.describe('Site público — erro de validação no envio', () => {
    test.beforeEach(async ({ page }) => {
        // Ordem importa: primeiro bloqueia a rede, depois registra os
        // mocks específicos para sobrescrever a proibição.
        await blockRealNetwork(page);
        await mockCatalogoCursos(page);

        await page.goto('/');
        await page.locator('#nav [data-go="inscricao"]').first().click();
        await esperarPaginaAtiva(page, 'inscricao');
        await esperarCatalogoCarregado(page);
    });

    test('mostra mensagem de erro do backend e preserva os dados', async ({ page }) => {
        // === Preenche os campos mínimos e um curso ===
        const cursoA = cursosFixture[0];
        const linha1 = itensDeCurso(page).nth(0);
        await linha1.locator('select.insc-curso-sel').selectOption(String(cursoA.id));
        await linha1
            .locator('select.insc-mod-sel')
            .selectOption(String(cursoA.modalidades[0].id));

        await preencherDadosMinimos(page);

        // === Prepara o mock de 422 com a mesma forma que o backend real usa ===
        await mockBatchApplications(page, 422, {
            success: false,
            error: {
                code: 'VALIDATION_ERROR',
                message: 'Dados inválidos.',
                details: {
                    cpf: ['CPF informado é inválido.'],
                },
            },
        });

        // === Envia e observa a resposta ===
        const respostaBatch = page.waitForResponse(
            (resp) =>
                resp.url().includes('/api/v1/applications/batch')
                && resp.request().method() === 'POST',
        );

        await page.locator('form#inscForm button[type="submit"]').click();
        const resp = await respostaBatch;
        expect(resp.status()).toBe(422);

        // O `app.js` mostra o erro num `#toast`. A primeira mensagem do
        // `error.details` é priorizada — verificamos exatamente essa.
        const toast = page.locator('#toast');
        await expect(toast).toHaveClass(/show/);
        await expect(page.locator('#toastMsg')).toHaveText('CPF informado é inválido.');

        // === Confere que os dados não foram limpos ===
        const form = page.locator('form#inscForm');
        await expect(form.locator('input[name="nome_completo"]')).toHaveValue('Fulano de Tal');
        await expect(form.locator('input[name="email"]')).toHaveValue('fulano@example.test');
        await expect(form.locator('input[name="cpf"]')).toHaveValue('000.000.000-00');
        await expect(linha1.locator('select.insc-curso-sel')).toHaveValue(String(cursoA.id));
        await expect(linha1.locator('select.insc-mod-sel'))
            .toHaveValue(String(cursoA.modalidades[0].id));

        // O bloco de sucesso jamais aparece num 422.
        await expect(page.locator('#inscSucesso')).toBeHidden();

        // === Confere que nenhuma outra rede real foi acessada ===
        // A única request POST permitida ao endpoint de batch é a que
        // acabamos de observar; a proibição da rota `**/api/v1/applications*`
        // aborta qualquer chamada extra e falharia o teste se ocorresse.
        expect(resp.request().url()).toContain('/api/v1/applications/batch');
    });
});
