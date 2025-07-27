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
        Schema::create('ingredient_nutritions', function (Blueprint $table) {
            $table->id();
            $table->string('ingredient_name')->unique()->comment('食材名');
            $table->json('nutrition_facts')->comment('栄養成分情報（カロリー、ビタミン、ミネラル等）');
            $table->json('health_benefits')->comment('健康効果・効能情報');
            $table->json('cooking_tips')->comment('調理法による栄養変化のコツ');

            // 監査カラム（必須・順序厳守）
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // 外部キー制約
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // インデックス
            $table->index('ingredient_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_nutritions');
    }
};
