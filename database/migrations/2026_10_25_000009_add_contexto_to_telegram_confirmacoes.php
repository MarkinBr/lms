<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_confirmacoes', function (Blueprint $table) {
            $table->uuid('application_id')->nullable()->index();
            $table->string('estado_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_confirmacoes', function (Blueprint $table) {
            $table->dropColumn(['application_id', 'estado_hash']);
        });
    }
};
