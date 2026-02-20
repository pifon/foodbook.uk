@extends('layouts.app')
@section('title', ($editing ? 'Edit' : 'Create') . ' Recipe — Foodbook')

@section('content')
@php
    $inputCls = 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none';
    $slug = $recipe['slug'] ?? $recipe['id'] ?? '';
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

        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Basic Information</h2>
            <div class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required
                        value="{{ old('title', $recipe['title'] ?? '') }}"
                        class="{{ $inputCls }} mt-1" />
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="{{ $inputCls }} mt-1">{{ old('description', $recipe['description'] ?? '') }}</textarea>
                </div>

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
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="{{ $inputCls }} mt-1">
                            <option value="draft" @selected(old('status', $recipe['status'] ?? 'draft') === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $recipe['status'] ?? '') === 'published')>Published</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Directions</h2>
                    <p class="text-sm text-gray-500">Total time: <span id="total-time" class="font-medium text-gray-700">0</span> min</p>
                </div>
            </div>

            <div id="directions-header" class="mb-1 hidden grid grid-cols-[2rem_1fr_1fr_4.5rem_2rem] gap-2 px-3 text-xs font-medium text-gray-400">
                <span></span>
                <span>Operation</span>
                <span>Product</span>
                <span>Min</span>
                <span></span>
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
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span id="preview-op" class="hidden rounded-full bg-blue-100 px-2.5 py-1 font-medium text-blue-700"></span>
                        <span id="preview-product" class="hidden rounded-full bg-green-100 px-2.5 py-1 font-medium text-green-700"></span>
                        <span id="preview-duration" class="hidden rounded-full bg-amber-100 px-2.5 py-1 font-medium text-amber-700"></span>
                        <span id="preview-temp" class="hidden rounded-full bg-red-100 px-2.5 py-1 font-medium text-red-700"></span>
                        <span id="preview-tool" class="hidden rounded-full bg-purple-100 px-2.5 py-1 font-medium text-purple-700"></span>
                        <span id="preview-condition" class="hidden rounded-full bg-teal-100 px-2.5 py-1 font-medium text-teal-700"></span>
                        <span id="preview-location" class="hidden rounded-full bg-indigo-100 px-2.5 py-1 font-medium text-indigo-700"></span>
                    </div>
                    <details id="step-json-details" class="mt-2 hidden">
                        <summary class="cursor-pointer text-xs text-gray-400 hover:text-gray-600">Show JSON</summary>
                        <pre id="json-output" class="mt-1 max-h-48 overflow-auto rounded bg-gray-900 p-2.5 text-xs leading-relaxed text-green-400"></pre>
                    </details>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Ingredients</h2>
                    <p class="text-sm text-gray-500">Auto-detected from steps. Adjust amounts and measures as needed.</p>
                </div>
                <button type="button" id="add-ingredient-btn"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    + Add
                </button>
            </div>
            <div id="ingredients-list" class="space-y-2"></div>
            <p id="ingredients-empty" class="py-4 text-center text-sm text-gray-400">No ingredients yet. They will appear as you add steps.</p>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit"
                class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                {{ $editing ? 'Update Recipe' : 'Create Recipe' }}
            </button>
            <a href="{{ $editing ? route('recipes.show', $slug) : route('recipes.index') }}"
                class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>

<template id="direction-tpl">
    <div class="direction-row rounded-lg border border-gray-200 bg-white p-3">
        <div class="grid grid-cols-[2rem_1fr_1fr_4.5rem_2rem] items-center gap-2">
            <span class="direction-num flex h-6 w-6 items-center justify-center rounded-full bg-primary-50 text-xs font-semibold text-primary-700">1</span>
            <input type="text" data-field="operation" placeholder="Operation"
                class="{{ $inputCls }}" />
            <input type="text" data-field="product" placeholder="Product"
                class="{{ $inputCls }}" />
            <input type="number" data-field="duration" placeholder="Min" min="0"
                class="duration-input {{ $inputCls }}" />
            <button type="button" class="remove-btn flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="mt-2 pl-8">
            <input type="text" data-field="notes" placeholder="Full description"
                class="{{ $inputCls }} text-xs text-gray-500" />
            <input type="hidden" data-field="parsed_json" value="" />
        </div>
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

