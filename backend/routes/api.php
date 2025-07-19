<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\RecipeController;
use App\Http\Controllers\API\FavoriteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 認証API・レシピAPI・お気に入りAPIを提供
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

// レシピ関連のルート
Route::group([
    'middleware' => 'api',
], function ($router) {
    Route::get('ingredients/search', [RecipeController::class, 'searchIngredients'])->name('ingredients.search');
    Route::post('recipes/suggest', [RecipeController::class, 'suggest'])->name('recipes.suggest');
    Route::get('recipes/{id}', [RecipeController::class, 'show'])->name('recipes.show');
});

// お気に入り関連のルート（認証が必要）
Route::group([
    'middleware' => ['api', 'auth:api'],
    'prefix' => 'favorites',
], function ($router) {
    Route::get('/', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('{recipe_id}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::get('{recipe_id}/check', [FavoriteController::class, 'check'])->name('favorites.check');
});
