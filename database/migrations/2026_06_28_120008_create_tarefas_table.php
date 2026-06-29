<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarefas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('status')->default('pendente');
            $table->string('prioridade')->default('media');
            $table->date('data_vencimento')->nullable();
            $table->foreignId('responsavel_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('categoria')->default('operacional');
            $table->date('data_inicio')->nullable();
            $table->unsignedInteger('tempo_segundos')->default(0);
            $table->string('recorrencia')->default('nenhuma');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarefas');
    }
};