<script>
(function () {
    var directionsEl = document.getElementById('directions-list');
    var ingredientsEl = document.getElementById('ingredients-list');
    var totalTimeEl = document.getElementById('total-time');
    var dirTpl = document.getElementById('direction-tpl');
    var ingTpl = document.getElementById('ingredient-tpl');
    var dirEmpty = document.getElementById('directions-empty');
    var ingEmpty = document.getElementById('ingredients-empty');
    var dirHeader = document.getElementById('directions-header');

    var stepInput = document.getElementById('step-input');
    var addStepBtn = document.getElementById('add-step-btn');
    var previewEl = document.getElementById('step-preview');
    var prevOp = document.getElementById('preview-op');
    var prevProduct = document.getElementById('preview-product');
    var prevDuration = document.getElementById('preview-duration');
    var prevTemp = document.getElementById('preview-temp');
    var prevTool = document.getElementById('preview-tool');
    var prevCondition = document.getElementById('preview-condition');
    var prevLocation = document.getElementById('preview-location');
    var jsonDetails = document.getElementById('step-json-details');
    var jsonOutput = document.getElementById('json-output');

    var dirIdx = 0;
    var ingIdx = 0;
    var lastParsed = null;
    var lastParsedText = '';

    var PARSE_URL = '{{ route("recipes.parse-direction") }}';
    var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    /* ── server-side parser ── */

    function serverParse(sentence, onSuccess, onError) {
        fetch(PARSE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({ sentence: sentence })
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(onSuccess)
        .catch(function (e) {
            console.error('Parse error:', e);
            if (onError) onError(e);
        });
    }

    /* ── live preview ── */

    function showPreview(steps) {
        if (!steps || !steps.length) { previewEl.classList.add('hidden'); return; }

        var step = steps[0];
        var any = false;

        function badge(el, text) {
            if (text) { el.textContent = text; el.classList.remove('hidden'); any = true; }
            else { el.classList.add('hidden'); }
        }

        badge(prevOp, step.type ? step.type.charAt(0).toUpperCase() + step.type.slice(1) : '');

        var targetNames = (step.targets || []).map(function (t) {
            var label = t.name;
            if (t.quantity) label = t.quantity.amount + (t.quantity.unit ? ' ' + t.quantity.unit : '') + ' ' + label;
            if (t.state) label += ' (' + t.state + ')';
            return label;
        }).join(', ');
        badge(prevProduct, targetNames);

        badge(prevDuration, step.duration ? step.duration.value + ' ' + step.duration.unit : '');

        var heatText = '';
        if (step.heat) {
            if (step.heat.temperature) heatText = step.heat.temperature.value + '°' + step.heat.temperature.unit;
            if (step.heat.level) heatText += (heatText ? ' / ' : '') + step.heat.level + ' heat';
        }
        badge(prevTemp, heatText);

        badge(prevTool, step.tool ? (step.tool.size ? step.tool.size + ' ' : '') + step.tool.name : '');
        badge(prevCondition, step.condition ? step.condition.type + ' ' + step.condition.value : '');
        badge(prevLocation, step.location || '');

        if (any) {
            jsonDetails.classList.remove('hidden');
            jsonOutput.textContent = JSON.stringify(steps, null, 2);
        } else {
            jsonDetails.classList.add('hidden');
        }

        previewEl.classList.toggle('hidden', !any);

        if (steps.length > 1) {
            prevOp.textContent += ' (' + steps.length + ' steps)';
        }
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
        dirHeader.classList.toggle('hidden', !hasDirs);
        dirHeader.classList.toggle('grid', hasDirs);
        ingEmpty.classList.toggle('hidden', hasIngs);
    }

    function renumberDirections() {
        directionsEl.querySelectorAll('.direction-num').forEach(function (el, i) { el.textContent = i + 1; });
    }

    function updateTotalTime() {
        var total = 0;
        directionsEl.querySelectorAll('.duration-input').forEach(function (el) { total += parseInt(el.value) || 0; });
        totalTimeEl.textContent = total;
    }

    function existingProducts() {
        var set = {};
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
            row.remove(); renumberDirections(); updateTotalTime(); toggleEmpty();
        });

        directionsEl.appendChild(clone);
        renumberDirections(); updateTotalTime(); toggleEmpty();
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
        });

        ingredientsEl.appendChild(clone);
        toggleEmpty();
    }

    function ensureIngredient(name, quantity) {
        if (!name || name.length < 2) return;
        var have = existingProducts();
        if (!have[name.toLowerCase().trim()]) {
            addIngredient({
                product: name,
                amount: quantity ? String(quantity.amount) : '',
                measure: quantity ? (quantity.unit || '') : '',
            });
        }
    }

    /* ── map parsed steps into form rows ── */

    function applyParsedSteps(steps, originalText) {
        (steps || []).forEach(function (step) {
            var mins = durationToMinutes(step.duration);
            var firstTarget = (step.targets && step.targets[0]) ? step.targets[0].name : '';

            addDirection({
                operation: step.type ? step.type.charAt(0).toUpperCase() + step.type.slice(1) : '',
                product: firstTarget,
                duration: mins || '',
                notes: originalText,
                parsed_json: JSON.stringify(step),
            });

            (step.targets || []).forEach(function (t) {
                ensureIngredient(t.name, t.quantity);
            });
        });
    }

    /* ── add step from input ── */

    function commitStep() {
        var text = stepInput.value.trim();
        if (!text) return;

        addStepBtn.disabled = true;
        addStepBtn.textContent = '…';

        if (lastParsed && lastParsedText === text) {
            applyParsedSteps(lastParsed, text);
            finishCommit();
        } else {
            serverParse(text, function (steps) {
                applyParsedSteps(steps, text);
                finishCommit();
            }, function () {
                addDirection({ notes: text });
                finishCommit();
            });
        }
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

    /* ── events ── */

    directionsEl.addEventListener('input', function (e) {
        if (e.target.classList.contains('duration-input')) updateTotalTime();
    });

    document.getElementById('add-ingredient-btn').addEventListener('click', function () { addIngredient(); });

    /* ── init ── */

    var existingDirections  = @json($preparation['directions'] ?? []);
    var existingIngredients = @json($preparation['ingredients'] ?? []);

    if (existingDirections.length) existingDirections.forEach(addDirection);
    if (existingIngredients.length) existingIngredients.forEach(addIngredient);

    toggleEmpty();
})();
</script>
@endsection
