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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            // Nome
            $table->string('name');
            $table->string('slug')->unique();
            // Complemento
            $table->text('complement')->nullable();
            // Status
            // 0- Inativo, 1 - Ativo
            $table->char('status', 1)->default(1);
            // Atributos personalizados
            $table->json('custom')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
