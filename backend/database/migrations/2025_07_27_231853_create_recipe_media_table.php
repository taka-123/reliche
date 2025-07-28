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
        Schema::create('recipe_media', function (Blueprint $table) {
            $table->id();

            // 外部キー
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade')->comment('レシピID');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('投稿ユーザーID');

            // メディア情報
            $table->enum('media_type', ['image', 'video'])->comment('メディアタイプ');
            $table->string('file_path')->comment('ファイルパス');
            $table->string('original_filename')->comment('オリジナルファイル名');
            $table->unsignedBigInteger('file_size')->comment('ファイルサイズ（バイト）');
            $table->string('mime_type')->comment('MIMEタイプ');
            $table->json('metadata')->nullable()->comment('メタデータ（幅、高さ、再生時間など）');

            // コンテンツ管理
            $table->text('description')->nullable()->comment('説明・キャプション');
            $table->boolean('is_approved')->default(false)->comment('承認状態');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('承認者ID');
            $table->timestamp('approved_at')->nullable()->comment('承認日時');

            // 監査カラム（必須・順序厳守）
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('作成者ID');
            $table->timestamp('updated_at')->useCurrent()->comment('更新日時');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('更新者ID');
            $table->timestamp('deleted_at')->nullable()->comment('削除日時');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete()->comment('削除者ID');

            // インデックス
            $table->index(['recipe_id', 'is_approved']);
            $table->index(['user_id', 'media_type']);
            $table->index(['created_at']);
            $table->index(['deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_media');
    }
};
