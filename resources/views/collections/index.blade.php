@extends('layouts.app')
@section('title', 'Collections — Foodbook')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Collections</h1>
    </div>

    @if(empty($collections))
        <div class="py-12 text-center text-gray-500">
            No collections yet. Create one to organise your recipes.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($collections as $collection)
                <a
                    href="{{ route('collections.show', $collection['id']) }}"
                    class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-300 hover:shadow-sm"
                >
                    <div class="mb-1 flex items-center gap-2">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                            {{ $collection['type'] ?? '' }}
                        </span>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-primary-600">{{ $collection['name'] }}</h3>
                    @if(!empty($collection['description']))
                        <p class="mt-1 text-sm text-gray-600">{{ $collection['description'] }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
