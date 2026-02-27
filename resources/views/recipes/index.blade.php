@extends('layouts.app')
@section('title', 'Recipes — Foodbook')

@section('content')
<div class="space-y-6">
    @if(session('error'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ session('error') }}
        </div>
    @endif
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Recipes</h1>

        <div class="flex flex-wrap items-center gap-2">
            <form class="flex gap-2" method="GET" action="{{ route('recipes.index') }}">
                <input
                    name="q"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Search recipes..."
                    class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                />
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700">
                    Search
                </button>
            </form>

            @if(session('api_token'))
                @if(session('user')['author'] ?? false)
                    <a href="{{ route('recipes.create') }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700">
                        + Create
                    </a>
                    <a href="{{ route('recipes.import') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        Import JSON
                    </a>
                @endif
            @endif
        </div>
    </div>

    @if(empty($recipes))
        <div class="py-12 text-center text-gray-500">
            {{ $search ? 'No recipes found for your search.' : 'No recipes available yet.' }}
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($recipes as $recipe)
                <a
                    href="{{ route('recipes.show', $recipe['slug'] ?? $recipe['id']) }}"
                    class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-300 hover:shadow-sm"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ match($recipe['status'] ?? '') { 'published' => 'bg-green-50 text-green-700', 'draft' => 'bg-yellow-50 text-yellow-700', default => 'bg-gray-100 text-gray-600' } }}">
                            {{ $recipe['status'] ?? 'unknown' }}
                        </span>
                        @if(!empty($recipe['difficulty']))
                            <span class="text-xs text-gray-500">{{ $recipe['difficulty'] }}</span>
                        @endif
                    </div>

                    <h3 class="font-semibold text-gray-900 group-hover:text-primary-600">
                        {{ $recipe['title'] }}
                    </h3>

                    @if(!empty($recipe['description']))
                        <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ $recipe['description'] }}</p>
                    @endif

                    <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                        @if(!empty($recipe['prep-time-minutes']))
                            <span>{{ $recipe['prep-time-minutes'] }} min</span>
                        @endif
                        @if(!empty($recipe['serves']))
                            <span>Serves {{ $recipe['serves'] }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        @if($pagination && ($pagination['last-page'] ?? 1) > 1)
            <nav class="flex items-center justify-center gap-2 pt-4">
                @for($p = 1; $p <= $pagination['last-page']; $p++)
                    <a
                        href="{{ route('recipes.index', array_filter(['q' => $search, 'page' => $p > 1 ? $p : null])) }}"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $p === $page ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                    >
                        {{ $p }}
                    </a>
                @endfor
            </nav>
        @endif
    @endif
</div>
@endsection
