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
        Schema::create('tenant_account_tenant_category', function (Blueprint $table) {
            // Conta de cliente
            $table->foreignId('tenant_account_id');
            $table->foreign('tenant_account_id')
                ->references('id')
                ->on('tenant_accounts')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            // Categoria
            $table->foreignId('category_id');
            $table->foreign('category_id')
                ->references('id')
                ->on('tenant_categories')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            // Não permite categorias repetidas por conta.
            $table->unique(['tenant_account_id', 'category_id'], 'tenant_account_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tenant_account_tenant_category');
    }
};
