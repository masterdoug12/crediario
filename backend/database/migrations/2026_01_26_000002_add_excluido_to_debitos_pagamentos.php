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
        Schema::table('debitos', function (Blueprint $table) {
            $table->boolean('excluido')->default(false)->index();
        });

        Schema::table('pagamentos', function (Blueprint $table) {
            $table->boolean('excluido')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debitos', function (Blueprint $table) {
            $table->dropColumn('excluido');
        });

        Schema::table('pagamentos', function (Blueprint $table) {
            $table->dropColumn('excluido');
        });
    }
};
