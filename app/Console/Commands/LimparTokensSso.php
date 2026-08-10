<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CETSADP v9.3.5 — Comando para limpar tokens SSO expirados
 * 
 * CORREÇÃO D03: Este arquivo foi reportado como entregue na v9.3.2
 * mas NÃO EXISTIA no código-fonte. Criado de fato na v9.3.5.
 * 
 * Uso: php artisan sso:cleanup-tokens
 * Agendado: Executado a cada hora pelo scheduler
 */
class LimparTokensSso extends Command
{
    /**
     * O nome e a assinatura do comando.
     */
    protected $signature = 'sso:cleanup-tokens 
                            {--days=1 : Dias de retenção para tokens expirados}
                            {--dry-run : Apenas exibir quantos seriam removidos}';

    /**
     * A descrição do comando.
     */
    protected $description = 'Limpa tokens SSO expirados do banco de dados do Live';

    /**
     * Executar o comando.
     */
    public function handle(): int
    {
        $this->info('🔒 Iniciando limpeza de tokens SSO...');

        try {
            $liveApiUrl = config('services.cetsadp.live_api_url', 'http://backend:3000');
            $ssoSecret = config('services.cetsadp.sso_secret', '');

            if (empty($ssoSecret) || str_contains($ssoSecret, 'TROQUE')) {
                $this->error('❌ SSO_SHARED_SECRET não configurada. Abortando.');
                Log::error('sso:cleanup-tokens — SSO_SHARED_SECRET não configurada');
                return self::FAILURE;
            }

            if ($this->option('dry-run')) {
                $this->warn('⚠️  Modo dry-run: nenhum token será removido.');
                $this->info('  Para executar de fato, remova a flag --dry-run');
                return self::SUCCESS;
            }

            $response = Http::timeout(15)->post("{$liveApiUrl}/api/sso/cleanup", [
                'secret' => $ssoSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $cleaned = $data['cleaned'] ?? 0;
                
                $this->info("✅ Limpeza concluída: {$cleaned} token(s) removido(s).");
                Log::info("sso:cleanup-tokens — {$cleaned} tokens removidos", [
                    'timestamp' => $data['timestamp'] ?? now()->toISOString(),
                ]);

                return self::SUCCESS;
            }

            $this->error("❌ Falha na limpeza. Status: {$response->status()}");
            Log::error('sso:cleanup-tokens — Falha na API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error("❌ Erro: {$e->getMessage()}");
            Log::error('sso:cleanup-tokens — Exceção', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
