<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Telegram\CatalogoComandosTelegram;
use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TelegramPublicarComandos extends Command
{
    protected $signature = 'telegram:comandos-publicar {--simular : Apenas conferir o catálogo, sem chamar o Telegram}';

    protected $description = 'Publica os comandos no menu do chat privado do administrador vinculado';

    public function handle(TelegramAdministrativo $administrativo): int
    {
        $id = filter_var(config('services.telegram.admin_user_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $usuario = $id !== false ? User::find($id) : null;
        if (! $administrativo->podeOperar($usuario)) {
            $this->error('Administrador autorizado não configurado ou inativo.');

            return self::FAILURE;
        }

        $vinculo = DB::table('telegram_vinculos')->where('user_id', $usuario->id)
            ->whereNotNull('chat_id')->first();
        if ($vinculo === null || ! preg_match('/^[1-9][0-9]*$/D', (string) $vinculo->chat_id)) {
            $this->error('Não existe chat privado válido vinculado ao administrador.');

            return self::FAILURE;
        }

        $comandos = CatalogoComandosTelegram::listar();
        if ($this->option('simular')) {
            $this->info('Catálogo pronto: '.count($comandos).' comandos. Nenhuma chamada ao Telegram foi realizada.');

            return self::SUCCESS;
        }

        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            $this->error('Token do bot indisponível neste processo.');

            return self::FAILURE;
        }

        try {
            $resposta = Http::timeout(5)->post('https://api.telegram.org/bot'.$token.'/setMyCommands', [
                'commands' => $comandos,
                'scope' => ['type' => 'chat', 'chat_id' => (int) $vinculo->chat_id],
            ]);
        } catch (ConnectionException) {
            $this->error('Não foi possível publicar os comandos. Verifique a conectividade do serviço web.');

            return self::FAILURE;
        }

        if (! $resposta->successful() || $resposta->json('ok') !== true) {
            $this->error('O Telegram não confirmou a publicação dos comandos.');

            return self::FAILURE;
        }

        $this->info('Menu administrativo publicado para o chat vinculado.');

        return self::SUCCESS;
    }
}
