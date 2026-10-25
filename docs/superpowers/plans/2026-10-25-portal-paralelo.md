# Portal paralelo e migração do legado — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans; tarefas independentes podem ser delegadas com escopo de escrita separado. Steps use checkbox syntax for tracking.

**Goal:** Entregar candidata integrada e homologação privada com importação idempotente e acesso por matrícula.
**Architecture:** Laravel continua como alvo; conexão separada somente leitura lê a origem e mapa persistente vincula IDs. Autenticação resolve matrícula/CPF/e-mail confirmado sem ambiguidade. Homologação usa PostgreSQL restaurado e serviços isolados, sem substituir produção.
**Tech Stack:** Laravel 12, PHP 8.4 em Linux, Blade, PostgreSQL 16, SQLite em testes, Docker Compose, PHPUnit/Playwright.
**Spec:** docs/superpowers/specs/2026-10-25-portal-paralelo-design.md

## Global Constraints
- Legado permanece em funcionamento; sem alteração de DNS ou publicação pública nesta entrega.
- Dados pessoais e dumps permanecem na VPS; fixtures locais são sintéticas.
- Sem serviços pagos, senha previsível, e-mail fictício ou sobrescrita de conflitos.
- Homologação não envia mensagens, pagamentos ou Telegram; storage/cookies/filas separados.

## Review Focus
- Identificador que encontra duas contas: negar login, nunca escolher a primeira.
- Alteração do e-mail e link antigo: manter matrícula e impedir confirmação/redefinição indevida.
- Importação repetida após edição local: reportar conflito e preservar edição.
- Vínculos e UUIDs do legado: reconciliar por identidade, sem somar duplicados nem converter UUID para inteiro.
- Migração falha ou configuração de produção acidental: abortar homologação e manter produção intacta.

### Task 1: Candidata e baseline
Files: spec/plano, dependências e apresentações selecionadas.
- [x] Criar worktree da sala atual, branch feat/84-portal-paralelo-migracao e integrar feat/82-telegram-bot-etapa1.
- [ ] Copiar apenas apresentações locais selecionadas sem modificar origem; preparar dependências e configuração de teste própria.
- [ ] Rodar baseline PHPUnit; registrar falhas existentes por nome e causa antes de novos testes.

### Task 2: Matrícula e e-mail confirmado
Files: app/Services/Auth/IdentificadorAluno.php; app/Http/Controllers/Auth/AlunoLoginController.php; app/Http/Controllers/Auth/RedefinirSenhaController.php; app/Http/Controllers/Aluno/EmailAlunoController.php; app/Models/User.php; app/Http/Controllers/Aluno/PerfilController.php; routes/web.php; views de login/perfil/e-mail; notification; migration 2026_10_25_000003_preparar_acesso_alunos_legados.php; tests/Feature/Auth/*.
Interfaces: users.matricula_login nullable unique (valor normalizado em minúsculas), users.email nullable; email_confirmacao_pendente e token SHA256/expiração para confirmação; email_verified_at indica somente endereço confirmado. Importador grava matricula_login e hash original diretamente, sem cast hashed.
- [ ] Escrever e observar falhar testes de login por matrícula sem e-mail, CPF, ambiguidade, inativo/admin, hash bcrypt Node, e-mail não confirmado e limitação de tentativas.
- [ ] Implementar resolução sem ambiguidade, preservando compatibilidade de payload email anterior e layout.
- [ ] Escrever e observar falhar testes de cadastro/troca de e-mail autenticado com senha recente, confirmação expirada/repetida/inválida e matrícula após troca; recuperar senha somente por e-mail confirmado.
- [ ] Implementar confirmação com envio testável via Mail/Notification fake, mantendo aulas acessíveis sem e-mail e orientação à secretaria.
- [ ] Rodar testes Auth e Pint nos arquivos alterados. Commit com (#84).

### Task 3: Importador com prévia, mapas e histórico
Files: config/legado.php; app/Services/Legado/*; app/Console/Commands/ImportarPortalLegado.php; migration 2026_10_25_000004_create_legado_mapeamentos_tables.php; tests/Feature/Legado/ImportarPortalLegadoTest.php.
Interfaces: comando portal:importar-legado --origem=legado --executar (sem flag, somente prévia); destino é conexão padrão isolada; LegadoImportador::executar(string $origem, bool $aplicar): array retorna contagens e conflitos sem PII. legado_mapeamentos possui origem/tabela/id_origem/tabela_destino/id_destino/impressao_destino; users.matricula_login conforme Task 2.
- [ ] Criar fixtures sintéticas em conexão SQLite separada e testes falhando: prévia não grava, segunda execução não duplica, conserva hash/matrícula, origem intocada, edição local e CPF/e-mail colidentes viram conflito.
- [ ] Implementar cursos/módulos/disciplinas de transição, alunos/professores, vínculos reconciliados por aluno/curso, aulas e materiais com origem explícita.
- [ ] Arquivar histórico em tabela restrita com origem e payload filtrado de credenciais, sem convertê-lo em notas/frequência oficial. Não disponibilizar histórico bruto por rota pública.
- [ ] Validar identidade da conexão, permitir execução apenas testing/homologacao ou configuração explícita de ambiente; bloquear mesma conexão/origem e produção nesta entrega.
- [ ] Rodar fixtures/previsão/reexecução e Pint; commit (#84).

### Task 4: Release e serviços isolados
Files: Dockerfile; .dockerignore; entrypoint.sh; docker/portal-homologacao.compose.yml; scripts/portal-homologacao.py; tests de scripts com unittest.
Interfaces: build usa somente git archive HEAD e apresentações selecionadas; compose namespaced, portas somente 127.0.0.1 e rede interna, bancos restaurados em cópias. Imagem web/worker igual; storage persistente compartilhado; mail array, queue database dedicada, Telegram desativado.
- [ ] Testar scripts com entradas sintéticas: rejeitar destino produção, credenciais ausentes e falha de migração; nenhum docker prune/down -v.
- [ ] Implementar npm ci/composer lock e exclusões de segredos, dumps e contextos locais; falha de migrate encerra entrypoint.
- [ ] Criar serviços privados com limites CPU/RAM, config privada gerada na VPS, sem copiar env produção ao computador.
- [ ] Validar compose resolvido sem imprimir segredos, worker/scheduler e armazenamento; commit (#84).

### Task 5: Ensaio completo e revisão
Files: docs/PORTAL_TRANSICAO.md; evidências privadas em storage/app.
- [ ] Construir imagem limpa na VPS, restaurar os dois bancos em isolamento e migrar apenas a cópia.
- [ ] Rodar importação prévia, execução e repetição; conferir contagens e projeções da origem, sem exportar registros.
- [ ] Validar arquivos e login sintético, isolamento entre alunos/cursos, PDF/caderno, confirmação de e-mail com captura local; testes PHPUnit, build e Playwright quando aplicável.
- [ ] Revisão independente da branch e correções de riscos reais, cada correção com regressão.
- [ ] Criar PR draft com Refs #84, evidências e limites; anexar ao chat. Entregar acesso privado por túnel SSH sem exigir comandos manuais ao usuário. Não publicar ou desligar legado.
