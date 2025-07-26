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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('recipe_id'); // 将来的にrecipesテーブルができたら外部キー制約を追加
            $table->timestamps();

            // ユーザーと同じレシピの重複お気に入りを防ぐユニーク制約
            $table->unique(['user_id', 'recipe_id']);

            // パフォーマンス向上のためのインデックス
            $table->index(['user_id', 'created_at']);
            $table->index('recipe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
