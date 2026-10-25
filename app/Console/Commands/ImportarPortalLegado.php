<?php

namespace App\Console\Commands;

use App\Services\Legado\LegadoImportador;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ImportarPortalLegado extends Command
{
    protected $signature = 'portal:importar-legado {--origem=legado} {--executar : Grava somente no destino de homologação}';

    protected $description = 'Confere ou importa o legado sem alterar a origem; execução limitada à homologação.';

    public function handle(LegadoImportador $importador): int
    {
        try {
            $resultado = $importador->executar((string) $this->option('origem'), (bool) $this->option('executar'));
        } catch (InvalidArgumentException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        } catch (Throwable) {
            // Exceções SQL podem conter documentos e hashes nos parâmetros.
            $this->error('Falha na conferência ou importação. Os registros desta execução não foram mantidos. Confira a estrutura e a conexão sem divulgar credenciais.');

            return self::FAILURE;
        }
        $this->line(json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return count($resultado['conflitos']) ? self::INVALID : self::SUCCESS;
    }
}
