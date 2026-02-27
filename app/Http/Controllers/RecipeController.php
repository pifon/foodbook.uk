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
            $recipes = is_array($json) ? $this->flattenCollection($json) : [];
            $pagination = is_array($json) ? ($json['meta']['page'] ?? null) : null;
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
            $rawPrep = $this->extractPreparationPayload($prepData);
            $preparation = $this->normalizePreparationForView($rawPrep) ?? ['directions' => [], 'ingredients' => []];
        }

        return view('recipes.show', compact('recipe', 'preparation', 'error'));
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->isAuthor()) {
            return redirect()->route('recipes.index')->with('error', 'Only authors can create recipes.');
        }

        $cuisines = $this->fetchCuisinesFromApi();
        return view('recipes.form', [
            'recipe' => null,
            'preparation' => null,
            'editing' => false,
            'cuisines' => $cuisines,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (! $this->isAuthor()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Only authors can create recipes.'], 403);
            }

            return redirect()->route('recipes.index')->with('error', 'Only authors can create recipes.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'cuisine' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'cuisine_request' => 'nullable|string|max:100',
        ]);

        $payload = $this->recipePayload($request);
        $response = $this->api->post('/v1/recipes', $payload, $this->token());

        if ($unauth = $this->ifApiUnauthorized($response)) {
            return $unauth;
        }
        if (! $response->successful()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $this->extractApiError($response)], 422);
            }

            return back()->withInput()->withErrors(['api' => $this->extractApiError($response)]);
        }

        $recipe = $this->flattenSingle($response->json());
        $slug = $recipe['slug'] ?? $recipe['id'];

        if (! $request->wantsJson()) {
            $this->savePreparation($slug, $request);

            return redirect()->route('recipes.edit', $slug)->with('success', 'Recipe created.');
        }

        return response()->json(['slug' => $slug, 'recipe' => $recipe]);
    }

    public function edit(string $slug): View|RedirectResponse
    {
        $recipeResponse = $this->api->get("/v1/recipes/{$slug}", token: $this->token());
        $prepResponse = $this->api->get("/v1/recipes/{$slug}/preparation", token: $this->token());

        if ($recipeResponse->status() === 401 || $prepResponse->status() === 401) {
            session()->forget(['api_token', 'user']);

            return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
        }

        $recipe = null;
        $preparation = null;

        if ($recipeResponse->successful()) {
            $recipe = $this->flattenSingle($recipeResponse->json());
        }

        if ($prepResponse->successful()) {
            $prepData = $prepResponse->json();
            $rawPrep = $this->extractPreparationPayload($prepData);
            $preparation = $this->normalizePreparationForView($rawPrep);
        }

        $cuisines = $this->fetchCuisinesFromApi();
        $preparationForView = $preparation ?? ['directions' => [], 'ingredients' => []];
        return view('recipes.form', [
            'recipe' => $recipe,
            'preparation' => $preparationForView,
            'editing' => true,
            'cuisines' => $cuisines,
        ]);
    }

    public function update(string $slug, Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'cuisine' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'cuisine_request' => 'nullable|string|max:100',
        ]);

        $payload = $this->recipePayload($request);
        $response = $this->api->patch("/v1/recipes/{$slug}", $payload, $this->token());

        if ($unauth = $this->ifApiUnauthorized($response)) {
            return $unauth;
        }
        if (! $response->successful()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $this->extractApiError($response)], 422);
            }

            return back()->withInput()->withErrors(['api' => $this->extractApiError($response)]);
        }

        $recipe = $this->flattenSingle($response->json());
        $newSlug = $recipe['slug'] ?? $slug;

        if (! $request->wantsJson()) {
            $this->savePreparation($newSlug, $request);

            return redirect()->route('recipes.edit', $newSlug)->with('success', 'Recipe updated.');
        }

        return response()->json(['slug' => $newSlug, 'recipe' => $recipe]);
    }

    public function import(): View|RedirectResponse
    {
        if (! $this->isAuthor()) {
            return redirect()->route('recipes.index')->with('error', 'Only authors can import recipes.');
        }

        return view('recipes.import');
    }

    public function importStore(Request $request): RedirectResponse
    {
        if (! $this->isAuthor()) {
            return redirect()->route('recipes.index')->with('error', 'Only authors can import recipes.');
        }

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

    /**
     * POST direction text to API: POST /v1/recipes/{slug}/directions/from-text.
     * API parses with DirectionParser, creates direction(s), returns data (array of directions) + meta.
     */
    public function addDirectionFromText(string $slug, Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'use_product_slug' => 'nullable|string|max:255',
            'product_ref' => 'nullable|string|max:255',
        ]);

        $path = "/v1/recipes/{$slug}/directions/from-text";
        $attributes = [
            'direction-text' => $request->input('text'),
        ];
        if ($request->filled('use_product_slug')) {
            $attributes['use-product-slug'] = $request->input('use_product_slug');
        }
        if ($request->filled('product_ref')) {
            $attributes['product-ref'] = $request->input('product_ref');
        }

        $response = $this->api->postJsonApi(
            $path,
            [
                'data' => [
                    'type' => 'direction-from-text',
                    'attributes' => $attributes,
                ],
            ],
            $this->token()
        );

        if ($unauth = $this->ifApiUnauthorized($response)) {
            return $unauth;
        }

        $apiRequest = 'POST ' . rtrim(config('services.api.base_url'), '/') . $path;
        $debugHeaders = config('app.debug') ? ['X-Api-Request' => $apiRequest] : [];

        if (! $response->successful()) {
            $apiJson = $response->json();
            $payload = [
                'error' => $this->extractApiError($response),
                'errors' => $apiJson['errors'] ?? null,
            ];
            if (config('app.debug')) {
                $payload['meta'] = ['api_request' => $apiRequest];
            }

            return response()->json(
                $payload,
                $response->status() >= 400 ? $response->status() : 422,
                $debugHeaders
            );
        }

        $resp = response()->json($response->json());
        foreach ($debugHeaders as $name => $value) {
            $resp->header($name, $value);
        }

        return $resp;
    }

    /**
     * Fetch all cuisines from API (GET /v1/cuisines). Paginates until no more pages, returns [ slug => label ].
     * If the first page is empty (API may ignore pagination params), retries once with no params.
     */
    private function fetchCuisinesFromApi(): array
    {
        $pageSize = 100;
        $pageNumber = 1;
        $allRawItems = [];
        $triedNoParams = false;

        do {
            $response = $this->api->get('/v1/cuisines', [
                'page[number]' => $pageNumber,
                'page[size]' => $pageSize,
            ], $this->token());
            if (! $response->successful()) {
                break;
            }
            $json = $response->json();
            if (! is_array($json)) {
                break;
            }
            $list = $this->flattenCollection($json);
            if (count($list) === 0 && $pageNumber === 1) {
                $fallback = $this->extractCuisinesFromAnyShape($json);
                foreach ($fallback as $item) {
                    $allRawItems[] = $item;
                }
            } else {
                foreach ($list as $item) {
                    $allRawItems[] = $item;
                }
            }
            if ($pageNumber === 1 && count($allRawItems) === 0) {
                $triedNoParams = true;
                $response = $this->api->get('/v1/cuisines', [], $this->token());
                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json)) {
                        $list = $this->flattenCollection($json);
                        if (count($list) > 0) {
                            foreach ($list as $item) {
                                $allRawItems[] = $item;
                            }
                        } else {
                            foreach ($this->extractCuisinesFromAnyShape($json) as $item) {
                                $allRawItems[] = $item;
                            }
                        }
                    }
                }
                break;
            }
            if (count($list) < $pageSize) {
                break;
            }
            $pageNumber++;
        } while (true);

        return $this->normalizeCuisinesList($allRawItems);
    }

    /**
     * Try to parse cuisine list from response when it's not standard JSON:API
     * (e.g. data[] with slug in attributes, or root key "cuisines").
     */
    private function extractCuisinesFromAnyShape(array $json): array
    {
        $raw = $json['data'] ?? $json['cuisines'] ?? $json['items'] ?? null;
        if (! is_array($raw)) {
            return [];
        }
        if (! array_is_list($raw) && isset($raw['id'])) {
            $raw = [$raw];
        }
        if (! array_is_list($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $r) {
            if (! is_array($r)) {
                continue;
            }
            $attrs = $r['attributes'] ?? $r;
            $id = $r['id'] ?? $attrs['id'] ?? $attrs['slug'] ?? $r['slug'] ?? null;
            $name = $attrs['name'] ?? $attrs['title'] ?? $attrs['label']
                ?? $r['name'] ?? $r['title'] ?? $r['label'] ?? (string) $id;
            if ($id !== null && $id !== '') {
                $out[] = ['id' => $id, 'slug' => $id, 'name' => $name];
            }
        }
        return $out;
    }

    /**
     * Build [ slug => label ] from a list of cuisine items (from flatten or
     * raw attributes). One entry per unique label.
     */
    private function normalizeCuisinesList(array $items): array
    {
        $out = [];
        $seenLabels = [];
        foreach ($items as $item) {
            $id = $item['id'] ?? $item['slug'] ?? null;
            if ($id === null || $id === '') {
                continue;
            }
            $label = $item['name'] ?? $item['title'] ?? $item['label'] ?? (string) $id;
            $labelKey = mb_strtolower(trim((string) $label));
            if ($labelKey === '') {
                continue;
            }
            $idStr = (string) $id;
            $labelSlug = preg_replace('/\s+/', '-', $labelKey);
            if (isset($seenLabels[$labelKey])) {
                $existingId = $seenLabels[$labelKey];
                if ($idStr === $labelSlug || $idStr === $labelKey) {
                    unset($out[$existingId]);
                    $out[$idStr] = $label;
                    $seenLabels[$labelKey] = $idStr;
                }
                continue;
            }
            $seenLabels[$labelKey] = $idStr;
            $out[$idStr] = $label;
        }

        return $out;
    }

    /**
     * Build JSON:API payload to match API spec: attributes (title,
     * description, prep-time-minutes, cook-time-minutes, difficulty, serves),
     * relationships.cuisine.data with type + slug.
     */
    private function recipePayload(Request $request): array
    {
        $attrs = $this->recipeAttributes($request);
        $cuisineSlug = $request->input('cuisine');
        $cuisineRequest = $request->input('cuisine_request');

        $data = [
            'type' => 'recipes',
            'attributes' => $attrs,
        ];

        $rels = [];
        if ($cuisineSlug !== null && $cuisineSlug !== '') {
            $rels['cuisine'] = [
                'data' => [
                    'type' => 'cuisines',
                    'slug' => $cuisineSlug,
                ],
            ];
        }
        if ($rels !== []) {
            $data['relationships'] = $rels;
        }

        if ($cuisineRequest !== null && $cuisineRequest !== '') {
            $data['attributes']['cuisine-request'] = $cuisineRequest;
        }

        return ['data' => $data];
    }

    private function recipeAttributes(Request $request): array
    {
        $attrs = [
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'prep-time-minutes' => $request->filled('prep_time_minutes')
                ? (int) $request->input('prep_time_minutes')
                : null,
            'cook-time-minutes' => $request->filled('cook_time_minutes')
                ? (int) $request->input('cook_time_minutes')
                : null,
            'difficulty' => $request->input('difficulty'),
            'serves' => $request->filled('serves')
                ? (int) $request->input('serves')
                : null,
        ];
        return array_filter($attrs, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Return current preparation (directions + ingredients) as JSON.
     * Used to refresh ingredients after add/remove direction.
     */
    public function getPreparationJson(string $slug): JsonResponse
    {
        $response = $this->api->get("/v1/recipes/{$slug}/preparation", token: $this->token());

        if ($unauth = $this->ifApiUnauthorized($response)) {
            return $unauth;
        }
        if (! $response->successful()) {
            return response()->json(['error' => $this->extractApiError($response)], $response->status());
        }

        $prepData = $response->json();
        $rawPrep = $this->extractPreparationPayload($prepData);
        $preparation = $this->normalizePreparationForView($rawPrep);

        return response()->json($preparation ?? ['directions' => [], 'ingredients' => []]);
    }

    /**
     * Delete a single direction step via API. Called when user removes a step that has an API id.
     * Returns meta.api_url and meta.api_status so the frontend can show the real API call in the log bar.
     */
    public function deleteDirection(string $slug, string $id): JsonResponse
    {
        $path = "/v1/recipes/{$slug}/directions/{$id}";
        $response = $this->api->delete($path, $this->token());

        if ($unauth = $this->ifApiUnauthorized($response)) {
            return $unauth;
        }
        if (! $response->successful()) {
            return response()->json(['error' => $this->extractApiError($response)], $response->status());
        }

        $baseUrl = rtrim(config('services.api.base_url', ''), '/');
        $apiUrl = $baseUrl !== '' ? $baseUrl . '/' . ltrim($path, '/') : null;

        return response()->json([
            'ok' => true,
            'meta' => [
                'api_url' => $apiUrl,
                'api_path' => $path,
                'api_status' => $response->status(),
            ],
        ]);
    }

    /**
     * Save preparation (directions + ingredients) via JSON body. Used when user accepts a step (AJAX).
     */
    public function savePreparationAjax(string $slug, Request $request): JsonResponse
    {
        $request->validate([
            'directions' => 'array',
            'directions.*.notes' => 'nullable|string',
            'directions.*.parsed_json' => 'nullable|string',
            'ingredients' => 'array',
            'ingredients.*.product' => 'nullable|string',
        ]);

        [$directions, $ingredients] = $this->normalizePreparation(
            $request->input('directions', []),
            $request->input('ingredients', [])
        );

        $payload = [
            'data' => [
                'type' => 'preparations',
                'attributes' => array_filter([
                    'directions' => $directions ?: null,
                    'ingredients' => $ingredients ?: null,
                ], fn ($v) => $v !== null),
            ],
        ];
        $response = $this->api->postJsonApi("/v1/recipes/{$slug}/preparation", $payload, $this->token());

        if ($unauth = $this->ifApiUnauthorized($response)) {
            return $unauth;
        }
        if ($response->successful()) {
            return response()->json(['ok' => true, 'preparation_saved' => true]);
        }
        if ($this->isPreparationMethodNotSupported($response)) {
            return response()->json(['ok' => true, 'preparation_saved' => false]);
        }

        return response()->json(['error' => $this->extractApiError($response)], 422);
    }

    /**
     * Extract preparation payload from API response.
     * Handles JSON:API compound document: data.relationships.directions/ingredients
     * reference included resources by type+id; we resolve and return attributes arrays.
     *
     * @return array{directions: array, ingredients: array}|null
     */
    private function extractPreparationPayload(array $prepData): ?array
    {
        $data = $prepData['data'] ?? null;
        if (! is_array($data)) {
            return null;
        }

        $included = $prepData['included'] ?? [];
        $relationships = $data['relationships'] ?? [];
        $dirRefs = $relationships['directions']['data'] ?? [];
        $ingRefs = $relationships['ingredients']['data'] ?? [];

        $byTypeId = [];
        foreach ((array) $included as $resource) {
            if (isset($resource['type'], $resource['id'])) {
                $byTypeId[$resource['type'] . ':' . $resource['id']] = $resource;
            }
        }

        $directions = [];
        foreach ((array) $dirRefs as $ref) {
            $key = ($ref['type'] ?? '') . ':' . ($ref['id'] ?? '');
            if (isset($byTypeId[$key])) {
                $directions[] = $byTypeId[$key];
            }
        }

        $ingredients = [];
        foreach ((array) $ingRefs as $ref) {
            $key = ($ref['type'] ?? '') . ':' . ($ref['id'] ?? '');
            if (isset($byTypeId[$key])) {
                $ingredients[] = $byTypeId[$key];
            }
        }

        return [
            'directions' => $directions,
            'ingredients' => $ingredients,
        ];
    }

    /**
     * Normalize preparation from API (JSON:API or flat) for the edit form view.
     * Form expects directions as [ ['notes' => string, 'parsed_json' => string], ... ]
     * and ingredients as [ ['amount' => ?, 'measure' => ?, 'product' => ?, 'notes' => ?], ... ].
     */
    private function normalizePreparationForView(?array $preparation): ?array
    {
        if ($preparation === null) {
            return null;
        }

        $rawDirections = $preparation['directions'] ?? [];
        $directions = [];
        foreach ($rawDirections as $d) {
            if (is_string($d)) {
                $directions[] = ['notes' => $d, 'parsed_json' => ''];
                continue;
            }
            $attrs = is_array($d) ? ($d['attributes'] ?? $d) : [];
            $instruction = $attrs['instruction'] ?? $d['instruction'] ?? '';
            $notes = $attrs['notes'] ?? $d['notes'] ?? '';
            if (is_array($notes)) {
                $notes = implode(' ', array_filter($notes));
            }
            $stepText = (string) $instruction !== '' ? $instruction : ((string) $notes !== '' ? $notes : '');
            if ($stepText === '' && is_array($d)) {
                $parts = [];
                if (! empty($d['operation'])) {
                    $op = $d['operation'];
                    $parts[] = is_array($op)
                        ? ($op['name'] ?? $op['title'] ?? '')
                        : (string) $op;
                }
                if (! empty($d['product'])) {
                    $prod = $d['product'];
                    $parts[] = is_array($prod)
                        ? ($prod['name'] ?? $prod['slug'] ?? '')
                        : (string) $prod;
                }
                $stepText = implode(' ', array_filter($parts));
            }
            $notes = (string) $stepText;
            $parsed = $attrs['parsed_json'] ?? $d['parsed_json'] ?? '';
            if ($parsed === '' && is_array($d)) {
                $parsed = json_encode($d);
            }
            $duration = $attrs['duration-minutes'] ?? $d['duration-minutes'] ?? $d['duration'] ?? null;
            $directions[] = [
                'notes' => (string) $notes,
                'parsed_json' => (string) $parsed,
                'duration' => $duration !== null ? (int) $duration : null,
            ];
        }

        $rawIngredients = $preparation['ingredients'] ?? [];
        $ingredients = [];
        foreach ($rawIngredients as $i) {
            if (is_string($i)) {
                $ingredients[] = ['amount' => '', 'measure' => '', 'product' => $i, 'notes' => ''];
                continue;
            }
            $attrs = is_array($i) ? ($i['attributes'] ?? $i) : [];
            $product = $attrs['product'] ?? $i['product'] ?? $attrs['product-name'] ?? $attrs['product-slug'] ?? '';
            $measure = $attrs['measure'] ?? $i['measure'] ?? $attrs['measure-symbol'] ?? '';
            $notesVal = $attrs['notes'] ?? $i['notes'] ?? '';
            $notesStr = is_array($notesVal) ? implode(' ', array_filter($notesVal)) : (string) $notesVal;
            $ingredients[] = [
                'amount' => (string) ($attrs['amount'] ?? $i['amount'] ?? ''),
                'measure' => $this->preparationValueToString($measure),
                'product' => $this->preparationValueToString($product, ['name', 'slug', 'title']),
                'notes' => $notesStr,
            ];
        }

        return [
            'directions' => $directions,
            'ingredients' => $ingredients,
        ];
    }

    /**
     * Convert a preparation attribute (product, measure) to a string for the form.
     */
    private function preparationValueToString(
        mixed $value,
        array $objectKeys = ['abbreviation', 'name', 'slug'],
    ): string {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            foreach ($objectKeys as $key) {
                if (isset($value[$key]) && (is_string($value[$key]) || is_numeric($value[$key]))) {
                    return (string) $value[$key];
                }
            }
        }

        return '';
    }

    /**
     * @return array{0: array, 1: array}
     */
    private function normalizePreparation(array $rawDirections, array $rawIngredients): array
    {
        $directions = collect($rawDirections)
            ->filter(fn ($d) => ! empty($d['notes']))
            ->values()
            ->map(function ($d) {
                if (! empty($d['parsed_json'])) {
                    $parsed = json_decode($d['parsed_json'], true);
                    if (is_array($parsed)) {
                        $parsed['notes'] = $d['notes'] ?? null;

                        return array_filter($parsed, fn ($v) => $v !== null && $v !== '' && $v !== []);
                    }
                }

                return array_filter(['notes' => $d['notes'] ?? null], fn ($v) => $v !== null && $v !== '');
            })
            ->all();

        $ingredients = collect($rawIngredients)
            ->filter(fn ($i) => ! empty($i['product']))
            ->values()
            ->map(fn ($i) => array_filter([
                'amount' => $i['amount'] ?? null,
                'measure' => $i['measure'] ?? null,
                'product' => $i['product'] ?? null,
                'notes' => $i['notes'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''))
            ->all();

        return [$directions, $ingredients];
    }

    private function savePreparation(string $slug, Request $request): void
    {
        [$directions, $ingredients] = $this->normalizePreparation(
            $request->input('directions', []),
            $request->input('ingredients', [])
        );

        if (empty($directions) && empty($ingredients)) {
            return;
        }

        $payload = [
            'data' => [
                'type' => 'preparations',
                'attributes' => array_filter([
                    'directions' => $directions ?: null,
                    'ingredients' => $ingredients ?: null,
                ], fn ($v) => $v !== null),
            ],
        ];
        $response = $this->api->postJsonApi("/v1/recipes/{$slug}/preparation", $payload, $this->token());
        if (! $response->successful() && $this->isPreparationMethodNotSupported($response)) {
            return;
        }
    }

    private function isPreparationMethodNotSupported(Response $response): bool
    {
        if ($response->status() !== 405 && $response->status() !== 422) {
            return false;
        }
        $body = strtolower($response->body());

        return str_contains($body, 'method')
            && (
                str_contains($body, 'not supported')
                || str_contains($body, 'supported methods')
            );
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

    /**
     * User is an author when /v1/me includes data.attributes.author (object); when no author profile, author is null.
     */
    protected function isAuthor(): bool
    {
        $user = session('user');
        if (! is_array($user)) {
            return false;
        }

        $author = $user['author'] ?? null;

        return $author !== null && $author !== '';
    }
}
