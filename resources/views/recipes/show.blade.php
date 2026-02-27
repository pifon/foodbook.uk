@extends('layouts.app')
@section('title', ($recipe['title'] ?? 'Recipe') . ' — Foodbook')

@section('content')
<div>
    <a href="{{ route('recipes.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-600 transition hover:text-gray-900">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Back
    </a>

    @if($error)
        <div class="py-12 text-center text-red-600">{{ $error }}</div>
    @elseif($recipe)
        <div class="space-y-8">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ match($recipe['status'] ?? '') { 'published' => 'bg-green-50 text-green-700', 'draft' => 'bg-yellow-50 text-yellow-700', default => 'bg-gray-100 text-gray-600' } }}">
                        {{ $recipe['status'] ?? 'unknown' }}
                    </span>
                    @if(!empty($recipe['difficulty']))
                        <span class="text-sm text-gray-500">{{ $recipe['difficulty'] }}</span>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $recipe['title'] }}</h1>
                    @if(session('api_token'))
                        <a href="{{ route('recipes.edit', $recipe['slug'] ?? $recipe['id']) }}"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                            Edit
                        </a>
                    @endif
                </div>

                @if(!empty($recipe['description']))
                    <p class="mt-2 text-gray-600">{{ $recipe['description'] }}</p>
                @endif

                <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-500">
                    @if(!empty($recipe['prep-time-minutes']))
                        <span class="flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $recipe['prep-time-minutes'] }} min
                        </span>
                    @endif
                    @if(!empty($recipe['serves']))
                        <span class="flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            Serves {{ $recipe['serves'] }}
                        </span>
                    @endif
                </div>
            </div>

            @if(!empty($preparation['ingredients']))
                <section class="rounded-xl border border-gray-200 bg-white p-6">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Ingredients</h2>
                    <ul class="space-y-2">
                        @foreach($preparation['ingredients'] as $ingredient)
                            <li class="flex items-baseline gap-2 text-sm">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary-400"></span>
                                <span>
                                    @if(!empty($ingredient['amount']))
                                        <span class="font-medium">{{ $ingredient['amount'] }}</span>
                                    @endif
                                    @if(!empty($ingredient['measure']))
                                        <span class="text-gray-500">{{ $ingredient['measure']['abbreviation'] ?? $ingredient['measure'] }}</span>
                                    @endif
                                    <span class="text-gray-900">{{ $ingredient['product']['name'] ?? $ingredient['product'] ?? '' }}</span>
                                    @if(!empty($ingredient['notes']))
                                        <span class="text-gray-500"> — {{ $ingredient['notes'] }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(!empty($preparation['directions']))
                <section class="rounded-xl border border-gray-200 bg-white p-6">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Directions</h2>
                    <ol class="space-y-4">
                        @foreach($preparation['directions'] as $idx => $direction)
                            <li class="flex gap-4 text-sm">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-xs font-semibold text-primary-700">
                                    {{ $idx + 1 }}
                                </span>
                                <div>
                                    {{-- Prefer instruction (full step text including all ingredients); notes is set from instruction in normalizePreparationForView --}}
                                    @if(!empty($direction['notes']))
                                        <p class="text-gray-900">{{ $direction['notes'] }}</p>
                                    @else
                                        <p class="text-gray-900">
                                            @if(!empty($direction['operation']))
                                                <span class="font-medium">{{ $direction['operation']['name'] ?? $direction['operation'] }}</span>
                                            @endif
                                            @if(!empty($direction['product']))
                                                {{ $direction['product']['name'] ?? $direction['product'] }}
                                            @endif
                                        </p>
                                    @endif
                                    @if(!empty($direction['duration']))
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $direction['duration'] }} min</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>
    @endif
</div>
@endsection
