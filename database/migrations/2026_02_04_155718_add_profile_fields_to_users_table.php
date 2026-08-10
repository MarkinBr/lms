<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Endereço detalhado (se ainda não existirem)
            if (!Schema::hasColumn('users', 'numero')) $table->string('numero')->nullable()->after('endereco');
            if (!Schema::hasColumn('users', 'complemento')) $table->string('complemento')->nullable()->after('numero');
            if (!Schema::hasColumn('users', 'bairro')) $table->string('bairro')->nullable()->after('complemento');

            // Dados Pessoais Extras
            if (!Schema::hasColumn('users', 'genero')) $table->string('genero')->nullable()->after('data_nasc');
            if (!Schema::hasColumn('users', 'profissao')) $table->string('profissao')->nullable()->after('genero');
            if (!Schema::hasColumn('users', 'escolaridade')) $table->string('escolaridade')->nullable()->after('profissao');
            if (!Schema::hasColumn('users', 'bio')) $table->text('bio')->nullable()->after('escolaridade');

            // Preferências
            if (!Schema::hasColumn('users', 'receber_newsletter')) $table->boolean('receber_newsletter')->default(false);
            if (!Schema::hasColumn('users', 'receber_notificacoes_email')) $table->boolean('receber_notificacoes_email')->default(true);
            if (!Schema::hasColumn('users', 'perfil_publico')) $table->boolean('perfil_publico')->default(false);

            // Controle e Segurança
            if (!Schema::hasColumn('users', 'senha_alterada_em')) $table->timestamp('senha_alterada_em')->nullable();
            if (!Schema::hasColumn('users', 'exclusao_solicitada_em')) $table->timestamp('exclusao_solicitada_em')->nullable();
            if (!Schema::hasColumn('users', 'motivo_exclusao')) $table->text('motivo_exclusao')->nullable();
            if (!Schema::hasColumn('users', 'perfil_atualizado_em')) $table->timestamp('perfil_atualizado_em')->nullable();
            
            // Status (caso não exista, ou se usa 'ativo' boolean, criamos um status string para controle mais granular)
            if (!Schema::hasColumn('users', 'status') && !Schema::hasColumn('users', 'ativo')) {
                 $table->string('status')->default('ativo'); 
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'numero', 'complemento', 'bairro',
                'genero', 'profissao', 'escolaridade', 'bio',
                'receber_newsletter', 'receber_notificacoes_email', 'perfil_publico',
                'senha_alterada_em', 'exclusao_solicitada_em', 'motivo_exclusao', 'perfil_atualizado_em'
            ]);
        });
    }
};