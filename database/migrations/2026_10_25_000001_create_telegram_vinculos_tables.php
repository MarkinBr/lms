<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_vinculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('chat_id')->nullable()->unique();
            $table->string('codigo_hash', 64)->nullable()->unique();
            $table->timestamp('codigo_expira_em')->nullable();
            $table->timestamps();
        });

        Schema::create('telegram_updates', function (Blueprint $table) {
            $table->bigInteger('update_id')->primary();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_updates');
        Schema::dropIfExists('telegram_vinculos');
    }
};
