<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noticias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->string('resumo', 500);
            $table->text('texto');
            $table->boolean('publicada')->default(false);
            $table->timestamp('publicada_em')->nullable();
            $table->foreignId('publicada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['publicada', 'publicada_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
