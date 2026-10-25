<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('matricula_login')->nullable()->unique();
            $table->string('email_confirmacao_pendente')->nullable();
            $table->string('email_confirmacao_token', 64)->nullable();
            $table->timestamp('email_confirmacao_expira_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matricula_login']);
            $table->dropColumn(['matricula_login', 'email_confirmacao_pendente', 'email_confirmacao_token', 'email_confirmacao_expira_em']);
        });
        // Preservar e-mails nulos: rollback não deve inventar endereços nem excluir alunos.
    }
};
