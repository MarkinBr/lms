# AGENTS.md — CETSADP LMS

Este arquivo é o **contrato de trabalho obrigatório** para **qualquer agente de IA** (Claude, Copilot, Cursor, Codex, etc.) e qualquer humano que for modificar este repositório. Se você é um agente de IA lendo isso: as regras abaixo não são sugestões, são o processo do projeto. Siga-as mesmo que ninguém peça explicitamente em cada tarefa.

---

## 1. Sobre o projeto

CETSADP é o LMS (Learning Management System) de uma instituição de ensino teológico, com três cursos: Teologia, Diaconato e Liderança Cristã Ministerial.

- **Backend**: Laravel (PHP), painel administrativo em Filament
- **Frontend**: Blade + Vite + Tailwind CSS v4 + JS "vanilla". O site público (`resources/views/site/`) é uma SPA client-side dentro de uma única view: 23 "páginas" (`<div class="page" data-page="...">`) trocadas via JS, sem framework de front-end
- **Banco**: PostgreSQL em produção, SQLite em testes
- **Infra**: Docker (VPS), fila via `queue:listen`, cache/Redis
- **Integrações**: Mercado Pago (pagamentos), SSO com sistema legado, geração de PDF (declarações/certificados), QR Code de validação

---

## 2. Fluxo Obrigatório: Issue → Branch → PR → Deploy

> **Regra de ouro:** Nenhuma mudança de código (correção, melhoria ou nova função) é feita sem uma Issue correspondente no GitHub. Isso vale para agentes de IA e humanos.

### 2.1 Toda tarefa começa com uma Issue no GitHub

Crie uma Issue para **toda** tarefa — seja Correção (`bug`), Melhoria (`enhancement`) ou Nova Função (`feature`).

| Label | Tipo | Quando usar |
|---|---|---|
| `bug` | Correção | Algo que já existe e está quebrado |
| `enhancement` | Melhoria | Algo que já existe e pode ficar melhor |
| `feature` | Nova função | Algo que não existe ainda |
| `chore` | Manutenção | Infra, config, dívida técnica |
| `epic` | Agrupador | Issue que contém várias sub-tarefas |

**Título:** `[tipo] descrição objetiva` — ex.: `Formulário de inscrição não envia dados ao backend`

**Conteúdo mínimo da Issue:**
- Contexto do problema/oportunidade
- Critérios de aceite claros (checklist)
- Referência a docs relevantes (ex.: `docs/MOTION_GUIDELINES.md`)

### 2.2 Branch por Issue

Crie uma branch dedicada para cada Issue:

```bash
fix/123-formulario-inscricao-nao-envia
feat/456-painel-cms-partials
chore/789-configurar-ci
```

O número é o da Issue. **Sem branch dedicada, sem PR.**

### 2.3 Commits (Conventional Commits)

Use o padrão Conventional Commits (verificado pelo Commitlint):

```bash
fix: corrige action do formulário de inscrição (#123)
feat: adiciona painel de edição de partials (#456)
chore: configura pipeline de CI (#789)
```

### 2.4 Pull Request **SEMPRE** menciona a Issue

A descrição do PR **precisa** conter uma palavra-chave de fechamento do GitHub apontando para a Issue:

```markdown
## Descrição
Implementa o envio real dos dados do formulário de inscrição para o backend.

## Mudanças
- Adiciona endpoint `site.inscricao`
- Corrige `name=` em todos os campos do formulário
- Remove `preventDefault` incondicional

## Checklist
- [ ] Testes unitários passando
- [ ] Lint (Pint/Biome) sem erros
- [ ] Playwright testando fluxo E2E

Closes #123
```

Palavras-chave válidas: `Closes`, `Fixes`, `Resolves` (fecham a Issue automaticamente). Use `Refs #123` se o PR só referencia sem resolver completamente.

### 2.5 Deploy gerenciado por PR

- **`main` é protegida** — sem commit direto, apenas via PR merge
- Merge do PR em `main` dispara o workflow de CI (`.github/workflows/ci.yml`): lint + testes automatizados
- Deploy para produção ocorre após merge em `main` (automatizado via GitHub Actions + SSH ou manual conforme configuração)

---

## 3. Motion & UI: Skill `kylezantos/design-motion-principles`

> **Referência:** https://github.com/kylezantos/design-motion-principles

Todas as interfaces do sistema devem seguir estes princípios de motion design, skeleton e loading states.

### 3.1 Princípios Fundamentais

#### Frequency Gate (Porta de Frequência)
Quanto mais frequente a interação, **menos** motion ela deve ter:

| Frequência | Exemplo no CETSADP | Motion permitido |
|---|---|---|
| Rara (mensal) | Site público, páginas institucionais | Motion expressivo (200–450ms) |
| Diária | Portal aluno, listagens | Motion sutil (<180ms) |
| Centenas/dia | Painel secretaria, tabelas | Sem animação ou instantâneo |
| Via teclado | Atalhos, navegação por keyboard | **Nunca** animar |

> **Regra de ouro:** "A melhor animação é aquela que passa despercebida." Se alguém comenta "que animação legal" a cada clique, está chamando atenção demais.

### 3.2 Requisitos Obrigatórios por Elemento

TODO elemento de interface que carrega dado de forma assíncrona DEVE ter:

1. **Skeleton** — estado de carregamento visual (shimmer effect)
2. **Lazy Loading** — carregamento sob demanda quando fora do viewport
3. **Smooth Animation** — transições de entrada/saída proporcionais à frequência
4. **Loading State** — indicador claro de progresso em ações assíncronas
5. **Progress Feedback** — feedback visual durante operações longas

### 3.3 Implementação Técnica

