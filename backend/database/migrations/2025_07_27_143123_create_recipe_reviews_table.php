<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recipe_reviews', function (Blueprint $table) {
            $table->id();

            // 外部キー
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // 評価項目（1-5の整数値）
            $table->tinyInteger('rating')->unsigned()->comment('総合評価 (1-5)');
            $table->tinyInteger('taste_score')->unsigned()->nullable()->comment('味評価 (1-5)');
            $table->tinyInteger('difficulty_score')->unsigned()->nullable()->comment('難易度評価 (1-5)');
            $table->tinyInteger('instruction_clarity')->unsigned()->nullable()->comment('手順明確性評価 (1-5)');

            // レビュー内容
            $table->text('comment')->nullable()->comment('レビューコメント');
            $table->json('review_images')->nullable()->comment('レビュー画像URLリスト');

            // 監査カラム（必須・順序厳守）
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('レコード作成日時');
            $table->bigInteger('created_by')->nullable()->comment('レコード作成ユーザーID');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('レコード最終更新日時');
            $table->bigInteger('updated_by')->nullable()->comment('レコード最終更新ユーザーID');
            $table->timestamp('deleted_at')->nullable()->comment('論理削除日時');
            $table->bigInteger('deleted_by')->nullable()->comment('レコード削除ユーザーID');

            // 外部キー制約
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // インデックス
            $table->index(['recipe_id', 'user_id']); // レシピ・ユーザー複合検索用
            $table->index('rating'); // 評価による並び替え用
            $table->index('created_at'); // 新着順表示用

            // 制約
            $table->unique(['recipe_id', 'user_id', 'deleted_at'], 'unique_recipe_user_review'); // 1ユーザー1レシピ1レビュー
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_reviews');
    }
};
