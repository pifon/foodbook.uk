<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PantryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShoppingListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');

Route::middleware('guest.api')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware(['auth.api', 'user.in.session'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/recipes/import', [RecipeController::class, 'import'])->name('recipes.import');
    Route::post('/recipes/import', [RecipeController::class, 'importStore'])->name('recipes.import.store');
    Route::post(
        '/recipes/parse-direction',
        [RecipeController::class, 'parseDirection']
    )->name('recipes.parse-direction');
    Route::get('/recipes/{slug}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::patch('/recipes/{slug}', [RecipeController::class, 'update'])->name('recipes.update');
    Route::get('/recipes/{slug}/preparation', [RecipeController::class, 'getPreparationJson'])
        ->name('recipes.preparation.show');
    Route::post('/recipes/{slug}/preparation', [RecipeController::class, 'savePreparationAjax'])
        ->name('recipes.preparation.store');
    Route::delete('/recipes/{slug}/directions/{id}', [RecipeController::class, 'deleteDirection'])
        ->name('recipes.directions.delete');
    Route::post('/recipes/{slug}/directions/from-text', [RecipeController::class, 'addDirectionFromText'])
        ->name('recipes.directions.from-text');
    Route::post('/recipes/product-create', [ProductController::class, 'store'])->name('products.store');

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

// api (internal): when outbound requests to the API (API_BASE_URL) loop back to this app, forward to the API.
$apiProxy = function (Request $request, string $path, string $contentType) {
    $base = rtrim(
        config('services.api.internal_base_url') ?: config('services.api.base_url'),
        '/',
    ) ?: null;
    if (! $base) {
        return response()->json([
            'errors' => [[
                'title' => 'api proxy not configured',
                'detail' => 'Set API_INTERNAL_BASE_URL (e.g. https://172.18.0.3/api) '
                    . 'so /api/* forwards to the API.',
            ]],
        ], 502);
    }
    $url = $base . '/' . ltrim($path, '/');
    $response = Http::withHeaders(['Content-Type' => $contentType, 'Accept' => $contentType])
        ->withOptions(['verify' => config('services.api.verify_ssl')])
        ->withBody($request->getContent(), $contentType)
        ->post($url);
    $headers = ['Content-Type' => $response->header('Content-Type') ?: $contentType];
    return response($response->body(), $response->status(), $headers);
};

// api (internal): forward /api/v1/* to the API when outbound requests loop back to this app.
$apiV1Proxy = function (Request $request, ?string $path = '') {
    $base = rtrim(
        config('services.api.internal_base_url') ?: config('services.api.base_url'),
        '/',
    ) ?: null;
    if (! $base) {
        return response()->json([
            'errors' => [[
                'title' => 'api proxy not configured',
                'detail' => 'Set API_INTERNAL_BASE_URL.',
            ]],
        ], 502);
    }
    $path = trim($path ?? '', '/');
    $url = rtrim($base . '/v1/' . $path, '/');
    $queryString = $request->getQueryString();
    if ($queryString !== null && $queryString !== '') {
        $url .= '?' . $queryString;
    }
    $contentType = $request->header('Content-Type')
        ?: 'application/vnd.api+json';
    $pending = Http::withHeaders([
        'Accept' => $request->header('Accept') ?: 'application/vnd.api+json',
        'Content-Type' => $contentType,
    ])->withOptions(['verify' => config('services.api.verify_ssl')])->withoutRedirecting();
    if ($request->header('Authorization')) {
        $pending = $pending->withToken(str_replace('Bearer ', '', $request->bearerToken()));
    }
    $method = strtoupper($request->method());
    $response = in_array($method, ['GET', 'HEAD'], true)
        ? $pending->{$method === 'HEAD' ? 'head' : 'get'}($url)
        : $pending->withBody($request->getContent(), $contentType)->send($method, $url);
    $headers = ['Content-Type' => $response->header('Content-Type') ?: $contentType];
    return response($response->body(), $response->status(), $headers);
};

Route::post('/api/login', fn (Request $request) => $apiProxy($request, 'login', 'application/vnd.api+json'));
Route::post('/api/register', fn (Request $request) => $apiProxy($request, 'register', 'application/vnd.api+json'));
Route::any('/api/v1/me', fn (Request $request) => $apiV1Proxy($request, 'me'));
Route::any('/api/v1/me/{path}', fn (Request $request, string $path) => $apiV1Proxy($request, 'me/' . $path))
    ->where('path', '.+');
Route::any('/api/v1/cuisines', fn (Request $request) => $apiV1Proxy($request, 'cuisines'));
Route::any('/api/v1/cuisines/{path}', fn (Request $request, string $path) => $apiV1Proxy($request, 'cuisines/' . $path))
    ->where('path', '.+');
Route::any('/api/v1/recipes', fn (Request $request) => $apiV1Proxy($request, 'recipes'));
Route::any('/api/v1/recipes/{path}', fn (Request $request, string $path) => $apiV1Proxy($request, 'recipes/' . $path))
    ->where('path', '.+');
Route::any('/api/v1/products', fn (Request $request) => $apiV1Proxy($request, 'products'));
Route::any('/api/v1/products/{path}', fn (Request $request, string $path) => $apiV1Proxy($request, 'products/' . $path))
    ->where('path', '.+');

// Fallback: serve app for any other path (e.g. when proxy adds path prefix or SPA client routes)
Route::fallback(function () {
    return view('home', ['authenticated' => (bool) session('api_token')]);
});
