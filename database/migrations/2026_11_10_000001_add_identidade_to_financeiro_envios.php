<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financeiro_envios', function (Blueprint $table) {
            $table->foreignId('aluno_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('acesso_versao')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('financeiro_envios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aluno_id');
            $table->dropColumn('acesso_versao');
        });
    }
};
