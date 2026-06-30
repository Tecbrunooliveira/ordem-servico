<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->foreignId('cliente_id')
                ->nullable()
                ->after('responsavel_id')
                ->constrained('clientes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
