<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_outbox', function (Blueprint $table) {
            // Alertas programados não possuem update_id da API Telegram.
            $table->unsignedBigInteger('update_id')->nullable()->change();
            $table->string('evento_chave', 120)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_outbox', function (Blueprint $table) {
            $table->dropUnique(['evento_chave']);
            $table->dropColumn('evento_chave');
        });
        // A nulidade permanece para preservar eventuais registros programados.
    }
};
