<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            // 1. Adicionar data_publicacao se não existir
            if (!Schema::hasColumn('avisos', 'data_publicacao')) {
                $table->dateTime('data_publicacao')->nullable()->after('ativo');
            }

            // 2. Garantir que data_evento existe (usado no seu Controller)
            if (!Schema::hasColumn('avisos', 'data_evento')) {
                $table->date('data_evento')->nullable()->after('data_expiracao');
            }
            
            // 3. Garantir que anexo existe
            if (!Schema::hasColumn('avisos', 'anexo')) {
                $table->string('anexo')->nullable()->after('mensagem');
            }

            // 4. Alterar a coluna 'tipo' para STRING usando o padrão do Laravel (compatível com SQLite)
            $table->string('tipo', 50)->default('info')->change();
        });

        // 5. Preencher data_publicacao nos registros antigos para não sumirem
        DB::table('avisos')->whereNull('data_publicacao')->update([
            'data_publicacao' => DB::raw('created_at')
        ]);
    }

    public function down(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            // Reverter mudanças se necessário (opcional)
            if (Schema::hasColumn('avisos', 'data_publicacao')) {
                $table->dropColumn('data_publicacao');
            }
        });
    }
};