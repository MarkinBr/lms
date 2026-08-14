<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove a coluna legada url_publica dos documentos de inscrição.
     * Issue #50: nenhum link permanente/público deve existir para esses arquivos.
     * Compatível com SQLite (testes) e PostgreSQL (produção).
     */
    public function up(): void
    {
        if (Schema::hasColumn('application_documents', 'url_publica')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->dropColumn('url_publica');
            });
        }
    }

    /** Rollback reversível: recria a coluna exatamente como estava. */
    public function down(): void
    {
        if (! Schema::hasColumn('application_documents', 'url_publica')) {
            Schema::table('application_documents', function (Blueprint $table) {
                $table->string('url_publica')->nullable()->after('caminho_arquivo');
            });
        }
    }
};
