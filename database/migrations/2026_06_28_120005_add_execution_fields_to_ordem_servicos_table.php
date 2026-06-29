<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->foreignId('tecnico_id')->nullable()->after('cliente_id')->constrained('usuarios')->nullOnDelete();
            $table->string('participante')->nullable()->after('status');
            $table->string('participante_telefone', 20)->nullable()->after('participante');
            $table->unsignedInteger('tempo_segundos')->default(0)->after('participante_telefone');
            $table->boolean('pausada')->default(false)->after('tempo_segundos');
            $table->text('descricao_servicos')->nullable()->after('pausada');
            $table->string('participante_1')->nullable()->after('descricao_servicos');
            $table->string('participante_2')->nullable()->after('participante_1');
            $table->string('participante_3')->nullable()->after('participante_2');
            $table->string('participante_4')->nullable()->after('participante_3');
            $table->text('observacoes')->nullable()->after('participante_4');
            $table->timestamp('iniciada_em')->nullable()->after('observacoes');
            $table->timestamp('finalizada_em')->nullable()->after('iniciada_em');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropForeign(['tecnico_id']);
            $table->dropColumn([
                'tecnico_id',
                'participante',
                'participante_telefone',
                'tempo_segundos',
                'pausada',
                'descricao_servicos',
                'participante_1',
                'participante_2',
                'participante_3',
                'participante_4',
                'observacoes',
                'iniciada_em',
                'finalizada_em',
            ]);
        });
    }
};
