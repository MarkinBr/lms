<?php

/**
 * CETSADP v9.3.5 — Console Routes / Scheduled Tasks
 *
 * CORREÇÃO D02: O agendamento foi reportado como entregue via Kernel.php na v9.3.2,
 * mas NÃO EXISTIA. No Laravel 12, scheduling é configurado em routes/console.php.
 *
 * Tarefas agendadas:
 *   1. sso:cleanup-tokens  — Limpeza de tokens SSO expirados (a cada hora)
 *   2. queue:prune-failed   — Limpar jobs falhos com mais de 7 dias (diário)
 *   3. sanctum:prune-expired — Limpar tokens Sanctum expirados (diário)
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// === Comando padrão do Laravel ===
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// === AGENDAMENTOS CETSADP v9.3.5 ===

// 1. Limpeza de tokens SSO expirados — a cada hora
Schedule::command('sso:cleanup-tokens')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sso-cleanup.log'));

// 2. Limpar jobs falhos antigos — diário às 02:00
Schedule::command('queue:prune-failed --hours=168') // 7 dias
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue-prune.log'));

// 3. Limpar tokens Sanctum expirados — diário às 03:00
Schedule::command('sanctum:prune-expired')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// 4. Otimização do banco (VACUUM ANALYZE) — semanal domingo 04:00
Schedule::command('db:vacuum')
    ->weeklyOn(0, '04:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/db-vacuum.log'));

// === MENSALIDADES (Auditoria v2026.06) ===

Schedule::command('mensalidades:atualizar-vencidas')
    ->dailyAt('00:30')->withoutOverlapping()->runInBackground()
    ->appendOutputTo(storage_path('logs/mensalidades-vencidas.log'));

Schedule::command('mensalidades:gerar-recorrentes')
    ->monthlyOn(25, '03:00')->withoutOverlapping()->runInBackground()
    ->appendOutputTo(storage_path('logs/mensalidades-recorrentes.log'));

Schedule::command('mensalidades:cancelar-orfas')
    ->dailyAt('02:00')->withoutOverlapping()->runInBackground()
    ->appendOutputTo(storage_path('logs/mensalidades-orfas.log'));

// === COBRANÇAS AUTOMÁTICAS (Auditoria v2026.06) ===

Schedule::command('lembretes:disparar-vencimento --dias=3')
    ->dailyAt('09:00')->withoutOverlapping()->runInBackground()
    ->appendOutputTo(storage_path('logs/lembretes.log'));

Schedule::command('cobrancas:disparar-atrasadas')
    ->dailyAt('10:00')->withoutOverlapping()->runInBackground()
    ->appendOutputTo(storage_path('logs/cobrancas.log'));
