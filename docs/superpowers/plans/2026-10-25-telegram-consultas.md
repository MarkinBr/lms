# Telegram menus e consultas — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans. Steps use checkbox syntax.

**Goal:** Entregar menus inline e consultas acadêmicas paginadas, exclusivas ao administrador do bot.
**Architecture:** Serviço separado `ConsultasAcademicasTelegram` faz queries exclusivamente de leitura e devolve texto mais teclado; adaptador `TelegramAdministrativo` valida chat/dono e despacha mensagem ou callback; webhook aceita callback_query.
**Tech Stack:** PHP 8.2+, Laravel 12, HTTP fake, PHPUnit, SQLite.
**Spec:** `docs/superpowers/specs/2026-10-25-telegram-consultas-design.md`.

## Global Constraints
- Base `680c462`; nunca alterar vinculação, VPS, banco real, sistema legado nem PR #85.
- Máximo cinco registros por consulta; páginas limitadas; proibir informação pessoal além do nome e identificador acadêmico.
- Somente administrador dono ativo e chat privado; no commands SQL ou escrita de dados.

## Review Focus
1. Callback após revogação/inativação: recusar.
2. Callback em grupo ou com `from.id` diferente: não responder.
3. Página malformada, categoria arbitrária ou ID inválido: não executar consultas não previstas.
4. Update repetido: não mandar duas vezes.
5. Falha Telegram: manter tratamento atual 503/reprocessamento.

### Task 1 — Testes e serviço de consulta
**Files:** Create `tests/Feature/TelegramMenuConsultaTest.php` and `app/Services/Telegram/ConsultasAcademicasTelegram.php`.
- [ ] Write feature tests for menu, pagination, restricted details and denial paths.
- [ ] Run test and confirm failure due to absent behavior.
- [ ] Implement query service with fixed allowlists and bounded inputs.
- [ ] Run tests to green.

### Task 2 — Adaptador e webhook
**Files:** Modify `app/Services/Telegram/TelegramAdministrativo.php`; `app/Http/Controllers/TelegramWebhookController.php`.
- [ ] Ensure red tests cover callback authorization, groups, repeated update, invalid search.
- [ ] Implement callback dispatcher, reply_markup support, and command routes without changing pairing.
- [ ] Run tests to green.

### Task 3 — Documentation, verification, PR
**Files:** Modify `docs/TELEGRAM_ADMINISTRATIVO.md`.
- [ ] Document available menu and privacy limitations.
- [ ] Run Pint and targeted tests in isolated CI; report results or blocked verification.
- [ ] Open draft PR based on `feat/84-portal-paralelo-migracao` with `Refs #86`; no deploy.
