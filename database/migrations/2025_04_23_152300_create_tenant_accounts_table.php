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
        Schema::create('tenant_accounts', function (Blueprint $table) {
            $table->id();
            // Conta de cliente
            $table->string('tenant_id');
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            // Plano da conta
            $table->foreignId('plan_id')->nullable();
            $table->foreign('plan_id')
                ->references('id')
                ->on('tenant_plans')
                ->onUpdate('cascade')
                ->onDelete('set null');
            // Tipo
            // 1 - 'Padrão', ...
            $table->char('role', 1)->default(1);
            // Nome
            $table->string('name');
            // CPF/CNPJ
            $table->string('cpf_cnpj')->unique()->nullable();
            // Nome do títular
            $table->string('holder_name')->nullable();
            // Email(s)
            $table->string('emails')->nullable();
            // Telefone(s) de contato
            $table->json('phones')->nullable();
            // Complemento
            $table->text('complement')->nullable();
            // Redes Sociais
            $table->json('social_media')->nullable();
            // Horário de funcionamento:
            $table->json('opening_hours')->nullable();
            // Configurações do tema (Cor primária, secundária...)
            $table->json('theme')->nullable();
            // Status
            // 0 - Inativo, 1 - Ativo.
            $table->char('status', 1)->default(1);
            // Configurações específicas da conta
            $table->json('settings')->nullable();
            // Atributos personalizados
            $table->json('custom')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_accounts');
    }
};
