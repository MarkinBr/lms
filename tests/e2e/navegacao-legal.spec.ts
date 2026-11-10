import { expect, test } from '@playwright/test';
import {
    esperarCatalogoCarregado,
    esperarPaginaAtiva,
    mockCatalogoCursos,
} from './support/helpers';

/**
 * Cenário 1 da Issue #27 — Navegação legal.
 *
 * Cobre os pontos que dispararam a issue: se um agente/humano quebrar
 * o `data-go` do formulário ou remover as páginas de termos e política
 * do include principal, este teste falha antes do merge.
 */
test.describe('Site público — navegação para páginas legais', () => {
    test.beforeEach(async ({ page }) => {
        // O catálogo é mocado mesmo neste cenário porque a página de
        // inscrição faz o fetch dele no bootstrap; sem interceptar, o
        // teste teria uma dependência de rede real.
        await mockCatalogoCursos(page);
        await page.goto('/');
        await esperarPaginaAtiva(page, 'home');
    });

    test('abre termos e política a partir do formulário de inscrição', async ({ page }) => {
        // Navega para Inscrição pelo CTA do topo (link visível).
        await page.locator('#nav [data-go="inscricao"]').first().click();
        await esperarPaginaAtiva(page, 'inscricao');
        await esperarCatalogoCarregado(page);

        // Link "termos e condições" dentro do parágrafo de aceite.
        const linkTermos = page
            .locator('div.page[data-page="inscricao"] label.terms a[data-go="termos-e-condicoes"]');
        await expect(linkTermos).toBeVisible();
        await linkTermos.click();

        await esperarPaginaAtiva(page, 'termos-e-condicoes');
        await expect(
            page.locator('div.page[data-page="termos-e-condicoes"] .form-sec-title'),
        ).toContainText('TERMOS E CONDIÇÕES');

        // Volta para Inscrição pelo botão "FAZER INSCRIÇÃO".
        await page
            .locator('div.page[data-page="termos-e-condicoes"] button[data-go="inscricao"]')
            .click();
        await esperarPaginaAtiva(page, 'inscricao');

        // Agora clica em "política de privacidade" no mesmo aceite.
        const linkPolitica = page
            .locator('div.page[data-page="inscricao"] label.terms a[data-go="politica-de-privacidade"]');
        await expect(linkPolitica).toBeVisible();
        await linkPolitica.click();

        await esperarPaginaAtiva(page, 'politica-de-privacidade');
        await expect(
            page.locator('div.page[data-page="politica-de-privacidade"] .form-sec-title'),
        ).toContainText('POLÍTICA DE PRIVACIDADE');
    });

    test('abre uma das políticas pelo link do rodapé', async ({ page }) => {
        // Preferimos o link "Termos e Condições" do bloco inferior do
        // footer (o `buildFooter('site')` monta ambos com `data-go`).
        const linkFooterTermos = page
            .locator('#footer-root a[data-go="termos-e-condicoes"]')
            .first();
        await expect(linkFooterTermos).toBeVisible();
        await linkFooterTermos.click();

        await esperarPaginaAtiva(page, 'termos-e-condicoes');
        await expect(page.locator('div.page[data-page="termos-e-condicoes"] h3').first())
            .toBeVisible();
    });
});
