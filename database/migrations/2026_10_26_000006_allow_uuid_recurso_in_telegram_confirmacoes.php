<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O registro auditado pode ter chave numérica (aluno/nota) ou UUID (inscrição).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE telegram_confirmacoes ALTER COLUMN recurso_id TYPE VARCHAR(64) USING recurso_id::text');
        } else {
            Schema::table('telegram_confirmacoes', function (Blueprint $table): void {
                $table->string('recurso_id', 64)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Não converter UUIDs em inteiro nem remover referências de auditoria.
    }
};
