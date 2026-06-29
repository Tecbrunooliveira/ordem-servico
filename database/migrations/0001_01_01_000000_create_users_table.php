<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('email')->unique();
                $table->timestamp('email_verificado_em')->nullable();
                $table->string('senha');
                $table->rememberToken('token_lembrar');
                $table->timestamp('criado_em')->nullable();
                $table->timestamp('atualizado_em')->nullable();
            });
        }

        if (! Schema::hasTable('tokens_redefinicao_senha')) {
            Schema::create('tokens_redefinicao_senha', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessoes')) {
            Schema::create('sessoes', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessoes');
        Schema::dropIfExists('tokens_redefinicao_senha');
        Schema::dropIfExists('usuarios');
    }
};
