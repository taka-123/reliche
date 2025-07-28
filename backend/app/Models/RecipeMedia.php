<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class RecipeMedia extends Model
{
    use SoftDeletes;

    protected $table = 'recipe_media';

    protected $fillable = [
        'recipe_id',
        'user_id',
        'media_type',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
        'metadata',
        'description',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'approved_at',
        'deleted_at',
    ];

    /**
     * 自動監査ロジック
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->save();
            }
        });
    }

    /**
     * レシピとのリレーション
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * 投稿ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 承認者とのリレーション
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 作成者とのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 更新者とのリレーション
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 削除者とのリレーション
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * スコープ: 承認済みメディアのみ
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * スコープ: 未承認メディアのみ
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * スコープ: 画像のみ
     */
    public function scopeImages($query)
    {
        return $query->where('media_type', 'image');
    }

    /**
     * スコープ: 動画のみ
     */
    public function scopeVideos($query)
    {
        return $query->where('media_type', 'video');
    }

    /**
     * ファイルサイズを人間が読みやすい形式で取得
     */
    public function getHumanReadableFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * 画像かどうかを判定
     */
    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    /**
     * 動画かどうかを判定
     */
    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    /**
     * 承認済みかどうかを判定
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * 承認する
     */
    public function approve($approver_id = null): bool
    {
        $this->is_approved = true;
        $this->approved_by = $approver_id ?? Auth::id();
        $this->approved_at = now();

        return $this->save();
    }

    /**
     * 承認を取り消す
     */
    public function unapprove(): bool
    {
        $this->is_approved = false;
        $this->approved_by = null;
        $this->approved_at = null;

        return $this->save();
    }

    /**
     * フルパスのURLを取得
     */
    public function getFullUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
    }
}
