# Etapa 3.5 Telegram — projeto e plano

> **For agentic workers:** Use superpowers:executing-plans; test-first and review each step.

**Goal:** Ampliar consultas sem escrita acadêmica, preservando exclusividade do dono e PRs anteriores.
**Architecture:** Serviço de leitura separado reutiliza Eloquent e PainelAcademicoQuery; adaptador Telegram valida identidade; notificações opt-in só após implantação controlada.
**Tech Stack:** Laravel 12, PHPUnit SQLite, PostgreSQL piloto isolado posteriormente, Telegram Bot API.
**Spec:** Issue #90.

## Constraints
Sem alterações de dados acadêmicos, segredos, sistema legado, Docker ou VPS. Base PR #89, sem merge; sem serviços pagos.

## Approaches
1. Reescrever usando SDK: desnecessário agora.
2. Usar Laravel existente, consultas SQL parametrizadas, páginas de cinco e callbacks estritas: selecionado.
3. Criar plataforma paralela: descartado.

## Tasks
### 1. Testes de novas consultas
- [ ] Escrever testes de /hoje, /agenda7, /aulaspendentes, /relatorios, /filtro e busca literal.
- [ ] Confirmar falha antes da implementação.
- [ ] Implementar funções agregadas e filtradas; conferir cinco registros por página.
- [ ] Testar resultados e ausência de campos pessoais.

### 2. Navegação
- [ ] Testar callback de ficha, anterior/próximo e menu, incluindo revogação.
- [ ] Atualizar menu e handler; usar editMessageText e answerCallbackQuery para navegação, mantendo fallback de envio seguro.
- [ ] Revalidar dono em cada interação e limitar callback.

### 3. Alertas: arquitetura segura
- [ ] Documentar preferências opt-in e limitações. Scheduler real continua desativado.
- [ ] A implementação de envios automáticos fica bloqueada até existir outbox, idempotência e saída de rede aprovada, sem alterar infraestrutura nesta PR.

### 4. Verificação
- [ ] PHPUnit, Pint, CI e Playwright isolados.
- [ ] PR draft dependente #89; homologação PostgreSQL isolado ainda necessária.
