<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('telefone', 20)->nullable()->after('senha');
            $table->string('tipo', 20)->default('tecnico')->after('telefone');
            $table->boolean('ativo')->default(true)->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['telefone', 'tipo', 'ativo']);
        });
    }
};
