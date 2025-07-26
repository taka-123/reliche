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
        Schema::table('recipes', function (Blueprint $table) {
            $table->integer('servings')->default(2)->after('cooking_time');
            $table->integer('calories')->nullable()->after('servings');
            $table->json('tags')->nullable()->after('calories');
            $table->string('category')->nullable()->after('tags');
            $table->string('source')->default('manual')->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['servings', 'calories', 'tags', 'category', 'source']);
        });
    }
};
