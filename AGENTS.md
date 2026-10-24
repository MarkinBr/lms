# AGENTS.md — CETSADP LMS

Este arquivo é o contrato de trabalho para **qualquer agente de IA** (Claude, Copilot, Cursor, Codex, etc.) e qualquer humano que for modificar este repositório. Se você é um agente de IA lendo isso: as regras abaixo não são sugestões, são o processo do projeto. Siga-as mesmo que ninguém peça explicitamente em cada tarefa.

## 1. Sobre o projeto

CETSADP é o LMS (Learning Management System) de uma instituição de ensino teológico, com três cursos: Teologia, Diaconato e Liderança Cristã Ministerial.

- **Backend**: Laravel (PHP), painel administrativo em Filament
- **Frontend**: Blade + Vite + Tailwind CSS v4 + JS "vanilla". O site público (`resources/views/site/`) é uma SPA client-side dentro de uma única view: 23 "páginas" (`<div class="page" data-page="...">`) trocadas via JS, sem framework de front-end
- **Banco**: PostgreSQL em produção, SQLite em testes
- **Infra**: Docker (VPS), fila via `queue:listen`, cache/Redis
- **Integrações**: Mercado Pago (pagamentos), SSO com sistema legado, geração de PDF (declarações/certificados), QR Code de validação

## 2. ⚠️ Estado atual e economia de contexto

O repositório **já usa Git e GitHub**, possui remoto configurado e workflow em `.github/workflows/ci.yml`. Preserve sempre as alterações existentes: não use `git reset --hard`, `git checkout .` ou limpezas amplas sem pedido explícito.

Para reduzir custo e tokens de agentes de IA:

- comece por `git status --short`, `git diff --stat` e buscas direcionadas;
- leia somente arquivos relacionados ao bug/tarefa;
- **não carregue** `vendor/`, `node_modules/`, `storage/`, `backups/`, `public/build/`, dumps ou logs inteiros;
- não analise cópias `.bak`/backup como fonte de verdade; o histórico fica no Git;
- antes de instalar pacote/plugin, confirme que o framework já não fornece a função;
- mantenha respostas de comandos concisas (falhas e resumo), ampliando só quando necessário.

As regras de exclusão também estão em `.cursorignore` e as orientações curtas do Copilot em `.github/copilot-instructions.md`.

## 3. Fluxo obrigatório: Issue → Branch → PR → Deploy

**Nenhuma mudança de código (correção, melhoria ou nova função) é feita sem uma Issue correspondente no GitHub.** Isso vale para agentes de IA e humanos.

### 3.1 Toda tarefa começa com uma Issue

- **Tipo `bug` (Correção)** — algo que já existe e está quebrado
- **Tipo `enhancement` (Melhoria)** — algo que já existe e pode ficar melhor
- **Tipo `feature` (Nova função)** — algo que não existe ainda

Título: `[tipo curto] descrição objetiva` — ex.: `Formulário de inscrição não envia dados ao backend`.
A issue deve ter contexto suficiente para alguém (ou um agente) sem memória da conversa entender o problema e os critérios de aceite.

### 3.2 Branch por Issue

```
fix/123-formulario-inscricao-nao-envia
feat/456-painel-cms-partials
chore/789-configurar-ci
```
O número é o da Issue. Sem branch dedicada, sem PR.

### 3.3 Commits

Convenção (Conventional Commits, verificado pelo Commitlint — ver seção 4):
```
fix: corrige action do formulário de inscrição (#123)
feat: adiciona painel de edição de partials (#456)
chore: configura pipeline de CI (#789)
```

### 3.4 Pull Request **sempre** menciona a Issue

A descrição do PR **precisa** conter uma palavra-chave de fechamento do GitHub apontando para a issue, para que o merge feche a issue automaticamente:

```
Closes #123
```
(`Closes` / `Fixes` / `Resolves` — qualquer uma funciona; use `Refs #123` se o PR só referencia a issue sem resolvê-la completamente.)

Template do PR já está em `.github/pull_request_template.md` e traz esse campo pronto — não apague.

### 3.5 Deploy é gerenciado por PR, não por push direto

