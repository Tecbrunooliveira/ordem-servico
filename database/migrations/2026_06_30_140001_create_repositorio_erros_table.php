<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositorio_erros', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo');
            $table->foreignId('sistema_id')->constrained('sistemas')->restrictOnDelete();
            $table->longText('descricao_erro');
            $table->longText('solucao')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositorio_erros');
    }
};
