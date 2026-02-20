<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q', '');
        $page = (int) $request->query('page', 1);

        $endpoint = $search ? '/v1/recipes/search' : '/v1/recipes';
        $params = ['page[number]' => $page, 'page[size]' => 12];
        if ($search) {
            $params['q'] = $search;
        }

        $response = $this->api->get($endpoint, $params, $this->token());

        $recipes = [];
        $pagination = null;

        if ($response->successful()) {
            $json = $response->json();
            $recipes = $this->flattenCollection($json);
            $pagination = $json['meta']['page'] ?? null;
        }

        return view('recipes.index', compact('recipes', 'pagination', 'search', 'page'));
    }

    public function show(string $slug): View
    {
        $recipeResponse = $this->api->get("/v1/recipes/{$slug}", token: $this->token());
        $prepResponse = $this->api->get("/v1/recipes/{$slug}/preparation", token: $this->token());

        $recipe = null;
        $preparation = null;
        $error = null;

        if ($recipeResponse->successful()) {
            $recipe = $this->flattenSingle($recipeResponse->json());
        } else {
            $error = $recipeResponse->status() === 404
                ? 'Recipe not found.'
                : 'Failed to load recipe.';
        }

        if ($prepResponse->successful()) {
            $prepData = $prepResponse->json();
            $preparation = $prepData['data']['attributes'] ?? $prepData;
        }

        return view('recipes.show', compact('recipe', 'preparation', 'error'));
    }
}
