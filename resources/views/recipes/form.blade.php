@extends('layouts.app')
@section('title', ($editing ? 'Edit' : 'Create') . ' Recipe — Foodbook')

@section('content')
@php
    $inputCls = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none';
    $slug = $recipe['slug'] ?? $recipe['id'] ?? '';
    $cuisines = $cuisines ?? [];
    $mainCuisines = $cuisines !== [] ? $cuisines : [
        'american' => 'American', 'british' => 'British', 'caribbean' => 'Caribbean', 'chinese' => 'Chinese',
        'french' => 'French', 'greek' => 'Greek', 'indian' => 'Indian', 'italian' => 'Italian',
        'japanese' => 'Japanese', 'korean' => 'Korean', 'mediterranean' => 'Mediterranean', 'mexican' => 'Mexican',
        'middle-eastern' => 'Middle Eastern', 'nordic' => 'Nordic', 'thai' => 'Thai', 'vietnamese' => 'Vietnamese',
        'other' => 'Other',
    ];
    $subCuisines = [
        'american' => ['southern' => 'Southern', 'cajun' => 'Cajun', 'creole' => 'Creole', 'southwestern' => 'Southwestern', 'other' => 'Other'],
        'chinese' => ['cantonese' => 'Cantonese', 'sichuan' => 'Sichuan', 'hunan' => 'Hunan', 'dim-sum' => 'Dim Sum', 'other' => 'Other'],
        'french' => ['provencal' => 'Provençal', 'normandy' => 'Normandy', 'alsace' => 'Alsace', 'other' => 'Other'],
        'indian' => ['north-indian' => 'North Indian', 'south-indian' => 'South Indian', 'bengali' => 'Bengali', 'other' => 'Other'],
        'italian' => ['northern' => 'Northern', 'southern' => 'Southern', 'tuscan' => 'Tuscan', 'sicilian' => 'Sicilian', 'other' => 'Other'],
        'japanese' => ['washoku' => 'Washoku', 'kaiseki' => 'Kaiseki', 'other' => 'Other'],
        'mexican' => ['yucatecan' => 'Yucatecan', 'oaxacan' => 'Oaxacan', 'other' => 'Other'],
        'middle-eastern' => ['lebanese' => 'Lebanese', 'persian' => 'Persian', 'turkish' => 'Turkish', 'other' => 'Other'],
        'thai' => ['central' => 'Central', 'northern' => 'Northern', 'other' => 'Other'],
    ];
@endphp

