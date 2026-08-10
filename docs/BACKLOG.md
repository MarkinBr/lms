# Backlog inicial — pronto para virar Issues no GitHub

Gerado a partir de uma auditoria real do código em `/lms` (não são suposições — cada item abaixo foi confirmado lendo o código-fonte). Rode `scripts/create-github-issues.sh` para criar todas de uma vez via `gh` CLI, ou copie manualmente pela interface do GitHub.

Cada bloco abaixo = uma Issue. `tipo` = label.

---

### 1. [chore] Inicializar Git e criar repositório no GitHub
**Por quê:** o projeto não tem `.git` nem repositório remoto hoje. Pré-requisito para tudo o resto neste backlog.
**Critérios de aceite:**
- [ ] `git init`, `.gitignore` revisado (ver item 2 antes de commitar)
- [ ] Repositório criado no GitHub (privado, salvo decisão em contrário)
- [ ] Branch `main` protegida (exige PR, sem push direto)
- [ ] Labels `bug`, `enhancement`, `feature` criados

---

### 2. [chore] Limpar artefatos soltos na raiz do projeto
**Por quê:** a raiz tem ~20 arquivos vazios com nomes como `default(true)`, `cascadeOnDelete()`, `string('titulo')` — fragmentos de código de migration que viraram arquivo por erro de shell (aspas/parênteses não escapados ao rodar um comando no terminal). Também há um arquivo `adp-lms php artisan cache_clear` (nome de comando, não de arquivo) e um `)` solto.
**Também neste item:** `cetsadp_lms` na raiz é na verdade **um banco SQLite** (confirmado via `file`), não deveria estar versionado nem fora de `database/`. Verificar se contém dado real de aluno antes de decidir: apagar, mover para `database/`, ou adicionar ao `.gitignore`.
**Critérios de aceite:**
- [ ] Arquivos-fragmento removidos
- [ ] `cetsadp_lms` tratado (ver acima) e `*.sqlite` no `.gitignore`
- [ ] Confirmar que `backups/` também está no `.gitignore` (pode conter dado sensível)

---

### 3. [bug] Formulários públicos não enviam dados ao backend (modo demonstração)
**Por quê:** confirmado em `public/js/site/app.js` — os 5 formulários do site (`inscForm`, `loginSecForm`, `loginAluForm`, `nlForm`, `contactForm`) têm `e.preventDefault()` incondicional no submit e só mostram um toast. Nenhum chega a fazer o POST para o Laravel, mesmo quando a rota já existe (`site.contato` e `site.newsletter` já existem em `routes/web.php`).
**Agravante no formulário de inscrição especificamente:** o `<form>` usa `action="{{ route('site.contato') }}"` — ou seja, mesmo se o `preventDefault` for removido, a inscrição cairia no endpoint de contato, não em um endpoint de matrícula. Dos campos do formulário, só `email` tem atributo `name`; o select de curso (`#inscCourseSel`) não tem `name` nenhum.
**Critérios de aceite:**
- [ ] Criar rota/controller dedicados para inscrição (`site.inscricao`, não reaproveitar `site.contato`)
- [ ] Adicionar `name=` em todos os campos do formulário de inscrição
- [ ] Trocar `preventDefault` fixo por submit real (via `fetch`/AJAX, mantendo o toast como feedback de sucesso/erro) nos 5 formulários
- [ ] `loginSecForm`/`loginAluForm`: ligar à autenticação real (hoje simula "acesso concedido" sem checar credencial)
- [ ] Teste Playwright cobrindo o envio real de cada formulário (ver `docs/QUALIDADE_E_OBSERVABILIDADE.md`)

---

### 4. [decision] Decidir arquitetura do novo frontend
**Por quê:** em aberto — o novo frontend (31 arquivos, SPA client-side dentro do Blade) deve **substituir** `resources/views/site/` em produção, ou funcionar como **camada adicional**? Isso muda a estratégia de todo o resto do backlog de UI (item 7 principalmente).
**Critérios de aceite:**
- [ ] Decisão registrada aqui ou em ADR próprio
- [ ] Issues de UI (motion, formulários) atualizadas para refletir a decisão

---

### 5. [feature] Painel administrativo (CMS) para os partials do site
**Por quê:** continuação do trabalho já em andamento — editar conteúdo de `resources/views/site/partials` e `pages` sem mexer em código.
**Critérios de aceite:**
- [ ] Mapear quais blocos de `dynamic-data.blade.php` já são "CMS-ready" (lidos de `window.CETSADP_CONTENT`) vs. hardcoded
- [ ] CRUD no Filament para o conteúdo editável
- [ ] Preview antes de publicar (já existe `filament/components/site-preview.blade.php` — verificar se está em uso)

---

### 6. [epic][feature] Motion, skeleton e lazy loading em toda a interface
Ver `docs/MOTION_GUIDELINES.md` para o racional completo e as classes utilitárias já implementadas como piloto. Quebrar em sub-issues por área ao iniciar cada uma:
- [ ] 6a. Site público (`site/pages/*`) — skeleton + fade-in nos cards de curso, eventos, notícias
- [ ] 6b. Formulários públicos — estado de loading no submit (depende da issue 3)
- [ ] 6c. Painel secretaria (`site/app/sec-*`) — skeleton em tabelas/listagens, sem exagero de transição (uso frequente)
- [ ] 6d. Portal aluno (`site/app/dash-aluno`, `aluno/*`) — mesmo critério do 6c

---

### 7. [epic][chore] Observabilidade (Sentry + OpenTelemetry)
Ver `docs/QUALIDADE_E_OBSERVABILIDADE.md`.
- [ ] 7a. Instalar e configurar `sentry/sentry-laravel`
- [ ] 7b. Instrumentar tracing básico com OpenTelemetry (requests HTTP + queries lentas)
- [ ] 7c. Decidir se Datadog/New Relic entram também ou se Sentry+OTel cobre a necessidade (ver racional no doc — rodar os três juntos é redundante)

---

### 8. [epic][chore] Qualidade de código (PHP + JS)
Ver `docs/QUALIDADE_E_OBSERVABILIDADE.md` para configs prontas.
- [ ] 8a. Pint no CI (já é dependência, só falta configurar + rodar)
- [ ] 8b. PHPStan/Larastan (análise estática — não existe hoje)
- [ ] 8c. Deptrac (contrato de arquitetura entre camadas)
- [ ] 8d. Biome (lint + format JS/CSS)
- [ ] 8e. Knip (código morto JS)
- [ ] 8f. Commitlint + Husky (Conventional Commits)

---

### 9. [epic][chore] Testes automatizados e cobertura
- [ ] 9a. Elevar cobertura PHPUnit dos módulos críticos (hoje só há `CpfValidationTest`, `SsoControllerTest`, `PatchValidationTest` e os exemplos padrão do Laravel)
- [ ] 9b. Playwright para os fluxos públicos (inscrição, contato, newsletter, login aluno, login secretaria)
- [ ] 9c. Codecov conectado ao CI, com meta mínima de cobertura combinada com o time
- [ ] 9d. Infection (PHP) / Stryker (JS) como mutation testing — rodar sob demanda, não em todo PR (é lento)

---

### 10. [chore] CI/CD no GitHub Actions
- [ ] 10a. Workflow de CI: lint + testes em toda PR (`.github/workflows/ci.yml` — já criado como ponto de partida, ajustar conforme os itens 8/9 forem sendo instalados)
- [ ] 10b. Job de deploy (SSH para o VPS + `docker compose up -d --build`) disparado em merge para `main`, usando Secrets do repositório para host/chave SSH
