@extends('layouts.app')
@section('title', ($list['name'] ?? 'Shopping List') . ' — Foodbook')

@section('content')
<div>
    <a href="{{ route('shopping-lists.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-600 transition hover:text-gray-900">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Back
    </a>

    @if($list)
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $list['name'] }}</h1>
                <span class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ match($list['status'] ?? '') { 'active' => 'bg-green-50 text-green-700', 'completed' => 'bg-blue-50 text-blue-700', default => 'bg-gray-100 text-gray-600' } }}">
                    {{ $list['status'] ?? '' }}
                </span>
            </div>

            @if(!empty($items))
                <ul class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
                    @foreach($items as $item)
                        <li class="flex items-center gap-3 px-4 py-3">
                            <form method="POST" action="{{ route('shopping-lists.toggle-item', [$list['id'], $item['id']]) }}">
                                @csrf
                                <input type="hidden" name="checked" value="{{ $item['checked'] ? '1' : '0' }}">
                                <button
                                    type="submit"
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded border transition {{ $item['checked'] ? 'border-primary-600 bg-primary-600' : 'border-gray-300 hover:border-primary-400' }}"
                                >
                                    @if($item['checked'])
                                        <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    @endif
                                </button>
                            </form>
                            <span class="{{ $item['checked'] ? 'text-gray-400 line-through' : 'text-gray-900' }}">
                                @if(!empty($item['quantity'])){{ $item['quantity'] }} @endif
                                {{ $item['product']['name'] ?? 'Item' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">This list is empty.</p>
            @endif
        </div>
    @else
        <div class="py-12 text-center text-gray-500">Shopping list not found.</div>
    @endif
</div>
@endsection
