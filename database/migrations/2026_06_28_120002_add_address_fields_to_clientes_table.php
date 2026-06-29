<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('rua')->nullable()->after('estado');
            $table->string('numero', 20)->nullable()->after('rua');
            $table->string('bairro')->nullable()->after('numero');
            $table->string('cep', 10)->nullable()->after('bairro');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['rua', 'numero', 'bairro', 'cep']);
        });
    }
};
