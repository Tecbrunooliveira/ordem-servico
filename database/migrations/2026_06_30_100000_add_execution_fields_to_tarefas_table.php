<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->boolean('pausada')->default(false)->after('tempo_segundos');
            $table->timestamp('iniciada_em')->nullable()->after('pausada');
            $table->timestamp('finalizada_em')->nullable()->after('iniciada_em');
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropColumn(['pausada', 'iniciada_em', 'finalizada_em']);
        });
    }
};
