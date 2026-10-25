<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_outbox', function (Blueprint $table) {
            // Registros históricos ficam protegidos por padrão (fail-closed).
            $table->boolean('requer_autorizacao')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('telegram_outbox', function (Blueprint $table) {
            $table->dropColumn('requer_autorizacao');
        });
    }
};