<div>
    <a href="{{ $editing ? route('recipes.show', $slug) : route('recipes.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-600 transition hover:text-gray-900">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Back
    </a>

    <h1 class="mb-6 text-2xl font-bold text-gray-900">{{ $editing ? 'Edit Recipe' : 'Create Recipe' }}</h1>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-600">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form
        method="POST"
        action="{{ $editing ? route('recipes.update', $slug) : route('recipes.store') }}"
        class="space-y-8"
        id="recipe-form"
    >
        @csrf
        @if($editing) @method('PATCH') @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>
            </div>
            <div class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required
                        value="{{ old('title', $recipe['title'] ?? '') }}"
                        class="{{ $inputCls }} mt-1" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="cuisine" class="block text-sm font-medium text-gray-700">Cuisine <span class="text-red-500">*</span></label>
                        <select id="cuisine" name="cuisine" required class="{{ $inputCls }} mt-1">
                            <option value="">— Select cuisine —</option>
                            @foreach($mainCuisines as $val => $label)
                                <option value="{{ $val }}" @selected(old('cuisine', is_array($recipe['cuisine'] ?? null) ? ($recipe['cuisine']['slug'] ?? $recipe['cuisine']['id'] ?? '') : ($recipe['cuisine'] ?? '')) === (string)$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($cuisines === [])
                            <p class="mt-1 text-xs text-amber-600">Using fallback list. Use "Other" and type the cuisine below if needed.</p>
                        @endif
                    </div>
                    <div id="region-wrap" class="hidden">
                        <label for="region" class="block text-sm font-medium text-gray-700">Region</label>
                        <select id="region" name="region" class="{{ $inputCls }} mt-1">
                            <option value="">— Optional —</option>
                        </select>
                    </div>
                </div>
                <div id="cuisine-request-wrap" class="hidden">
                    <label for="cuisine_request" class="block text-sm font-medium text-gray-700">Cuisine name (if not in list)</label>
                    <input type="text" id="cuisine_request" name="cuisine_request" maxlength="100"
                        value="{{ old('cuisine_request', $recipe['cuisine_request'] ?? '') }}"
                        placeholder="e.g. Basque, fusion"
                        class="{{ $inputCls }} mt-1" />
                </div>
                <script type="application/json" id="region-data">@json($subCuisines)</script>
                <script>
                (function() {
                    var cuisineEl = document.getElementById('cuisine');
                    var wrap = document.getElementById('region-wrap');
                    var regionEl = document.getElementById('region');
                    var requestWrap = document.getElementById('cuisine-request-wrap');
                    var data = JSON.parse(document.getElementById('region-data').textContent || '{}');
                    var currentRegion = @json(old('region', $recipe['region'] ?? ''));
                    function updateRegion() {
                        var main = (cuisineEl && cuisineEl.value) || '';
                        var opts = data[main];
                        wrap.classList.toggle('hidden', !opts || Object.keys(opts).length === 0);
                        if (regionEl) {
                            regionEl.innerHTML = '<option value="">— Optional —</option>';
                            if (opts) {
                                for (var val in opts) {
                                    var opt = document.createElement('option');
                                    opt.value = val;
                                    opt.textContent = opts[val];
                                    if (currentRegion === val) opt.selected = true;
                                    regionEl.appendChild(opt);
                                }
                            }
                        }
                        currentRegion = '';
                        requestWrap.classList.toggle('hidden', main !== 'other');
                    }
                    if (cuisineEl) cuisineEl.addEventListener('change', updateRegion);
                    updateRegion();
                })();
                </script>

                {{-- Shown only before create: [Create] button --}}
                <div id="create-block" class="pt-2 {{ $editing ? 'hidden' : '' }}">
                    <button type="button" id="create-recipe-btn"
                        class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                        Create
                    </button>
                </div>

                {{-- Shown after creation: Description, Difficulty, Serves, Status (Draft), [Add directions] --}}
                <div id="basic-after-create" class="space-y-4 {{ $editing ? '' : 'hidden' }}">
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="{{ $inputCls }} mt-1">{{ old('description', $recipe['description'] ?? '') }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <span class="block text-sm font-medium text-gray-700">Prep time</span>
                            <p class="mt-1 text-lg font-semibold text-gray-900"><span id="prep-time-display">0</span> min</p>
                            <input type="hidden" id="prep_time_minutes" name="prep_time_minutes" value="{{ old('prep_time_minutes', $recipe['prep_time_minutes'] ?? $recipe['prep-time-minutes'] ?? '0') }}" />
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700">Cook time</span>
                            <p class="mt-1 text-lg font-semibold text-gray-900"><span id="cook-time-display">0</span> min</p>
                            <input type="hidden" id="cook_time_minutes" name="cook_time_minutes" value="{{ old('cook_time_minutes', $recipe['cook_time_minutes'] ?? $recipe['cook-time-minutes'] ?? '0') }}" />
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Times are counted from step durations when you add directions.</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="difficulty" class="block text-sm font-medium text-gray-700">Difficulty</label>
                            <select id="difficulty" name="difficulty" class="{{ $inputCls }} mt-1">
                                <option value="">—</option>
                                @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('difficulty', $recipe['difficulty'] ?? '') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="serves" class="block text-sm font-medium text-gray-700">Serves</label>
                            <input type="number" id="serves" name="serves" min="1"
                                value="{{ old('serves', $recipe['serves'] ?? '') }}"
                                class="{{ $inputCls }} mt-1" />
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700">Status</span>
                            <p class="mt-1 rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500">Draft</p>
                            <input type="hidden" name="status" value="draft" />
                        </div>
                    </div>
                    @if(!$editing || empty(($preparation ?? [])['directions'] ?? []))
                    <div class="pt-2">
                        <button type="button" id="add-directions-btn"
                            class="rounded-lg border-2 border-dashed border-primary-300 bg-primary-50/50 px-6 py-3 text-sm font-medium text-primary-700 transition hover:border-primary-500 hover:bg-primary-100">
                            Add directions
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </section>

        <div id="steps-section" class="hidden space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Directions</h2>
                <p class="text-sm text-gray-500">Each step is the sentence you entered. Ingredients and times are taken from detection when you add.</p>
            </div>

            <div id="directions-list" class="space-y-2"></div>
            <p id="directions-empty" class="py-4 text-center text-sm text-gray-400">No steps yet. Describe your first step below.</p>

            <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50/50 p-4">
                <label for="step-input" class="mb-1.5 block text-xs font-medium text-gray-500">Describe the next step</label>
                <div class="flex gap-2">
                    <input type="text" id="step-input"
                        placeholder="e.g. Roast the chicken in the oven for 40-45 minutes at 475°F"
                        class="{{ $inputCls }} flex-1" />
                    <button type="button" id="add-step-btn"
                        class="shrink-0 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700">
                        Add
                    </button>
                </div>
                <div id="step-preview" class="mt-3 hidden rounded-lg border border-primary-200 bg-primary-50/50 p-3">
                    <p class="mb-2 text-xs font-medium text-gray-500">Detected:</p>
                    <div id="detected-inline" class="text-sm leading-relaxed"></div>
                    <details id="step-json-details" class="mt-2 hidden">
                        <summary class="cursor-pointer text-xs text-gray-400 hover:text-gray-600">Show JSON</summary>
                        <pre id="json-output" class="mt-1 max-h-48 overflow-auto rounded bg-gray-900 p-2.5 text-xs leading-relaxed text-green-400"></pre>
                    </details>
                </div>
            </div>

        </section>

        <section id="tools-required-wrap" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm hidden">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Tools required</h2>
                <p class="text-sm text-gray-500">Auto-detected from steps when you add directions.</p>
            </div>
            <div id="tools-required-list" class="flex flex-wrap gap-2"></div>
            <p id="tools-empty" class="py-4 text-center text-sm text-gray-400">No tools yet. They will appear as you add steps.</p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Ingredients</h2>
                    <p class="text-sm text-gray-500">{{ $editing ? 'Calculated from directions. Updated when you add or remove steps.' : 'Auto-detected from steps. Adjust amounts and measures as needed.' }}</p>
                </div>
                @if(!$editing)
                <button type="button" id="add-ingredient-btn"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    + Add
                </button>
                @endif
            </div>
            <div id="ingredients-list" class="space-y-2"></div>
            <p id="ingredients-empty" class="py-4 text-center text-sm text-gray-400">No ingredients yet. They will appear as you add steps.</p>
        </section>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" id="submit-recipe-btn"
                class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 {{ $editing ? '' : 'hidden' }}">
                Update Recipe
            </button>
            <p id="created-msg" class="hidden text-sm font-medium text-green-600">Recipe created. Add directions below or edit details.</p>
            <a id="cancel-recipe-btn" href="{{ $editing ? route('recipes.show', $slug) : route('recipes.index') }}"
                class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>

<template id="direction-tpl">
    <div class="direction-row flex items-start gap-2 rounded-lg border border-gray-200 bg-white p-3">
        <span class="direction-num mt-2 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-50 text-xs font-semibold text-primary-700">1</span>
        <input type="text" data-field="notes" placeholder="Step description (sentence)"
            class="{{ $inputCls }} flex-1" />
        <input type="hidden" data-field="parsed_json" value="" />
        <button type="button" class="remove-btn mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</template>

<template id="ingredient-tpl">
    <div class="ingredient-row rounded-lg border border-gray-200 bg-white p-3">
        <div class="grid grid-cols-[1fr_1fr_2fr_2fr_2rem] items-center gap-2">
            <input type="text" data-field="amount" placeholder="Amount"
                class="{{ $inputCls }}" />
            <input type="text" data-field="measure" placeholder="Measure"
                class="{{ $inputCls }}" />
            <input type="text" data-field="product" placeholder="Product"
                class="{{ $inputCls }}" />
            <input type="text" data-field="notes" placeholder="Notes"
                class="{{ $inputCls }}" />
            <button type="button" class="remove-btn flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</template>

{{-- Product not found modal: create product then retry direction, or pick a suggested product (overlay / side step) --}}
<div id="product-not-found-modal" class="fixed inset-0 z-[60] flex hidden items-center justify-center p-4" aria-hidden="true" style="background: rgba(0,0,0,0.55); backdrop-filter: blur(4px);">
    <div class="w-full max-w-md rounded-2xl border-2 border-primary-200 bg-white p-6 shadow-2xl ring-4 ring-primary-500/10" role="dialog" aria-labelledby="product-not-found-title" aria-modal="true" style="box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.05);">
        <h2 id="product-not-found-title" class="text-lg font-semibold text-gray-900">Add missing product</h2>
        <p id="product-not-found-message" class="mt-2 text-sm text-gray-600"></p>
        <p id="product-create-measure-info" class="mt-1 text-xs text-gray-500 hidden"></p>
        <div id="product-suggested-wrap" class="mt-3 hidden">
            <p class="text-sm font-medium text-gray-700">Did you mean?</p>
            <ul id="product-suggested-list" class="mt-1.5 space-y-1"></ul>
        </div>
        <form id="product-not-found-form" class="mt-4 space-y-3">
            <div>
                <label for="product-create-name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                <input type="text" id="product-create-name" name="name" required maxlength="255"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none" />
            </div>
            <div>
                <label for="product-create-slug" class="block text-sm font-medium text-gray-700">Slug (optional)</label>
                <input type="text" id="product-create-slug" name="slug" maxlength="255"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                    placeholder="Leave empty to generate from name" />
            </div>
            <div>
                <label for="product-create-description" class="block text-sm font-medium text-gray-700">Description (optional)</label>
                <textarea id="product-create-description" name="description" rows="2"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"></textarea>
            </div>
            <div id="product-create-errors" class="hidden text-sm text-red-600"></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" id="product-not-found-cancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" id="product-create-submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
                    Create product
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var directionsEl = document.getElementById('directions-list');
    var ingredientsEl = document.getElementById('ingredients-list');
    var dirTpl = document.getElementById('direction-tpl');
    var ingTpl = document.getElementById('ingredient-tpl');
    var dirEmpty = document.getElementById('directions-empty');
    var ingEmpty = document.getElementById('ingredients-empty');

    var stepInput = document.getElementById('step-input');
    var addStepBtn = document.getElementById('add-step-btn');
    var previewEl = document.getElementById('step-preview');
    var detectedInline = document.getElementById('detected-inline');
    var jsonDetails = document.getElementById('step-json-details');
    var jsonOutput = document.getElementById('json-output');
    var toolsRequiredWrap = document.getElementById('tools-required-wrap');
    var toolsRequiredList = document.getElementById('tools-required-list');
    var toolsEmpty = document.getElementById('tools-empty');
    var prepTimeInput = document.getElementById('prep_time_minutes');
    var cookTimeInput = document.getElementById('cook_time_minutes');
    var prepTimeDisplay = document.getElementById('prep-time-display');
    var cookTimeDisplay = document.getElementById('cook-time-display');

    var APP_DEBUG = @json(config('app.debug', false));
    var EDITING = @json($editing);
    var recipeSlug = @json($slug ?? null);
    var dirIdx = 0;
    var ingIdx = 0;
    var lastParsed = null;
    var lastParsedText = '';
    var toolsRequiredSet = {};

    var stepsSection = document.getElementById('steps-section');
    var submitBtn = document.getElementById('submit-recipe-btn');
    var createdMsg = document.getElementById('created-msg');
    var createBlock = document.getElementById('create-block');
    var basicAfterCreate = document.getElementById('basic-after-create');
    var addDirectionsBtn = document.getElementById('add-directions-btn');
    var createRecipeBtn = document.getElementById('create-recipe-btn');
    var recipeForm = document.getElementById('recipe-form');

    var PARSE_URL = '{{ route("recipes.parse-direction") }}';
    var STORE_URL = '{{ route("recipes.store") }}';
    var PRODUCTS_STORE_URL = '{{ route("products.store") }}';
    var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    function getUpdateUrl() { return '{{ url("recipes") }}/' + recipeSlug; }
    function getPreparationUrl() { return '{{ url("recipes") }}/' + recipeSlug + '/preparation'; }
    function getDirectionsFromTextUrl() { return '{{ url("recipes") }}/' + recipeSlug + '/directions/from-text'; }
    function getDirectionDeleteUrl(id) { return '{{ url("recipes") }}/' + recipeSlug + '/directions/' + encodeURIComponent(id); }

    if (!EDITING) {
        if (submitBtn) submitBtn.classList.add('hidden');
    }

    /* ── basic payload for create/update ── */

    function collectBasicPayload() {
        var f = recipeForm;
        return {
            title: (f.querySelector('[name="title"]') || {}).value || '',
            cuisine: (f.querySelector('[name="cuisine"]') || {}).value || '',
            region: (f.querySelector('[name="region"]') || {}).value || '',
            cuisine_request: (f.querySelector('[name="cuisine_request"]') || {}).value || '',
            description: (f.querySelector('[name="description"]') || {}).value || '',
            prep_time_minutes: (f.querySelector('[name="prep_time_minutes"]') || {}).value || '',
            cook_time_minutes: (f.querySelector('[name="cook_time_minutes"]') || {}).value || '',
            difficulty: (f.querySelector('[name="difficulty"]') || {}).value || '',
            serves: (f.querySelector('[name="serves"]') || {}).value || '',
            status: (f.querySelector('[name="status"]') || {}).value || ''
        };
    }

    var currentIngredients = [];

    function renderReadOnlyIngredients() {
        if (!ingredientsEl) return;
        ingredientsEl.innerHTML = '';
        var list = currentIngredients;
        list.forEach(function (ing) {
            var amount = (ing.amount != null && ing.amount !== '') ? ing.amount : '';
            var measure = (ing.measure != null && ing.measure !== '') ? ing.measure : '';
            var product = (ing.product != null && ing.product !== '') ? ing.product : '';
            var notes = (ing.notes != null && ing.notes !== '') ? ing.notes : '';
            var parts = [];
            if (amount) parts.push(amount);
            if (measure) parts.push(measure);
            if (product) parts.push(product);
            var line = parts.join(' ');
            if (notes) line += (line ? ' — ' : '') + notes;
            if (!line) return;
            var div = document.createElement('div');
            div.className = 'rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700';
            div.textContent = line;
            ingredientsEl.appendChild(div);
        });
        toggleEmpty();
    }

    function refreshIngredientsFromApi() {
        if (!EDITING || !recipeSlug) return;
        fetch(getPreparationUrl(), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (pr) { return pr.ok ? pr.json() : null; })
            .then(function (prep) {
                if (prep && Array.isArray(prep.ingredients)) {
                    currentIngredients = prep.ingredients.map(function (ing) {
                        return {
                            amount: (ing.amount != null) ? String(ing.amount) : '',
                            measure: (ing.measure != null) ? String(ing.measure) : '',
                            product: (ing.product != null) ? String(ing.product) : '',
                            notes: (ing.notes != null) ? String(ing.notes) : ''
                        };
                    });
                    renderReadOnlyIngredients();
                }
            })
            .catch(function (err) { console.warn('Refresh ingredients failed:', err); });
    }

    function collectDirectionsAndIngredients() {
        var directions = [];
        directionsEl.querySelectorAll('.direction-row').forEach(function (row) {
            var notesInp = row.querySelector('[data-field="notes"]');
            var jsonInp = row.querySelector('[data-field="parsed_json"]');
            var notes = notesInp ? notesInp.value : '';
            var parsed = jsonInp ? jsonInp.value : '';
            if (notes) directions.push({ notes: notes, parsed_json: parsed });
        });
        var ingredients;
        if (EDITING) {
            ingredients = currentIngredients.map(function (ing) {
                return {
                    amount: (ing.amount != null) ? String(ing.amount) : '',
                    measure: (ing.measure != null) ? String(ing.measure) : '',
                    product: (ing.product != null) ? String(ing.product) : '',
                    notes: (ing.notes != null) ? String(ing.notes) : ''
                };
            });
        } else {
            ingredients = [];
            ingredientsEl.querySelectorAll('.ingredient-row').forEach(function (row) {
                var amount = (row.querySelector('[data-field="amount"]') || {}).value || '';
                var measure = (row.querySelector('[data-field="measure"]') || {}).value || '';
                var product = (row.querySelector('[data-field="product"]') || {}).value || '';
                var notes = (row.querySelector('[data-field="notes"]') || {}).value || '';
                if (product) ingredients.push({ amount: amount, measure: measure, product: product, notes: notes });
            });
        }
        return { directions: directions, ingredients: ingredients };
    }

    function switchToEditMode(slug) {
        recipeSlug = slug;
        if (createBlock) createBlock.classList.add('hidden');
        if (basicAfterCreate) basicAfterCreate.classList.remove('hidden');
        if (submitBtn) submitBtn.classList.remove('hidden');
        if (createdMsg) createdMsg.classList.remove('hidden');
        recipeForm.action = getUpdateUrl();
        var method = recipeForm.querySelector('input[name="_method"]');
        if (!method) {
            method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            recipeForm.appendChild(method);
        }
        method.value = 'PATCH';
        var cancelBtn = document.getElementById('cancel-recipe-btn');
        if (cancelBtn) cancelBtn.href = '{{ url("recipes") }}/' + slug;
    }

    var basicSaveTimer;
    function scheduleBasicSave() {
        if (!recipeSlug) return;
        clearTimeout(basicSaveTimer);
        basicSaveTimer = setTimeout(function () {
            var payload = collectBasicPayload();
            fetch(getUpdateUrl(), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function (r) {
                if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return Promise.reject();
                if (!r.ok) return r.json().then(function (d) {
                    var _up = window.updateLastApiResponseDetail || (window.top && window.top.updateLastApiResponseDetail); if (_up) _up(d);
                    throw new Error(d.error || d.errors && d.errors[0] && d.errors[0].detail || 'Update failed');
                });
            }).catch(function (e) { console.error('Update error:', e); });
        }, 600);
    }

    var preparationSaveTimer;
    function schedulePreparationSave() {
        if (!EDITING || !recipeSlug) return;
        clearTimeout(preparationSaveTimer);
        preparationSaveTimer = setTimeout(function () {
            var payload = collectDirectionsAndIngredients();
            var url = getPreparationUrl();
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function (r) {
                if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return Promise.reject();
                if (!r.ok) return r.json().then(function (d) {
                    var _up = window.updateLastApiResponseDetail || (window.top && window.top.updateLastApiResponseDetail); if (_up) _up(d);
                    throw new Error(d.error || d.errors && d.errors[0] && d.errors[0].detail || 'Preparation update failed');
                });
            }).catch(function (e) { console.error('Preparation save error:', e); });
        }, 400);
    }

    function doCreateRecipe() {
        var p = collectBasicPayload();
        if (!p.title || !p.cuisine) {
            recipeForm.reportValidity();
            return;
        }
        if (!createRecipeBtn) return;
        createRecipeBtn.disabled = true;
        createRecipeBtn.textContent = '…';
        fetch(STORE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify(p)
        }).then(function (r) {
            if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return Promise.reject();
            if (!r.ok) return r.json().then(function (d) {
                var _up = window.updateLastApiResponseDetail || (window.top && window.top.updateLastApiResponseDetail); if (_up) _up(d);
                throw new Error(d.error || d.errors && d.errors[0] && d.errors[0].detail || 'Create failed');
            });
            return r.json();
        }).then(function (data) {
            if (data && data.slug) switchToEditMode(data.slug);
        }).catch(function (e) {
            console.error('Create error:', e);
            createRecipeBtn.disabled = false;
            createRecipeBtn.textContent = 'Create';
        }).then(function () {
            if (createRecipeBtn && !createRecipeBtn.disabled) createRecipeBtn.textContent = 'Create';
        });
    }

    /* ── server-side parser ── */

    function serverParse(sentence, onSuccess, onError) {
        fetch(PARSE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ sentence: sentence })
        })
        .then(function (r) {
            if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return Promise.reject();
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(onSuccess)
        .catch(function (e) {
            console.error('Parse error:', e);
            if (onError) onError(e);
        });
    }

    /* ── live preview: "Detected" inline (ingredients / operation / duration / tools) ── */

    function formatIngredientLabel(ing) {
        var q = ing.quantity;
        var parts = [];
        if (q && q.amount != null) parts.push(String(q.amount));
        if (q && q.unit) parts.push(q.unit);
        parts.push(ing.name || '');
        return parts.join(' ').trim();
    }

    function showPreview(steps) {
        if (!steps || !steps.length) { previewEl.classList.add('hidden'); return; }

        var existing = existingProducts();
        var fragments = [];
        var totalDurMinutes = 0;
        var seenToolKeys = {};
        var seenIngKeys = {};

        function span(cls, text, wrapPlus) {
            if (!text) return;
            var s = document.createElement('span');
            s.className = cls;
            s.textContent = wrapPlus ? '[+ ' + String(text).trim() + ']' : String(text).trim();
            fragments.push(s);
        }

        function addTool(t) {
            if (!t || !t.name) return;
            var label = (t.size ? t.size + ' ' : '') + t.name;
            var key = label.toLowerCase().trim();
            if (seenToolKeys[key]) return;
            seenToolKeys[key] = true;
            span('inline-block rounded px-1.5 py-0.5 font-medium text-purple-700 bg-purple-100', label, true);
        }

        function addIngredient(ing) {
            var label = formatIngredientLabel(ing);
            if (!label) return;
            var name = (ing.name || '').toLowerCase().trim();
            var isNew = name && !existing[name];
            var key = label.toLowerCase();
            if (seenIngKeys[key]) return;
            seenIngKeys[key] = true;
            span('inline-block rounded px-1.5 py-0.5 font-medium text-green-700 bg-green-100', label, isNew);
        }

        steps.forEach(function (step) {
            if (step.tool) addTool(step.tool);
            if (step.tools && step.tools.length) step.tools.forEach(addTool);
            (step.ingredients || step.targets || []).forEach(addIngredient);
            var dur = step.duration;
            var mins = dur && dur.unit === 'hours' ? dur.value * 60 : (dur ? dur.value : 0);
            if (mins) totalDurMinutes += mins;
        });

        if (totalDurMinutes) span('inline-block rounded px-1.5 py-0.5 font-medium text-amber-700 bg-amber-100', totalDurMinutes + ' min', true);

        detectedInline.innerHTML = '';
        fragments.forEach(function (el) {
            detectedInline.appendChild(el);
            if (fragments.indexOf(el) < fragments.length - 1) detectedInline.appendChild(document.createTextNode(' '));
        });

        if (APP_DEBUG) {
            jsonDetails.classList.remove('hidden');
            jsonOutput.textContent = JSON.stringify(steps, null, 2);
        } else {
            jsonDetails.classList.add('hidden');
        }

        previewEl.classList.toggle('hidden', fragments.length === 0 && !APP_DEBUG);
    }

    var previewTimer;
    stepInput.addEventListener('input', function () {
        clearTimeout(previewTimer);
        var text = stepInput.value.trim();
        if (!text) { previewEl.classList.add('hidden'); lastParsed = null; lastParsedText = ''; return; }
        previewTimer = setTimeout(function () {
            serverParse(text, function (steps) {
                lastParsed = steps;
                lastParsedText = text;
                showPreview(steps);
            });
        }, 250);
    });

    /* ── helpers ── */

    function textOf(val) {
        if (val === null || val === undefined) return '';
        if (typeof val === 'object') return val.name || val.abbreviation || val.title || '';
        return String(val);
    }

    function toggleEmpty() {
        var hasDirs = directionsEl.children.length > 0;
        var hasIngs = ingredientsEl.children.length > 0;
        dirEmpty.classList.toggle('hidden', hasDirs);
        ingEmpty.classList.toggle('hidden', hasIngs);
    }

    function renumberDirections() {
        directionsEl.querySelectorAll('.direction-num').forEach(function (el, i) { el.textContent = i + 1; });
    }

    function existingProducts() {
        var set = {};
        if (EDITING) {
            currentIngredients.forEach(function (ing) {
                var v = (ing.product || '').trim().toLowerCase();
                if (v) set[v] = true;
            });
            return set;
        }
        ingredientsEl.querySelectorAll('[data-field="product"]').forEach(function (el) {
            var v = el.value.trim().toLowerCase();
            if (v) set[v] = true;
        });
        return set;
    }

    function durationToMinutes(dur) {
        if (!dur) return null;
        var val = dur.value;
        if (dur.unit === 'hours') val *= 60;
        if (dur.unit === 'seconds') val = Math.max(1, Math.round(val / 60));
        return val;
    }

    /* ── row management ── */

    function addDirection(data) {
        data = data || {};
        var idx = dirIdx++;
        var clone = dirTpl.content.cloneNode(true);
        var row = clone.querySelector('.direction-row');

        row.querySelectorAll('[data-field]').forEach(function (input) {
            var field = input.dataset.field;
            input.name = 'directions[' + idx + '][' + field + ']';
            input.value = textOf(data[field]);
        });

        row.querySelector('.remove-btn').addEventListener('click', function () {
            var jsonInp = row.querySelector('[data-field="parsed_json"]');
            var parsed = jsonInp ? jsonInp.value : '';
            var directionId = null;
            if (parsed) {
                try {
                    var d = JSON.parse(parsed);
                    directionId = d && (d.id != null ? d.id : (d.data && d.data.id != null ? d.data.id : null));
                } catch (e) {}
            }
            if (directionId != null && EDITING && recipeSlug) {
                var deleteUrl = getDirectionDeleteUrl(directionId);
                fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
                }).then(function (r) {
                    if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return;
                    return r.json().catch(function () { return null; }).then(function (body) {
                        if (body && body.meta && (body.meta.api_url != null || body.meta.api_path != null || body.meta.api_status != null)) {
                            var addApi = window.addServerApiLogEntries || (window.top && window.top.addServerApiLogEntries);
                            if (addApi) addApi([{
                                method: 'DELETE',
                                url: body.meta.api_url || body.meta.api_path || ('/v1/recipes/' + recipeSlug + '/directions/' + directionId),
                                status: body.meta.api_status != null ? body.meta.api_status : r.status,
                                message: (body.meta.message || r.statusText || 'OK')
                            }]);
                        }
                        if (r.ok && EDITING && recipeSlug) {
                            fetch(getPreparationUrl(), {
                                headers: { 'Accept': 'application/json' },
                                credentials: 'same-origin'
                            }).then(function (pr) { return pr.ok ? pr.json() : null; })
                                .then(function (prep) {
                                    if (prep && Array.isArray(prep.ingredients)) {
                                        currentIngredients = prep.ingredients.map(function (ing) {
                                            return {
                                                amount: (ing.amount != null) ? String(ing.amount) : '',
                                                measure: (ing.measure != null) ? String(ing.measure) : '',
                                                product: (ing.product != null) ? String(ing.product) : '',
                                                notes: (ing.notes != null) ? String(ing.notes) : ''
                                            };
                                        });
                                        renderReadOnlyIngredients();
                                    }
                                })
                                .catch(function (err) { console.warn('Refresh ingredients after delete failed:', err); });
                        }
                        if (!r.ok) console.error('Delete direction failed:', body && (body.error || body.errors));
                    });
                }).catch(function (e) { console.error('Delete direction error:', e); });
            }
            row.remove(); renumberDirections(); toggleEmpty();
        });

        directionsEl.appendChild(clone);
        renumberDirections(); toggleEmpty();
    }

    function addIngredient(data) {
        data = data || {};
        var idx = ingIdx++;
        var clone = ingTpl.content.cloneNode(true);
        var row = clone.querySelector('.ingredient-row');

        row.querySelectorAll('[data-field]').forEach(function (input) {
            var field = input.dataset.field;
            input.name = 'ingredients[' + idx + '][' + field + ']';
            input.value = textOf(data[field]);
        });

        row.querySelector('.remove-btn').addEventListener('click', function () {
            row.remove(); toggleEmpty();
            schedulePreparationSave();
        });

        ingredientsEl.appendChild(clone);
        toggleEmpty();
    }

    function ensureIngredient(name, quantity, notes) {
        if (!name || name.length < 2) return;
        if (EDITING) {
            currentIngredients.push({
                product: name,
                amount: quantity && quantity.amount != null ? String(quantity.amount) : '',
                measure: quantity && quantity.unit ? quantity.unit : '',
                notes: notes || '',
            });
            renderReadOnlyIngredients();
            return;
        }
        var have = existingProducts();
        if (!have[name.toLowerCase().trim()]) {
            addIngredient({
                product: name,
                amount: quantity && quantity.amount != null ? String(quantity.amount) : '',
                measure: quantity && quantity.unit ? quantity.unit : '',
                notes: notes || '',
            });
        }
    }

    function ingredientNotesFromParsed(ing) {
        var parts = [];
        if (ing.state) parts.push(ing.state);
        if (ing.optional) parts.push('optional');
        if (ing.size) parts.push(ing.size);
        return parts.join(', ');
    }

    function addToolToList(tool) {
        if (!tool || !tool.name) return;
        var label = (tool.size ? tool.size + ' ' : '') + tool.name;
        var key = label.toLowerCase().trim();
        if (toolsRequiredSet[key]) return;
        toolsRequiredSet[key] = true;
        var chip = document.createElement('span');
        chip.className = 'inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700';
        chip.textContent = label;
        toolsRequiredList.appendChild(chip);
        toolsRequiredWrap.classList.remove('hidden');
        if (toolsEmpty) toolsEmpty.classList.add('hidden');
    }

    /* ── map parsed steps into form rows; add ingredients, prep time, tools ── */

    function applyParsedSteps(steps, originalText) {
        (steps || []).forEach(function (step) {
            var mins = durationToMinutes(step.duration);
            var ings = step.ingredients || step.targets || [];

            addDirection({
                notes: originalText,
                parsed_json: JSON.stringify(step),
            });

            ings.forEach(function (ing) {
                ensureIngredient(ing.name, ing.quantity, ingredientNotesFromParsed(ing));
            });

            if (mins && prepTimeInput) {
                var current = parseInt(prepTimeInput.value, 10) || 0;
                var next = current + mins;
                prepTimeInput.value = next;
                if (prepTimeDisplay) prepTimeDisplay.textContent = next;
            }

            if (step.tool) addToolToList(step.tool);
            if (step.tools && step.tools.length) step.tools.forEach(addToolToList);
        });
    }

    /* ── apply API from-text response: data (directions) + meta (ingredients, prep_time, tools).
     * Prefer attributes.instruction for step text (includes all ingredients for multi-ingredient steps).
     * parsed_json keeps full direction resource (relationships.ingredients etc.) for future use.
     */

    function applyResponseToUi(data, meta, originalText) {
        data = Array.isArray(data) ? data : (data && typeof data === 'object' ? [data] : []);
        meta = meta || {};
        originalText = (originalText && typeof originalText === 'string') ? originalText.trim() : '';
        data.forEach(function (d) {
            var attrs = d.attributes || d;
            var instruction = (attrs.instruction != null ? attrs.instruction : (d.instruction != null ? d.instruction : '')) || '';
            var notesArr = attrs.notes != null ? (Array.isArray(attrs.notes) ? attrs.notes : [attrs.notes]) : (d.notes != null ? (Array.isArray(d.notes) ? d.notes : [d.notes]) : []);
            var notesStr = (notesArr.length > 0 ? notesArr.join(' ') : '') || (attrs.notes != null && typeof attrs.notes === 'string' ? attrs.notes : (d.notes != null && typeof d.notes === 'string' ? d.notes : (d.text != null ? d.text : ''))) || '';
            var stepText = (instruction !== '' ? instruction : (notesStr !== '' ? notesStr : (originalText !== '' ? originalText : '')));
            addDirection({ notes: stepText, parsed_json: typeof d === 'object' ? JSON.stringify(d) : '' });
        });
        var allIngredients = (meta.ingredients || []).slice();
        data.forEach(function (d) {
            var attrs = d.attributes || d;
            var list = attrs.ingredients || d.ingredients;
            if (Array.isArray(list)) list.forEach(function (ing) { allIngredients.push(ing); });
            var rels = d.relationships || {};
            var ingRefs = (rels.ingredients && rels.ingredients.data) || (rels.ingredient && rels.ingredient.data ? [rels.ingredient.data] : []);
            var included = meta.included || [];
            ingRefs.forEach(function (ref) {
                var type = ref.type, id = ref.id;
                var res = included.find(function (r) { return r.type === type && String(r.id) === String(id); });
                if (res) {
                    var a = res.attributes || res;
                    allIngredients.push({ product: a.product || a['product-name'], amount: a.amount, measure: a.measure || a['measure-symbol'], name: a.product || a['product-name'] });
                }
            });
        });
        allIngredients.forEach(function (ing) {
            var name = ing.name || ing.product;
            if (!name) return;
            var qty = ing.quantity || { amount: ing.amount, unit: ing.measure || ing.unit };
            var notes = ing.notes || ingredientNotesFromParsed(ing);
            ensureIngredient(name, qty, notes);
        });
        if (meta.prep_time_minutes != null && prepTimeInput) {
            var current = parseInt(prepTimeInput.value, 10) || 0;
            var next = current + (meta.prep_time_minutes | 0);
            prepTimeInput.value = next;
            if (prepTimeDisplay) prepTimeDisplay.textContent = next;
        }
        (meta.tools || []).forEach(addToolToList);
    }

    /* ── product not found modal: create product then retry direction ── */

    var pendingDirectionTextForRetry = null;
    var pendingProductRefForRetry = null;

    function isProductNotFoundError(d) {
        if (!d || !d.errors || !d.errors[0]) return false;
        var err = d.errors[0];
        return (err.title === 'Product Not Found' || (String(err.status) === '404' && err.detail && err.detail.indexOf('not found') !== -1));
    }

    function parseProductSlugFromDetail(d) {
        if (!d || !d.errors || !d.errors[0] || !d.errors[0].detail) return '';
        var detail = d.errors[0].detail;
        var m = detail.match(/Product '([^']+)' not found\.?/i);
        return m ? m[1] : detail.replace(/^Product\s+/i, '').replace(/\s+not found\.?$/i, '').trim();
    }

    function humanizeSlug(slug) {
        if (!slug) return '';
        return slug.split('-').map(function (w) { return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(); }).join(' ');
    }

    function showProductNotFoundModal(productSlug, directionText, errorResponse) {
        pendingDirectionTextForRetry = directionText;
        pendingProductRefForRetry = productSlug || null;
        var modal = document.getElementById('product-not-found-modal');
        var messageEl = document.getElementById('product-not-found-message');
        var measureEl = document.getElementById('product-create-measure-info');
        var suggestedWrap = document.getElementById('product-suggested-wrap');
        var suggestedList = document.getElementById('product-suggested-list');
        var nameInput = document.getElementById('product-create-name');
        var slugInput = document.getElementById('product-create-slug');
        var descInput = document.getElementById('product-create-description');
        var errorsEl = document.getElementById('product-create-errors');
        if (!modal || !messageEl) return;
        messageEl.textContent = "The product '" + productSlug + "' was not found. You can create it now, then the direction will be added again.";
        var parsedMeasure = null;
        var suggestedProducts = [];
        if (errorResponse && errorResponse.errors && errorResponse.errors[0] && errorResponse.errors[0].meta) {
            var meta = errorResponse.errors[0].meta;
            parsedMeasure = meta['parsed-measure'] || null;
            suggestedProducts = meta['suggested-products'] || [];
        }
        if (measureEl) {
            if (parsedMeasure && (parsedMeasure['measure-slug'] || parsedMeasure.amount != null)) {
                var measureParts = [];
                if (parsedMeasure.amount != null) measureParts.push('Amount: ' + parsedMeasure.amount);
                if (parsedMeasure['measure-slug']) measureParts.push('Measure: ' + parsedMeasure['measure-slug']);
                measureEl.textContent = measureParts.join(', ');
                measureEl.classList.remove('hidden');
            } else {
                measureEl.textContent = '';
                measureEl.classList.add('hidden');
            }
        }
        if (suggestedWrap && suggestedList) {
            suggestedList.innerHTML = '';
            if (suggestedProducts.length > 0) {
                suggestedProducts.forEach(function (p) {
                    var name = (p.name || p.slug || '').trim() || ('Slug: ' + (p.slug || p.id));
                    var slug = p.slug || (p.id != null ? String(p.id) : '');
                    var li = document.createElement('li');
                    li.className = 'flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2';
                    var nameSpan = document.createElement('span');
                    nameSpan.className = 'text-sm text-gray-800 truncate';
                    nameSpan.textContent = name;
                    var useBtn = document.createElement('button');
                    useBtn.type = 'button';
                    useBtn.className = 'shrink-0 rounded border border-primary-500 bg-primary-600 px-2 py-1 text-xs font-medium text-white hover:bg-primary-700';
                    useBtn.textContent = 'Yes, use this';
                    useBtn.dataset.productName = name;
                    useBtn.dataset.productSlug = slug;
                    useBtn.addEventListener('click', function () {
                        var productName = useBtn.dataset.productName || '';
                        if (pendingDirectionTextForRetry && productName) retryDirectionWithProduct(pendingDirectionTextForRetry, pendingProductRefForRetry, productName);
                    });
                    li.appendChild(nameSpan);
                    li.appendChild(useBtn);
                    suggestedList.appendChild(li);
                });
                suggestedWrap.classList.remove('hidden');
            } else {
                suggestedWrap.classList.add('hidden');
            }
        }
        if (nameInput) nameInput.value = humanizeSlug(productSlug);
        if (slugInput) slugInput.value = productSlug;
        if (descInput) descInput.value = '';
        if (errorsEl) { errorsEl.classList.add('hidden'); errorsEl.textContent = ''; }
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        var createBtn = document.getElementById('product-create-submit');
        if (createBtn) {
            createBtn.disabled = false;
            createBtn.onclick = function (e) { e.preventDefault(); e.stopPropagation(); doCreateProduct(); };
        }
        if (nameInput) nameInput.focus();
    }

    function replaceProductRefInDirectionText(text, productRef, productName) {
        if (!text || !productRef || !productName) return text;
        var escapeRe = function (s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); };
        var patternFromSlug = productRef.split('-').map(escapeRe).join('\\s+');
        var regex = new RegExp('\\b' + patternFromSlug + '\\b', 'gi');
        var replaced = text.replace(regex, productName);
        if (replaced === text) replaced = text.replace(new RegExp(escapeRe(productRef), 'gi'), productName);
        return replaced;
    }

    function retryDirectionWithProduct(text, productRef, productName) {
        hideProductNotFoundModal();
        if (!text || !recipeSlug) { addStepBtn.disabled = false; addStepBtn.textContent = 'Add'; return; }
        addStepBtn.disabled = true;
        addStepBtn.textContent = '…';
        var url = getDirectionsFromTextUrl();
        var sendText = (productRef && productName) ? replaceProductRefInDirectionText(text, productRef, productName) : text;
        var payload = { text: sendText };
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (r) {
            if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return Promise.reject();
            if (!r.ok) return r.json().then(function (d) {
                var _up = window.updateLastApiResponseDetail; if (_up) _up(d);
                if (isProductNotFoundError(d)) {
                    var productSlug = parseProductSlugFromDetail(d);
                    showProductNotFoundModal(productSlug, sendText, d);
                    addStepBtn.disabled = false;
                    addStepBtn.textContent = 'Add';
                    return;
                }
                throw new Error(d.error || d.errors && d.errors[0] && d.errors[0].detail || 'Add direction failed');
            });
            return r.json();
        }).then(function (body) {
            if (!body) return;
            applyResponseToUi(body.data, Object.assign({}, body.meta || {}, body.included ? { included: body.included } : {}), sendText);
            finishCommit();
            refreshIngredientsFromApi();
        }).catch(function (err) {
            console.error('Retry direction with product error:', err);
            addStepBtn.disabled = false;
            addStepBtn.textContent = 'Add';
        });
    }

    function hideProductNotFoundModal() {
        var modal = document.getElementById('product-not-found-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
        pendingDirectionTextForRetry = null;
        pendingProductRefForRetry = null;
    }

    function retryDirectionAfterProductCreated() {
        var text = pendingDirectionTextForRetry;
        hideProductNotFoundModal();
        if (!text || !recipeSlug) { addStepBtn.disabled = false; addStepBtn.textContent = 'Add'; return; }
        var url = getDirectionsFromTextUrl();
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ text: text })
        }).then(function (r) {
            if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return Promise.reject();
            if (!r.ok) return r.json().then(function (d) {
                var _up = window.updateLastApiResponseDetail || (window.top && window.top.updateLastApiResponseDetail); if (_up) _up(d);
                if (isProductNotFoundError(d)) {
                    var productSlug = parseProductSlugFromDetail(d);
                    showProductNotFoundModal(productSlug, text, d);
                    return;
                }
                throw new Error(d.error || d.errors && d.errors[0] && d.errors[0].detail || 'Add direction failed');
            });
            return r.json();
        }).then(function (body) {
            if (!body) return;
            applyResponseToUi(body.data, Object.assign({}, body.meta || {}, body.included ? { included: body.included } : {}), text);
            finishCommit();
            refreshIngredientsFromApi();
        }).catch(function (err) {
            console.error('Retry direction error:', err);
            addStepBtn.disabled = false;
            addStepBtn.textContent = 'Add';
        });
    }

    function doCreateProduct() {
        var nameInput = document.getElementById('product-create-name');
        var slugInput = document.getElementById('product-create-slug');
        var descInput = document.getElementById('product-create-description');
        var errorsEl = document.getElementById('product-create-errors');
        var submitBtn = document.getElementById('product-create-submit');
        if (!nameInput || nameInput.value.trim() === '') return;
        var payload = { name: nameInput.value.trim() };
        if (slugInput && slugInput.value.trim()) payload.slug = slugInput.value.trim();
        if (descInput && descInput.value.trim()) payload.description = descInput.value.trim();
        if (errorsEl) { errorsEl.classList.add('hidden'); errorsEl.textContent = ''; }
        if (submitBtn) submitBtn.disabled = true;
        fetch(PRODUCTS_STORE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (r) {
            if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return Promise.reject();
            return r.json().then(function (body) { return { status: r.status, body: body }; });
        }).then(function (res) {
            if (submitBtn) submitBtn.disabled = false;
            if (res.status === 201) {
                retryDirectionAfterProductCreated();
                return;
            }
            if (res.status === 422 && res.body && errorsEl) {
                var msg = res.body.error || (res.body.errors && res.body.errors[0] && (res.body.errors[0].detail || res.body.errors[0].title));
                if (!msg && res.body.errors) msg = res.body.errors.map(function (e) { return e.detail || e.title; }).join(' ');
                errorsEl.textContent = msg || 'Validation failed.';
                errorsEl.classList.remove('hidden');
            }
        }).catch(function (err) {
            if (submitBtn) submitBtn.disabled = false;
            if (errorsEl) {
                errorsEl.textContent = err.message || 'Could not create product.';
                errorsEl.classList.remove('hidden');
            }
        });
    }

    /* ── add step from input: send only text to from-text API, then update UI from response ── */

    function commitStep() {
        var text = stepInput.value.trim();
        if (!text) return;
        if (!recipeSlug) {
            stepInput.setCustomValidity('Create the recipe first (fill title and cuisine).');
            stepInput.reportValidity();
            return;
        }
        stepInput.setCustomValidity('');

        addStepBtn.disabled = true;
        addStepBtn.textContent = '…';

        var url = getDirectionsFromTextUrl();
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ text: text })
        }).then(function (r) {
            if (window.redirectIfUnauthorized && window.redirectIfUnauthorized(r)) return Promise.reject();
            if (!r.ok) return r.json().then(function (d) {
                var _up = window.updateLastApiResponseDetail || (window.top && window.top.updateLastApiResponseDetail); if (_up) _up(d);
                if (isProductNotFoundError(d)) {
                    var productSlug = parseProductSlugFromDetail(d);
                    showProductNotFoundModal(productSlug, text, d);
                    addStepBtn.disabled = false;
                    addStepBtn.textContent = 'Add';
                    return;
                }
                throw new Error(d.error || d.errors && d.errors[0] && d.errors[0].detail || 'Add direction failed');
            });
            return r.json();
        }).then(function (body) {
            if (!body) return;
            applyResponseToUi(body.data, Object.assign({}, body.meta || {}, body.included ? { included: body.included } : {}), text);
            finishCommit();
            refreshIngredientsFromApi();
        }).catch(function (err) {
            console.error('From-text error:', err);
            addStepBtn.disabled = false;
            addStepBtn.textContent = 'Add';
        });
    }

    function finishCommit() {
        stepInput.value = '';
        previewEl.classList.add('hidden');
        lastParsed = null;
        lastParsedText = '';
        addStepBtn.disabled = false;
        addStepBtn.textContent = 'Add';
        stepInput.focus();
    }

    addStepBtn.addEventListener('click', function (e) { e.preventDefault(); commitStep(); });
    stepInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); commitStep(); }
    });

    (function () {
        var cancelBtn = document.getElementById('product-not-found-cancel');
        var form = document.getElementById('product-not-found-form');

        if (cancelBtn) cancelBtn.addEventListener('click', function () {
            hideProductNotFoundModal();
            addStepBtn.disabled = false;
            addStepBtn.textContent = 'Add';
        });
        document.body.addEventListener('click', function (e) {
            var btn = e.target && e.target.closest && e.target.closest('#product-create-submit');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                doCreateProduct();
            }
        });
        if (form) form.addEventListener('submit', function (e) {
            e.preventDefault();
            doCreateProduct();
        });
    })();

    /* ── events: Create button, Add directions, auto-update ── */

    if (createRecipeBtn) createRecipeBtn.addEventListener('click', function (e) { e.preventDefault(); doCreateRecipe(); });

    if (addDirectionsBtn) addDirectionsBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (stepsSection) stepsSection.classList.remove('hidden');
    });

    ['title', 'cuisine', 'region', 'cuisine_request', 'description', 'difficulty', 'serves'].forEach(function (name) {
        var el = recipeForm.querySelector('[name="' + name + '"]');
        if (el) el.addEventListener('input', scheduleBasicSave);
    });

    var addIngredientBtn = document.getElementById('add-ingredient-btn');
    if (addIngredientBtn) addIngredientBtn.addEventListener('click', function () { addIngredient(); });

    if (EDITING && recipeForm) {
        recipeForm.addEventListener('submit', function () {
            var container = document.getElementById('ingredients-hidden-fields');
            if (container) container.innerHTML = '';
            else {
                container = document.createElement('div');
                container.id = 'ingredients-hidden-fields';
                container.setAttribute('aria-hidden', 'true');
                container.className = 'hidden';
                recipeForm.appendChild(container);
            }
            currentIngredients.forEach(function (ing, idx) {
                ['amount', 'measure', 'product', 'notes'].forEach(function (field) {
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'ingredients[' + idx + '][' + field + ']';
                    inp.value = (ing[field] != null) ? String(ing[field]) : '';
                    container.appendChild(inp);
                });
            });
        });
    }

    function syncTimeDisplays() {
        if (prepTimeDisplay) prepTimeDisplay.textContent = parseInt(prepTimeInput && prepTimeInput.value, 10) || 0;
        if (cookTimeDisplay) cookTimeDisplay.textContent = parseInt(cookTimeInput && cookTimeInput.value, 10) || 0;
    }

    /* ── init: load existing directions/ingredients when editing ── */

    var existingDirections  = @json(($preparation ?? [])['directions'] ?? []);
    var existingIngredients = @json(($preparation ?? [])['ingredients'] ?? []);

    syncTimeDisplays();

    if (directionsEl && dirTpl && Array.isArray(existingDirections) && existingDirections.length > 0) {
        existingDirections.forEach(function (d) { addDirection(d); });
    }
    if (EDITING && Array.isArray(existingIngredients)) {
        currentIngredients = existingIngredients.map(function (ing) {
            return {
                amount: (ing.amount != null) ? String(ing.amount) : '',
                measure: (ing.measure != null) ? String(ing.measure) : '',
                product: (ing.product != null) ? String(ing.product) : '',
                notes: (ing.notes != null) ? String(ing.notes) : ''
            };
        });
        renderReadOnlyIngredients();
    } else if (ingredientsEl && ingTpl && Array.isArray(existingIngredients) && existingIngredients.length > 0) {
        existingIngredients.forEach(function (ing) { addIngredient(ing); });
    }

    toggleEmpty();
    if (EDITING && stepsSection && (existingDirections.length > 0 || existingIngredients.length > 0)) {
        stepsSection.classList.remove('hidden');
    }
})();
</script>
@endsection
