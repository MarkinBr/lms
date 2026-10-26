# Central de gestao Telegram - Implementation Plan

> For agentic workers: REQUIRED SUB-SKILL superpowers:test-driven-development and verification-before-completion.

**Goal:** Implementar sete novas superficies administrativas sem envio automatico.
**Architecture:** CentralGestaoTelegram faz consultas somente leitura; TelegramAdministrativo roteia mensagens e callback; catalogo e menu exibem comandos; PreferenciasAlertasTelegram guarda configuracao.
**Tech Stack:** Laravel 12, PHP 8.4, SQLite/PostgreSQL 16.
**Spec:** docs/superpowers/specs/2026-10-26-telegram-central-gestao-design.md.

## Restrições
Sem deploy, banco real, segredos ou escrita academica.

## Tarefas
- [ ] Tests Feature/TelegramCentralGestaoTest: validar todos os sete comandos, callback, limites, negacao; executar CI RED.
- [ ] Criar app/Services/Telegram/CentralGestaoTelegram.php para consultas com limites.
- [ ] Adaptar TelegramAdministrativo, ConsultasAcademicasTelegram menu e CatalogoComandosTelegram; atualizar CI.
- [ ] Rodar CI GREEN e corrigir regressoes no SHA final.
- [ ] Criar issue subsequente para avisos automáticos opt-in seguros, dependente de retencao/outbox e validacao.
