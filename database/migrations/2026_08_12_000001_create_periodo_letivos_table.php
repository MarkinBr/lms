<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodo_letivos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->boolean('ativo')->default(false);
            $table->timestamps();

            $table->index('data_inicio');
            $table->index('data_fim');
            $table->index('ativo');
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX periodo_letivos_ativo_unique ON periodo_letivos (ativo) WHERE ativo = true');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX periodo_letivos_ativo_unique ON periodo_letivos (ativo) WHERE ativo = 1');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS periodo_letivos_ativo_unique');
        }

        Schema::dropIfExists('periodo_letivos');
    }
};
