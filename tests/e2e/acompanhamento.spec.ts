import { expect, test } from '@playwright/test';
import {
    esperarCatalogoCarregado,
    esperarPaginaAtiva,
    mockCatalogoCursos,
} from './support/helpers';

/**
 * Cenário 4 da Issue #27 — Acompanhamento de inscrição.
 *
 * Somente valida o caminho de navegação (link do formulário de
 * inscrição → tela "Acompanhar Inscrição"). Não dispara consulta
 * real, portanto não há mock de `/api/v1/applications/acompanhamento`.
 */
test.describe('Site público — acompanhamento de inscrição', () => {
    test.beforeEach(async ({ page }) => {
        await mockCatalogoCursos(page);
        await page.goto('/');
        await page.locator('#nav [data-go="inscricao"]').first().click();
        await esperarPaginaAtiva(page, 'inscricao');
        await esperarCatalogoCarregado(page);
    });

    test('abre a tela de acompanhamento pelo link do formulário', async ({ page }) => {
        const linkAcompanhar = page
            .locator('div.page[data-page="inscricao"] a[data-go="acompanhar-inscricao"]');
        await expect(linkAcompanhar).toBeVisible();
        await linkAcompanhar.click();

        await esperarPaginaAtiva(page, 'acompanhar-inscricao');

        // Confere que o formulário de consulta segura carregou com os
        // campos esperados (`#acompProtocolo` + `#acompEmail`).
        await expect(page.locator('#acompanharForm')).toBeVisible();
        await expect(page.locator('#acompProtocolo')).toBeVisible();
        await expect(page.locator('#acompEmail')).toBeVisible();
        await expect(
            page.locator('div.page[data-page="acompanhar-inscricao"] .form-sec-title'),
        ).toContainText('CONSULTAR SITUAÇÃO');
    });
});
