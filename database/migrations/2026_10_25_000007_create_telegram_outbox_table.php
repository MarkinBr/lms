<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('update_id')->unique();
            $table->string('chat_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pendente')->index();
            $table->json('payload')->nullable();
            $table->unsignedInteger('tentativas')->default(0);
            $table->timestamp('proxima_tentativa_em')->nullable()->index();
            $table->timestamp('lease_ate')->nullable();
            $table->timestamp('expira_em');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_outbox');
    }
};
