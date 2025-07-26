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
            $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
            $table->softDeletes()->after('updated_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->index('deleted_at');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
            $table->softDeletes()->after('updated_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->index('deleted_at');
        });

        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
            $table->softDeletes()->after('updated_by');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->index('deleted_at');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->index('category');
            $table->index('cooking_time');
            $table->index('calories');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // インデックス削除
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['cooking_time']);
            $table->dropIndex(['calories']);
            $table->dropIndex(['source']);
        });

        // 監査カラム削除（外部キー制約から先に削除）
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
        });
    }
};
