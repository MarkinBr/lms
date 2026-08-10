<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verificamos primeiro se a tabela existe para evitar erros
        if (Schema::hasTable('matriculas')) {
            Schema::table('matriculas', function (Blueprint $table) {
                
                if (!Schema::hasColumn('matriculas', 'observacoes')) {
                    $table->text('observacoes')->nullable()->after('status');
                }
                
                if (!Schema::hasColumn('matriculas', 'numero_matricula')) {
                    $table->string('numero_matricula')->nullable()->after('observacoes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('matriculas')) {
            Schema::table('matriculas', function (Blueprint $table) {
                // Removemos apenas se as colunas existirem
                if (Schema::hasColumn('matriculas', 'observacoes')) {
                    $table->dropColumn('observacoes');
                }
                if (Schema::hasColumn('matriculas', 'numero_matricula')) {
                    $table->dropColumn('numero_matricula');
                }
            });
        }
    }
};