<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_plans', function (Blueprint $table) {
            $table->id();
            // Nome
            $table->string('name');
            $table->string('slug')->unique();
            // Complemento
            $table->text('complement')->nullable();
            // Preço/Valor mensal
            $table->bigInteger('monthly_price')->nullable();
            // Observações do preço/valor mensal
            $table->string('monthly_price_notes')->nullable();
            // Preço/Valor anual
            $table->bigInteger('annual_price')->nullable();
            // Observações do preço/valor anual
            $table->string('annual_price_notes')->nullable();
            // Melhor custo benefício? 1 - sim, 0 - não
            $table->boolean('best_benefit_cost')->default(0);
            // Ordem
            $table->integer('order')->unsigned()->default(1);
            // Status
            // 0- Inativo, 1 - Ativo
            $table->char('status', 1)->default(1);
            // Recursos de destaque
            $table->json('features')->nullable();
            // Configurações do plano
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_plans');
    }
};
