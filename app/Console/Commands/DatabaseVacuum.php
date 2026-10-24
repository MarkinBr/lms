<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseVacuum extends Command
{
    protected $signature = 'db:vacuum';

    protected $description = 'Executa manutenção/otimização segura do banco de dados';

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();

        try {
            match ($driver) {
                'pgsql' => DB::unprepared('VACUUM (ANALYZE)'),
                'sqlite' => DB::unprepared('VACUUM'),
                'mysql', 'mariadb' => $this->components->info('MySQL/MariaDB gerencia otimização de forma diferente; VACUUM não é necessário.'),
                default => $this->components->warn("Driver '{$driver}' não possui rotina de VACUUM configurada."),
            };
        } catch (\Throwable $e) {
            $this->components->error('Falha ao otimizar o banco: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Manutenção do banco concluída ({$driver}).");

        return self::SUCCESS;
    }
}
