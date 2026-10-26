<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_confirmacoes', function (Blueprint $table) {
            $table->string('recurso_tipo', 64)->nullable();
            $table->unsignedBigInteger('recurso_id')->nullable();
        });
        Schema::create('telegram_publicacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('confirmacao_id')->unique()->constrained('telegram_confirmacoes')->cascadeOnDelete();
            $table->text('dados_criptografados');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_publicacoes');
        Schema::table('telegram_confirmacoes', function (Blueprint $table) {
            $table->dropColumn(['recurso_tipo', 'recurso_id']);
        });
    }
};
