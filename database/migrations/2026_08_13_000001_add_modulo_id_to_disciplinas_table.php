<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona a coluna aditiva `disciplinas.modulo_id` para vincular
     * disciplinas ao módulo curricular a que pertencem.
     *
     * - Nullable: preserva disciplinas legadas sem módulo.
     * - FK para `modulos`, com nullOnDelete: apagar um módulo não apaga
     *   a disciplina, apenas zera a referência.
     * - Idempotente e reversível: guardas com `hasColumn` / `hasTable`
     *   evitam falhas em bancos que ainda não possuem as tabelas-base.
     */
    public function up(): void
    {
        if (! Schema::hasTable('disciplinas')) {
            return;
        }

        if (! Schema::hasTable('modulos')) {
            return;
        }

        if (Schema::hasColumn('disciplinas', 'modulo_id')) {
            return;
        }

        Schema::table('disciplinas', function (Blueprint $table) {
            $table->foreignId('modulo_id')
                ->nullable()
                ->after('curso_id')
                ->constrained('modulos')
                ->nullOnDelete();

            $table->index('modulo_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('disciplinas')) {
            return;
        }

        if (! Schema::hasColumn('disciplinas', 'modulo_id')) {
            return;
        }

        Schema::table('disciplinas', function (Blueprint $table) {
            $table->dropIndex(['modulo_id']);
            $table->dropConstrainedForeignId('modulo_id');
        });
    }
};
