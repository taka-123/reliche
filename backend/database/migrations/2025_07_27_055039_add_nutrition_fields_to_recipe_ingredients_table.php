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
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->text('nutrition_notes')->nullable()->comment('このレシピにおける栄養的な役割や特記事項');
            $table->text('cooking_method_tips')->nullable()->comment('この調理法での栄養素の損失を防ぐコツなど');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropColumn(['nutrition_notes', 'cooking_method_tips']);
        });
    }
};
