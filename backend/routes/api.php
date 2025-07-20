<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\RecipeController;
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
], function ($router) {
    Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('favorites/{recipe_id}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::get('favorites/check/{recipe_id}', [FavoriteController::class, 'check'])->name('favorites.check');
});
