<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Telegram\ResumoAvisosTelegram;
use App\Services\Telegram\TelegramAdministrativo;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TelegramAvisosAutomaticos extends Command
{
    protected $signature = 'telegram:avisos-automaticos {--tipo=diario : diario ou semanal} {--simular : Não envia nem grava dados}';

    protected $description = 'Envia indicador agregado ao chat privado com opt-in, no máximo uma tentativa por período';

    public function handle(ResumoAvisosTelegram $resumos): int
    {
        $tipo = (string) $this->option('tipo');
        if (! in_array($tipo, ['diario', 'semanal'], true)) {
            $this->error('Tipo inválido. Utilize diario ou semanal.');

            return self::FAILURE;
        }

        if (! $this->option('simular') && ! (bool) config('services.telegram.avisos_automaticos', false)) {
            $this->info('Avisos automáticos desativados neste ambiente.');

            return self::SUCCESS;
        }

        $destino = $this->destinoAutorizado();
        if ($destino === null) {
            $this->info('Nenhum administrador autorizado, vinculado e com notificações ativadas.');

            return self::SUCCESS;
        }

        if ($this->option('simular')) {
            $this->info('Simulação aprovada para aviso '.$tipo.'. Nenhuma gravação nem chamada HTTP.');

            return self::SUCCESS;
        }

        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            $this->error('Token do Telegram indisponível neste processo.');

            return self::FAILURE;
        }

        $agora = CarbonImmutable::now('America/Sao_Paulo');
        $periodo = $tipo === 'diario'
            ? $agora->format('Y-m-d')
            : $agora->startOfWeek()->format('Y-m-d');
        $texto = $resumos->texto($tipo);

        // Reserva única global no banco, antes da rede: evita envio simultâneo e
        // duplicação após timeout ambíguo. A política é de no máximo uma tentativa.
        $registrado = DB::table('telegram_avisos_programados')->insertOrIgnore([
            'user_id' => $destino['usuario'],
            'tipo' => $tipo,
            'periodo' => $periodo,
            'status' => 'reservado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($registrado === 0) {
            $this->info('Aviso já reservado ou processado nesta janela.');

            return self::SUCCESS;
        }

        // Revalidar logo antes da chamada externa, sem reter payload no banco.
        if ($destino !== $this->destinoAutorizado()) {
            $this->finalizar($destino, $tipo, $periodo, 'bloqueado');

            return self::SUCCESS;
        }

        try {
            $resposta = Http::timeout(5)->post('https://api.telegram.org/bot'.$token.'/sendMessage', [
                'chat_id' => $destino['chat'],
                'text' => $texto,
            ]);
            $aceito = $resposta->successful() && $resposta->json('ok') === true;
        } catch (ConnectionException) {
            $aceito = false;
        }

        $this->finalizar($destino, $tipo, $periodo, $aceito ? 'aceito' : 'nao_confirmado');
        if (! $aceito) {
            $this->error('Telegram não confirmou a entrega. A janela não será repetida automaticamente.');

            return self::FAILURE;
        }

        $this->info('Aviso agregado aceito pelo Telegram.');

        return self::SUCCESS;
    }

    /** @return array{usuario: int, chat: string}|null */
    private function destinoAutorizado(): ?array
    {
        $id = filter_var(config('services.telegram.admin_user_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($id === false) {
            return null;
        }

        $usuario = User::find($id);
        if (! app(TelegramAdministrativo::class)->podeOperar($usuario)) {
            return null;
        }

        $ativo = DB::table('telegram_alerta_preferencias')->where('user_id', $id)->value('ativo');
        if (! (bool) $ativo) {
            return null;
        }

        $chat = DB::table('telegram_vinculos')->where('user_id', $id)->value('chat_id');
        if (! is_string($chat) || preg_match('/^[1-9][0-9]{0,18}$/D', $chat) !== 1) {
            return null;
        }

        return ['usuario' => (int) $id, 'chat' => $chat];
    }

    private function finalizar(array $destino, string $tipo, string $periodo, string $status): void
    {
        DB::table('telegram_avisos_programados')
            ->where('user_id', $destino['usuario'])->where('tipo', $tipo)
            ->where('periodo', $periodo)->update([
                'status' => $status,
                'finalizado_em' => now(),
                'updated_at' => now(),
            ]);
    }
}
