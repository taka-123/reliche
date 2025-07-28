<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    protected $guarded = [
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    // 監査ログ関係のリレーション
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // 自動監査ロジック
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $userId = optional(auth()->user())->id;
            if ($userId) {
                $model->created_by = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = optional(auth()->user())->id;
            if ($userId) {
                $model->updated_by = $userId;
            }
        });

        static::deleting(function ($model) {
            $userId = optional(auth()->user())->id;
            if ($userId) {
                $model->deleted_by = $userId;
                $model->saveQuietly(); // イベントを発生させずに保存
            }
        });
    }
}
