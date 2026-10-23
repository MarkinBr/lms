<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('eventos_publicos',function(Blueprint $table){
  $table->id(); $table->string('titulo');$table->text('descricao')->nullable();$table->string('categoria',30);
  $table->dateTime('data_inicio');$table->dateTime('data_fim')->nullable();$table->string('local')->nullable();
  $table->unsignedInteger('vagas')->nullable();$table->string('imagem_url',2048)->nullable();$table->string('inscricao_url',2048)->nullable();
  $table->boolean('publicado')->default(false);$table->timestamps();$table->index(['publicado','data_inicio']);
 }); }
 public function down(): void { Schema::dropIfExists('eventos_publicos'); }
};
