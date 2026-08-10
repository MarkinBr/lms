<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * CETSADP v9.3.2 — Migration SSO
 * Adiciona campos necessários para integração SSO entre Live e LMS
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Campo username para login de admin/professor via SSO
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('email');
            }
            
            // Campos SSO
            if (!Schema::hasColumn('users', 'last_sso_at')) {
                $table->timestamp('last_sso_at')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'sso_provider')) {
                $table->string('sso_provider', 20)->nullable()->after('last_sso_at');
            }
        });

        $indexExists = false;

        // Verifica qual banco de dados está rodando para usar a consulta correta
        if (DB::getDriverName() === 'pgsql') {
            $result = DB::select("
                SELECT 1 FROM pg_indexes 
                WHERE tablename = 'users' 
                AND indexname IN ('users_cpf_index', 'idx_users_cpf')
                LIMIT 1
            ");
            $indexExists = !empty($result);
        } elseif (DB::getDriverName() === 'sqlite') {
            // Consulta compatível com o SQLite no seu ambiente local
            $result = DB::select("
                SELECT 1 FROM sqlite_master 
                WHERE type = 'index' 
                AND name IN ('users_cpf_index', 'idx_users_cpf') 
                AND tbl_name = 'users'
                LIMIT 1
            ");
            $indexExists = !empty($result);
        }

        // Cria o índice se não existir na tabela
        if (!$indexExists && Schema::hasColumn('users', 'cpf')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('cpf', 'idx_users_cpf');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['last_sso_at', 'sso_provider'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};