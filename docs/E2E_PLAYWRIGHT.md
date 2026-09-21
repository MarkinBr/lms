# Testes E2E com Playwright — site público

Este documento descreve como instalar e executar os E2E do site
público do CETSADP, introduzidos pela Issue #27.

## Escopo

Os E2E cobrem apenas fluxos públicos críticos, todos na mesma SPA
Blade (`resources/views/site/index.blade.php`) que troca páginas via
`data-page` / `data-go`:

1. **Navegação legal** — links de "termos e condições" e "política de
   privacidade" do formulário de inscrição e do rodapé.
2. **Inscrição em mais de um curso** — o botão "ADICIONAR OUTRO CURSO",
   o bloqueio de repetição do mesmo curso, a remoção de linhas e o
   limite máximo de três cursos.
3. **Erro de validação no envio** — o comportamento visível quando
   `POST /api/v1/applications/batch` retorna 422.
4. **Acompanhamento** — link do formulário de inscrição para a tela
   "Acompanhar Inscrição".

Os testes **não** enviam inscrição, e-mail, WhatsApp ou qualquer outro
dado real. As chamadas HTTP relevantes (`GET /api/v1/cursos` e
`POST /api/v1/applications/batch`) são interceptadas por
`page.route(...)` com fixtures locais (`tests/e2e/fixtures/`).

## Requisitos

- PHP 8.2+ e Composer (para o `php artisan serve`)
- Node.js 18+ e npm
- Uma instância local do LMS já configurada (`.env`, `key:generate`,
  `migrate`), suficiente para responder `/` — os controllers reais
  de aplicação nunca são exercitados

## Instalação

```bash
npm install
npx playwright install --with-deps chromium
```

O `--with-deps` só é necessário na primeira execução; em CI ele instala
as libs de sistema do Chromium.

## Execução

Modo padrão (o Playwright sobe o servidor Laravel local para você):

```bash
npm run test:e2e
```

Isso executa `php artisan serve --host=127.0.0.1 --port=8000` apenas
durante os testes e o encerra ao final. Se a porta 8000 já estiver
ocupada por um servidor seu, o Playwright **reutiliza** o processo
existente (`reuseExistingServer: true`) — nada é matado.

Se você prefere gerenciar o servidor manualmente:

```bash
# terminal 1
php artisan serve
# terminal 2
E2E_REUSE_SERVER=1 npm run test:e2e
```

Para apontar para outro host/porta (por exemplo `php artisan serve`
em 8080 ou um Sail):

```bash
E2E_BASE_URL=http://127.0.0.1:8080 npm run test:e2e
```

Modo interativo, útil ao investigar uma quebra:

```bash
npm run test:e2e:ui
```

Após uma execução, o relatório HTML fica disponível em:

```bash
npm run test:e2e:report
```

## Estrutura dos arquivos

```
playwright.config.ts                     # configuração da suíte
tests/e2e/
├── fixtures/
│   └── cursos.ts                        # catálogo mocado
├── support/
│   └── helpers.ts                       # utilidades compartilhadas
├── navegacao-legal.spec.ts              # cenário 1
├── inscricao-multiplos-cursos.spec.ts   # cenário 2
├── inscricao-erro-validacao.spec.ts     # cenário 3
├── acompanhamento.spec.ts               # cenário 4
└── .gitignore                           # ignora .artifacts/ e .report/
```

## Convenções de escrita

- Prefira seletores por `id` já existente (`#inscForm`, `#inscAddCurso`,
  `#acompProtocolo`, ...) e por texto visível. Nunca dependa de classes
  de estilo, ordem no DOM ou detalhes de layout.
- Aguarde estados observáveis (respostas de rede, `data-page` ativo,
  `toast.show`). Não use `waitForTimeout` fixo.
- Toda chamada HTTP que sairia para produção precisa ser interceptada
  ou explicitamente bloqueada por `blockRealNetwork()`.
- Nenhum dado pessoal real: fixtures usam `Fulano de Tal`,
  `fulano@example.test`, `000.000.000-00`, etc.

## O que NÃO é coberto aqui

Os fluxos de login do aluno e da secretaria, contato e newsletter estão
listados no `AGENTS.md` como candidatos a E2E, mas ficam fora do escopo
da Issue #27. Serão adicionados em issues próprias, reaproveitando a
mesma configuração e helpers.
