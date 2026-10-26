# Avisos automáticos — Implementation Plan

> For agentic workers: use test-driven-development and verification-before-completion.

**Goal:** Avisos diários e resumo semanal automáticos opt-in, segregados da outbox interativa, desativados por padrão.
**Architecture:** Comando telegram:avisos-automaticos com --tipo, --simular. Serviço compõe agregados; tabela de deduplicação exclusiva por usuário/tipo/período, reserva atômica e entrega única. Scheduler em routes/console.php protegido pela flag.
**Tech Stack:** Laravel 12, PHP 8.4, SQLite e PostgreSQL.
**Spec:** docs/superpowers/specs/2026-10-26-telegram-avisos-automaticos-design.md.

- [ ] Criar testes feature (simulação, opt-in e dono, dedupe, falha, janela, ausência de PII) e confirmar RED em CI.
- [ ] Criar migração dedupe, serviço/console e flag de config.
- [ ] Registrar scheduler com flag off por padrão, documentar rollout/manual.
- [ ] Confirmar CI no SHA final; PR draft stacked no #117 sem alterações em VPS.