- `main` é protegida — sem commit direto.
- Merge do PR em `main` dispara o workflow de CI (`.github/workflows/ci.yml`): lint + testes automatizados.
- O deploy para o VPS (Docker Compose, `entrypoint.sh`) hoje é assistido/manual — ver `docs/BACKLOG.md` (item de CI/CD) para o plano de automatizar isso com um job de deploy via SSH usando secrets do repositório.

## 4. Qualidade de código

| Camada | Ferramenta | O que faz |
|---|---|---|
| PHP — formatação | Laravel Pint (já é dependência do projeto) | Estilo de código PSR-12 |
| PHP — análise estática | PHPStan + Larastan | Erros de tipo, código morto, bugs prováveis |
| PHP — arquitetura | Deptrac | Impede que camadas (Controllers/Services/Models/Filament) violem as dependências permitidas |
| JS/CSS — lint e formatação | Biome | Substitui ESLint + Prettier num binário só |
| JS — código morto | Knip | Encontra exports, arquivos e dependências não usados |
| Mensagens de commit | Commitlint (+ Husky) | Garante Conventional Commits |
| PHP — mutation testing | Infection | Mede se os testes realmente pegam bugs (não só cobertura de linha) |
| JS — mutation testing | Stryker | Mesma ideia, para `public/js/site/*.js` |

A configuração efetivamente disponível no repositório é a fonte de verdade. Consulte `docs/QUALIDADE_E_OBSERVABILIDADE.md` antes de adicionar novas ferramentas; não instale toda a lista apenas por existir na documentação.

## 5. Testes

- **Unitário/Integração**: PHPUnit (`tests/Unit`, `tests/Feature` — já existem alguns testes, ver `phpunit.xml`)
- **E2E**: Playwright, cobrindo os fluxos públicos críticos (inscrição, login aluno, login secretaria, contato, newsletter)
- **Cobertura**: reportada ao Codecov no CI

Toda Issue do tipo `bug` que descreve um comportamento incorreto deve, sempre que possível, ganhar um teste que falha antes da correção e passa depois (regressão).

## 6. Observabilidade

Erros e desempenho em produção são monitorados via **Sentry** (erros/exceptions) e **OpenTelemetry** (tracing distribuído). Ver `docs/QUALIDADE_E_OBSERVABILIDADE.md` para o racional de não rodar Sentry + Datadog + New Relic simultaneamente (redundante e caro) e a recomendação de ferramenta principal.

## 7. Motion, loading e estados de interface

Todo elemento de interface que carrega dado de forma assíncrona precisa de: skeleton (estado de carregamento), lazy loading (quando fora do viewport inicial) e uma transição de entrada/saída **proporcional à frequência de uso** — não é "anime tudo igual". O racional completo e as classes utilitárias prontas estão em `docs/MOTION_GUIDELINES.md`. Baseado na skill pública `kylezantos/design-motion-principles`.

## 8. Convenções gerais

- Comentários e nomes de domínio (models, rotas, mensagens ao usuário) em **português**, seguindo o padrão já usado no projeto (`aluno`, `matricula`, `inscricao`, `secretaria`).
- Nomes de variáveis/funções de infraestrutura genérica podem ficar em inglês, se já for o padrão do arquivo.
- Nunca commitar `.env`, dumps de banco (`*.sqlite`, `*.sql`) ou credenciais. Ver item de limpeza em `docs/BACKLOG.md` — há um `cetsadp_lms` (banco SQLite) solto na raiz que não deveria estar aí.
- Antes de fechar uma Issue, rode: lint (Pint/Biome), testes (PHPUnit + Playwright quando aplicável) e confirme que o PR referencia a issue.

## 9. Onde encontrar cada coisa

- `docs/BACKLOG.md` — lista de Issues a criar/já criadas
- `docs/MOTION_GUIDELINES.md` — padrão de motion/loading (prompt 2)
- `docs/QUALIDADE_E_OBSERVABILIDADE.md` — lint, testes, observabilidade (prompt 3)
- `scripts/create-github-issues.sh` — cria as issues do backlog via `gh` CLI
- `.github/ISSUE_TEMPLATE/` — templates de Issue
- `.github/pull_request_template.md` — template de PR
- `.github/workflows/ci.yml` — pipeline de CI