**CSS (motion.css):**
```css
/* Skeleton com shimmer */
.cs-skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

/* Entrada suave */
.cs-enter {
  opacity: 0;
  transform: translateY(8px);
  filter: blur(4px);
  transition: opacity 300ms ease, transform 300ms ease, filter 300ms ease;
}

.cs-enter.visible {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}

/* Respeita prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  .cs-enter, .cs-skeleton {
    animation: none;
    transition: none;
  }
}
```

**JavaScript (motion.js):**
```javascript
// IntersectionObserver para lazy loading e entrada ao rolar
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('[data-cs-zone]').forEach(el => observer.observe(el));
```

**HTML Pattern:**
```html
<!-- Skeleton -->
<div class="cs-skeleton" data-cs-skeleton-for="card-curso-1"></div>

<!-- Conteúdo real (hidden até carregar) -->
<div class="cs-enter" data-cs-zone="site" hidden>
  <!-- conteúdo -->
</div>
```

### 3.4 Zonas do Sistema

| Zona | Telas | Peso Primário | Duração Alvo |
|---|---|---|---|
| **Site público** | Home, Cursos, Eventos, Notícias | Jakub (polimento) | 200–450ms |
| **Formulários públicos** | Inscrição, Contato, Login | Emil (restrição) | <300ms |
| **Painel secretaria/aluno** | Dashboards, Tabelas, CRUDs | Emil (velocidade) | <180ms |

Ver `docs/MOTION_GUIDELINES.md` para detalhes completos.

---

## 4. Observabilidade, Qualidade e Testes

### 4.1 Observabilidade

| Ferramenta | Propósito | Status |
|---|---|---|
| **Sentry** | Error tracking (exceptions, erros JS) | A instalar |
| **OpenTelemetry** | Tracing distribuído (HTTP, queries lentas) | A instalar |
| ~~Datadog/New Relic~~ | Redundante se usar Sentry+OTel | Não usar juntos |

> **Nota:** Não instale Sentry + Datadog + New Relic simultaneamente — é redundante e caro. Sentry + OpenTelemetry cobre a necessidade.

**Configuração mínima:**
- Capturar exceptions PHP e erros JavaScript
- Trace de requests HTTP e queries >100ms
- Environment tags (prod/staging)

Ver `docs/QUALIDADE_E_OBSERVABILIDADE.md` para configs detalhadas.

### 4.2 Qualidade de Código (Lint & Static Analysis)

| Camada | Ferramenta | Propósito |
|---|---|---|
| PHP — format | **Laravel Pint** | Estilo PSR-12 |
| PHP — static analysis | **PHPStan + Larastan** | Erros de tipo, bugs |
| PHP — architecture | **Deptrac** | Contrato entre camadas |
| JS/CSS — lint+format | **Biome** | Substitui ESLint+Prettier |
| JS — dead code | **Knip** | Exports/arquivos não usados |
| Commits | **Commitlint + Husky** | Conventional Commits |
| PHP — mutation | **Infection** | Qualidade dos testes |
| JS — mutation | **Stryker** | Qualidade dos testes JS |

**Comandos essenciais:**
```bash
# PHP
composer pint          # Format
composer phpstan       # Static analysis
composer deptrac       # Architecture check

# JS
npx biome check .      # Lint
npx biome format .     # Format
npx knip               # Dead code
```

### 4.3 Testes Automatizados

| Tipo | Ferramenta | Cobertura |
|---|---|---|
| Unitário/Integração | **PHPUnit** | Models, Services, Controllers |
| E2E | **Playwright** | Fluxos críticos (inscrição, login, contato) |
| Coverage Report | **Codecov** | Meta definida pelo time |

**Regra:** Toda Issue do tipo `bug` deve ganhar um teste de regressão que falha antes da correção e passa depois.

**Comandos:**
```bash
composer test              # PHPUnit
npx playwright test        # E2E
```

---

## 5. Convenções Gerais

- **Idioma:** Comentários, nomes de domínio (models, rotas, mensagens) em **português** (`aluno`, `matricula`, `inscricao`)
- **Variáveis de infra:** Podem ficar em inglês se já for padrão do arquivo
- **Nunca commitar:** `.env`, `*.sqlite`, `*.sql`, credenciais, backups
- **Antes de fechar Issue:** Rode lint (Pint/Biome), testes (PHPUnit/Playwright), confirme PR referencia Issue

---

## 6. Estrutura de Documentação

| Arquivo | Propósito |
|---|---|
| `AGENTS.md` (este) | Contrato de processo para agentes/humanos |
| `BACKLOG.md` | Lista de Issues a criar/já criadas |
| `MOTION_GUIDELINES.md` | Padrão de motion/loading/UI |
| `QUALIDADE_E_OBSERVABILIDADE.md` | Configs de lint, testes, observabilidade |
| `.github/ISSUE_TEMPLATE/` | Templates de Issue |
| `.github/pull_request_template.md` | Template de PR |
| `.github/workflows/ci.yml` | Pipeline de CI/CD |

---

## 7. Resumo Rápido para Agentes de IA

Ao receber uma tarefa:

1. **Verifique se existe Issue** no GitHub para esta tarefa
   - Se não existir: **CRIE UMA ISSUE** antes de codificar
   
2. **Crie branch** com nome padrão: `tipo/numero-descricao`

3. **Desenvolva** seguindo:
   - Motion Principles (skeleton, lazy loading, animações apropriadas)
   - Qualidade (lint, tests, observabilidade)

4. **Commite** com Conventional Commits: `tipo: mensagem (#numero)`

5. **Abra PR** mencionando a Issue: `Closes #numero`

6. **Aguarde revisão** e merge em `main`

7. **Deploy** ocorre automaticamente ou conforme pipeline

---

*Última atualização: 2025 — Processo baseado nos prompts de definição do projeto.*
