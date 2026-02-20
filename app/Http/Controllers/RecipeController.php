<?php

namespace App\Http\Controllers;

use App\Services\DirectionParser;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

    public function create(): View
    {
        return view('recipes.form', [
            'recipe' => null,
            'preparation' => null,
            'editing' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $response = $this->api->post('/v1/recipes', [
            'data' => [
                'type' => 'recipes',
                'attributes' => $this->recipeAttributes($request),
            ],
        ], $this->token());

        if (! $response->successful()) {
            return back()->withInput()->withErrors(['api' => $this->extractApiError($response)]);
        }

        $recipe = $this->flattenSingle($response->json());
        $slug = $recipe['slug'] ?? $recipe['id'];

        $this->savePreparation($slug, $request);

        return redirect()->route('recipes.edit', $slug)->with('success', 'Recipe created.');
    }

    public function edit(string $slug): View
    {
        $recipeResponse = $this->api->get("/v1/recipes/{$slug}", token: $this->token());
        $prepResponse = $this->api->get("/v1/recipes/{$slug}/preparation", token: $this->token());

        $recipe = null;
        $preparation = null;

        if ($recipeResponse->successful()) {
            $recipe = $this->flattenSingle($recipeResponse->json());
        }

        if ($prepResponse->successful()) {
            $prepData = $prepResponse->json();
            $preparation = $prepData['data']['attributes'] ?? $prepData;
        }

        return view('recipes.form', [
            'recipe' => $recipe,
            'preparation' => $preparation,
            'editing' => true,
        ]);
    }

    public function update(string $slug, Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $response = $this->api->patch("/v1/recipes/{$slug}", [
            'data' => [
                'type' => 'recipes',
                'attributes' => $this->recipeAttributes($request),
            ],
        ], $this->token());

        if (! $response->successful()) {
            return back()->withInput()->withErrors(['api' => $this->extractApiError($response)]);
        }

        $recipe = $this->flattenSingle($response->json());
        $newSlug = $recipe['slug'] ?? $slug;

        $this->savePreparation($newSlug, $request);

        return redirect()->route('recipes.edit', $newSlug)->with('success', 'Recipe updated.');
    }

    public function import(): View
    {
        return view('recipes.import');
    }

    public function importStore(Request $request): RedirectResponse
    {
        $request->validate([
            'json_file' => 'required|file',
        ]);

        $content = json_decode(
            file_get_contents($request->file('json_file')->path()),
            true,
        );

        if ($content === null) {
            return back()->withErrors(['json_file' => 'The file does not contain valid JSON.']);
        }

        $response = $this->api->post('/v1/recipes/import', $content, $this->token());

        if (! $response->successful()) {
            return back()->withErrors(['json_file' => 'Import failed: ' . $this->extractApiError($response)]);
        }

        $recipe = $this->flattenSingle($response->json());
        $slug = $recipe['slug'] ?? $recipe['id'];

        return redirect()->route('recipes.edit', $slug)->with('success', 'Recipe imported successfully.');
    }

    public function parseDirection(Request $request, DirectionParser $parser): JsonResponse
    {
        $request->validate(['sentence' => 'required|string|max:1000']);

        return response()->json($parser->parse($request->input('sentence')));
    }

    private function recipeAttributes(Request $request): array
    {
        return array_filter([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'difficulty' => $request->input('difficulty'),
            'serves' => $request->filled('serves') ? (int) $request->input('serves') : null,
            'status' => $request->input('status', 'draft'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function savePreparation(string $slug, Request $request): void
    {
        $directions = collect($request->input('directions', []))
            ->filter(fn ($d) => ! empty($d['operation']) || ! empty($d['product']) || ! empty($d['notes']))
            ->values()
            ->map(function ($d) {
                if (! empty($d['parsed_json'])) {
                    $parsed = json_decode($d['parsed_json'], true);
                    if (is_array($parsed)) {
                        $parsed['notes'] = $d['notes'] ?? null;

                        return array_filter($parsed, fn ($v) => $v !== null && $v !== '' && $v !== []);
                    }
                }

                return array_filter([
                    'operation' => $d['operation'] ?? null,
                    'product' => $d['product'] ?? null,
                    'notes' => $d['notes'] ?? null,
                    'duration' => ! empty($d['duration']) ? (int) $d['duration'] : null,
                ], fn ($v) => $v !== null && $v !== '');
            })
            ->all();

        $ingredients = collect($request->input('ingredients', []))
            ->filter(fn ($i) => ! empty($i['product']))
            ->values()
            ->map(fn ($i) => array_filter([
                'amount' => $i['amount'] ?? null,
                'measure' => $i['measure'] ?? null,
                'product' => $i['product'] ?? null,
                'notes' => $i['notes'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''))
            ->all();

        if (empty($directions) && empty($ingredients)) {
            return;
        }

        $this->api->patch("/v1/recipes/{$slug}/preparation", [
            'data' => [
                'type' => 'preparations',
                'attributes' => array_filter([
                    'directions' => $directions ?: null,
                    'ingredients' => $ingredients ?: null,
                ], fn ($v) => $v !== null),
            ],
        ], $this->token());
    }

    private function extractApiError(Response $response): string
    {
        try {
            $json = $response->json();
        } catch (\Throwable) {
            return 'API error (HTTP ' . $response->status() . '): ' . substr($response->body(), 0, 300);
        }

        if (isset($json['errors'][0])) {
            return $json['errors'][0]['detail'] ?? $json['errors'][0]['title'] ?? json_encode($json['errors'][0]);
        }

        if (isset($json['message'])) {
            return $json['message'];
        }

        return 'API error (HTTP ' . $response->status() . '): ' . substr($response->body(), 0, 300);
    }
}
