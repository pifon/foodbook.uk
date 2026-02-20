@extends('layouts.app')
@section('title', ($collection['name'] ?? 'Collection') . ' — Foodbook')

@section('content')
<div>
    <a href="{{ route('collections.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-600 transition hover:text-gray-900">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Back
    </a>

    @if($collection)
        <div class="space-y-6">
            <div>
                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                    {{ $collection['type'] ?? '' }}
                </span>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $collection['name'] }}</h1>
                @if(!empty($collection['description']))
                    <p class="mt-1 text-gray-600">{{ $collection['description'] }}</p>
                @endif
            </div>

            @if(!empty($collection['items']))
                <div class="space-y-3">
                    @foreach($collection['items'] as $item)
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            @if(!empty($item['recipe']))
                                <a
                                    href="{{ route('recipes.show', $item['recipe']['slug'] ?? $item['recipe']['id']) }}"
                                    class="font-medium text-primary-600 hover:text-primary-700"
                                >
                                    {{ $item['recipe']['title'] ?? 'Untitled' }}
                                </a>
                            @endif
                            @if(!empty($item['notes']))
                                <p class="mt-1 text-sm text-gray-500">{{ $item['notes'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">This collection is empty.</p>
            @endif
        </div>
    @else
        <div class="py-12 text-center text-gray-500">Collection not found.</div>
    @endif
</div>
@endsection
