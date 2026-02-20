@extends('layouts.app')
@section('title', 'Shopping Lists — Foodbook')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Shopping Lists</h1>

    @if(empty($lists))
        <div class="py-12 text-center text-gray-500">No shopping lists yet.</div>
    @else
        <div class="space-y-3">
            @foreach($lists as $list)
                <a
                    href="{{ route('shopping-lists.show', $list['id']) }}"
                    class="group flex items-center justify-between rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-300 hover:shadow-sm"
                >
                    <div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-primary-600">{{ $list['name'] }}</h3>
                        <p class="text-sm text-gray-500">{{ $list['status'] ?? '' }}</p>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
