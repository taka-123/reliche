<?php

use App\Http\Controllers\API\AIRecipeController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\RecipeController;
use App\Http\Controllers\API\RecipeReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 認証API・レシピAPIを提供
|
*/

// 認証関連のルート
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::get('me', [AuthController::class, 'me'])->name('me');
});

// AIレシピ生成関連のルート（異なるパスで先に定義）
Route::group([
    'middleware' => 'api',
    'prefix' => 'ai-recipes',
], function ($router) {
    Route::post('generate', [AIRecipeController::class, 'generate'])->name('ai-recipes.generate');
    Route::post('generate/ingredients', [AIRecipeController::class, 'generateByIngredients'])->name('ai-recipes.generate-by-ingredients');
    Route::post('generate/constraints', [AIRecipeController::class, 'generateWithConstraints'])->name('ai-recipes.generate-with-constraints');
});

// レシピ関連のルート
Route::group([
    'middleware' => 'api',
], function ($router) {
    Route::get('ingredients/search', [RecipeController::class, 'searchIngredients'])->name('ingredients.search');
    Route::post('recipes/suggest', [RecipeController::class, 'suggest'])->name('recipes.suggest');
    Route::get('recipes', [RecipeController::class, 'index'])->name('recipes.index');
    Route::get('recipes/{id}', [RecipeController::class, 'show'])->name('recipes.show');
});

// お気に入り関連のルート（認証が必要）
Route::group([
    'middleware' => ['api', 'auth:api'],
], function ($router) {
    Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('favorites/{recipe_id}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::get('favorites/check/{recipe_id}', [FavoriteController::class, 'check'])->name('favorites.check');
});

// レシピレビュー関連のルート
Route::group([
    'middleware' => 'api',
], function ($router) {
    // 認証不要: レビュー表示・統計
    Route::get('recipes/{recipe}/reviews', [RecipeReviewController::class, 'index'])->name('recipe-reviews.index');
    Route::get('recipes/{recipe}/reviews/statistics', [RecipeReviewController::class, 'statistics'])->name('recipe-reviews.statistics');
    Route::get('recipes/{recipe}/reviews/{review}', [RecipeReviewController::class, 'show'])->name('recipe-reviews.show');
});

// レシピレビュー関連のルート（認証が必要）
Route::group([
    'middleware' => ['api', 'auth:api'],
], function ($router) {
    // 認証必要: レビュー投稿・更新・削除
    Route::post('recipes/{recipe}/reviews', [RecipeReviewController::class, 'store'])->name('recipe-reviews.store');
    Route::put('recipes/{recipe}/reviews/{review}', [RecipeReviewController::class, 'update'])->name('recipe-reviews.update');
    Route::delete('recipes/{recipe}/reviews/{review}', [RecipeReviewController::class, 'destroy'])->name('recipe-reviews.destroy');
});
