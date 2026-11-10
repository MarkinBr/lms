import { expect, test } from '@playwright/test';
import { cursosFixture } from './fixtures/cursos';
import {
    esperarCatalogoCarregado,
    esperarPaginaAtiva,
    itensDeCurso,
    mockCatalogoCursos,
} from './support/helpers';

/**
 * Cenário 2 da Issue #27 — Inscrição em mais de um curso.
 *
 * Se alguém regredir o `atualizarOpcoesCursos()` do `app.js`, o
 * botão "ADICIONAR OUTRO CURSO", ou o limite máximo de 3 cursos,
 * este spec falha antes do merge.
 *
 * Todo o catálogo vem de fixture (não há dependência de banco real).
 */
test.describe('Site público — inscrição em múltiplos cursos', () => {
    test.beforeEach(async ({ page }) => {
        await mockCatalogoCursos(page);
        await page.goto('/#');
        await page.locator('#nav [data-go="inscricao"]').first().click();
        await esperarPaginaAtiva(page, 'inscricao');
        await esperarCatalogoCarregado(page);
    });

    test('permite adicionar até três cursos e bloqueia repetição', async ({ page }) => {
        const cursoA = cursosFixture[0];
        const cursoB = cursosFixture[1];
        const cursoC = cursosFixture[2];

        // === Linha 1 ===
        const linha1 = itensDeCurso(page).nth(0);
        await linha1.locator('select.insc-curso-sel').selectOption(String(cursoA.id));
        await linha1
            .locator('select.insc-mod-sel')
            .selectOption(String(cursoA.modalidades[0].id));

        // === Adiciona linha 2 ===
        await page.locator('#inscAddCurso').click();
        await expect(itensDeCurso(page)).toHaveCount(2);

        // No 2º select, o curso já usado no 1º precisa estar desabilitado.
        const selectCursoLinha2 = itensDeCurso(page).nth(1).locator('select.insc-curso-sel');
        await expect(
            selectCursoLinha2.locator(`option[value="${cursoA.id}"]`),
        ).toBeDisabled();
        // Os demais cursos continuam disponíveis.
        await expect(
            selectCursoLinha2.locator(`option[value="${cursoB.id}"]`),
        ).toBeEnabled();

        await selectCursoLinha2.selectOption(String(cursoB.id));
        await itensDeCurso(page)
            .nth(1)
            .locator('select.insc-mod-sel')
            .selectOption(String(cursoB.modalidades[0].id));

        // Depois de escolher o curso B na linha 2, o curso B fica
        // desabilitado no select da linha 1 (só o próprio valor
        // selecionado dela é que segue habilitado).
        const selectCursoLinha1 = itensDeCurso(page).nth(0).locator('select.insc-curso-sel');
        await expect(
            selectCursoLinha1.locator(`option[value="${cursoB.id}"]`),
        ).toBeDisabled();

        // === Remove a linha 2 e confirma que voltou ao estado inicial ===
        await itensDeCurso(page).nth(1).locator('.insc-curso-remove').click();
        await expect(itensDeCurso(page)).toHaveCount(1);
        // O curso B volta a ficar disponível na linha remanescente.
        await expect(
            itensDeCurso(page)
                .nth(0)
                .locator(`select.insc-curso-sel option[value="${cursoB.id}"]`),
        ).toBeEnabled();

        // === Reconstrói duas linhas e adiciona a terceira ===
        await page.locator('#inscAddCurso').click();
        await itensDeCurso(page)
            .nth(1)
            .locator('select.insc-curso-sel')
            .selectOption(String(cursoB.id));
        await itensDeCurso(page)
            .nth(1)
            .locator('select.insc-mod-sel')
            .selectOption(String(cursoB.modalidades[0].id));

        await page.locator('#inscAddCurso').click();
        await expect(itensDeCurso(page)).toHaveCount(3);

        await itensDeCurso(page)
            .nth(2)
            .locator('select.insc-curso-sel')
            .selectOption(String(cursoC.id));
        await itensDeCurso(page)
            .nth(2)
            .locator('select.insc-mod-sel')
            .selectOption(String(cursoC.modalidades[0].id));

        // === Quarto curso é bloqueado ===
        // O botão de adicionar some quando o máximo é atingido
        // (regra de `atualizarOpcoesCursos()` em app.js).
        await expect(page.locator('#inscAddCurso')).toBeHidden();
        await expect(itensDeCurso(page)).toHaveCount(3);
    });
});
