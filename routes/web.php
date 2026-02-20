<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PantryController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShoppingListController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');

Route::middleware('guest.api')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth.api')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/recipes/import', [RecipeController::class, 'import'])->name('recipes.import');
    Route::post('/recipes/import', [RecipeController::class, 'importStore'])->name('recipes.import.store');
    Route::post('/recipes/parse-direction',
        [RecipeController::class, 'parseDirection'])->name('recipes.parse-direction');
    Route::get('/recipes/{slug}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::patch('/recipes/{slug}', [RecipeController::class, 'update'])->name('recipes.update');

    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('/collections/{id}', [CollectionController::class, 'show'])->name('collections.show');

    Route::get('/shopping-lists', [ShoppingListController::class, 'index'])->name('shopping-lists.index');
    Route::get('/shopping-lists/{id}', [ShoppingListController::class, 'show'])->name('shopping-lists.show');
    Route::post('/shopping-lists/{listId}/items/{itemId}/toggle', [ShoppingListController::class, 'toggleItem'])
        ->name('shopping-lists.toggle-item');

    Route::get('/pantry', PantryController::class)->name('pantry');

    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::get('/recipes/{slug}', [RecipeController::class, 'show'])->name('recipes.show');
