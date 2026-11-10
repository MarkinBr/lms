import { expect, Page, Route } from '@playwright/test';
import { catalogoCursosBody } from '../fixtures/cursos';

/**
 * Utilidades compartilhadas entre os specs E2E do site público.
 *
 * As funções aqui existem só para deixar cada spec curto e legível,
 * sem introduzir dependência de infraestrutura nem de dados reais.
 */

/**
 * Intercepta `GET /api/v1/cursos` e responde com o catálogo fixture
 * (três cursos ativos, cada um com duas modalidades).
 *
 * Deve ser chamado ANTES de qualquer `page.goto('/')` que dispare o
 * carregamento do formulário de inscrição.
 */
export async function mockCatalogoCursos(page: Page): Promise<void> {
    await page.route('**/api/v1/cursos', async (route: Route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(catalogoCursosBody),
        });
    });
}

/**
 * Falha explicitamente se o teste tentar POSTar em endpoints que
 * enviariam dados reais (e-mail, WhatsApp, gravação de inscrição).
 *
 * Cada teste que precisa liberar `POST /api/v1/applications/batch`
 * chama `mockBatchApplications` DEPOIS deste helper, sobrescrevendo
 * a rota para a URL específica.
 */
export async function blockRealNetwork(page: Page): Promise<void> {
    const rotasProibidas = [
        '**/api/v1/applications',
        '**/api/v1/applications/**',
        '**/contato',
        '**/newsletter',
    ];

    for (const padrao of rotasProibidas) {
        await page.route(padrao, async (route: Route) => {
            // Se o teste chegou aqui, é bug: o spec deveria ter mocado
            // a chamada. Aborta a request e o teste falha mais adiante
            // por não observar a resposta esperada.
            await route.abort('failed');
        });
    }
}

/**
 * Intercepta `POST /api/v1/applications/batch` retornando o status e
 * corpo informados. Sobrescreve a proibição de `blockRealNetwork`
 * quando registrada depois dela.
 */
export async function mockBatchApplications(
    page: Page,
    status: number,
    body: unknown,
): Promise<void> {
    await page.route('**/api/v1/applications/batch', async (route: Route) => {
        await route.fulfill({
            status,
            contentType: 'application/json',
            body: JSON.stringify(body),
        });
    });
}

/**
 * Espera até que a página SPA com o `data-page` informado esteja
 * ativa (recebeu a classe `active` via `go()` no `app.js`).
 */
export async function esperarPaginaAtiva(page: Page, dataPage: string): Promise<void> {
    const seletor = `div.page[data-page="${dataPage}"]`;
    await expect(page.locator(seletor)).toHaveClass(/(^|\s)active(\s|$)/);
}

/**
 * Retorna todos os itens visíveis de curso dentro do formulário
 * de inscrição (`#inscCursosList .insc-curso-item`).
 */
export function itensDeCurso(page: Page) {
    return page.locator('#inscCursosList .insc-curso-item');
}

/**
 * Espera o catálogo de cursos ser preenchido no primeiro select.
 * Enquanto está carregando, a opção default é "Carregando cursos…".
 */
export async function esperarCatalogoCarregado(page: Page): Promise<void> {
    const primeiroSelect = itensDeCurso(page).first().locator('select.insc-curso-sel');
    // Aguarda o texto padrão "Selecione um curso" (definido em app.js após o fetch).
    await expect(primeiroSelect.locator('option').first()).toHaveText(
        /Selecione um curso/,
        { timeout: 10_000 },
    );
}

/**
 * Preenche os campos mínimos obrigatórios do formulário de inscrição,
 * com valores neutros. Nenhum dos valores é pessoal ou de produção.
 */
export async function preencherDadosMinimos(page: Page): Promise<void> {
    const form = page.locator('form#inscForm');
    await form.locator('input[name="nome_completo"]').fill('Fulano de Tal');
    await form.locator('input[name="data_nascimento"]').fill('1990-01-15');
    await form.locator('input[name="cpf"]').fill('000.000.000-00');
    await form.locator('select[name="sexo"]').selectOption('M');
    await form.locator('input[name="telefone"]').fill('(21) 90000-0000');
    await form.locator('input[name="email"]').fill('fulano@example.test');
    await form.locator('input[name="logradouro"]').fill('Rua de Teste');
    await form.locator('input[name="numero"]').fill('1');
    // Endereço complementar
    const bairro = form.locator('input[name="bairro"]');
    if (await bairro.count()) await bairro.fill('Centro');
    const cidade = form.locator('input[name="cidade"]');
    if (await cidade.count()) await cidade.fill('Paracambi');
    await form.locator('select[name="estado"]').selectOption('RJ');
    const cep = form.locator('input[name="cep"]');
    if (await cep.count()) await cep.fill('26600-000');
    await form.locator('select[name="escolaridade"]').selectOption('medio_completo');
    await form
        .locator('.field')
        .filter({ hasText: 'Já possui formação teológica?' })
        .locator('select')
        .selectOption({ label: 'Não' });
    await form
        .locator('.field')
        .filter({ hasText: 'Como conheceu o CETSADP?' })
        .locator('select')
        .selectOption({ label: 'Site' });
    // Aceita termos
    await form.locator('label.terms input[type="checkbox"]').check();
}
