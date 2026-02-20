@extends('layouts.app')
@section('title', 'Pantry — Foodbook')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Pantry</h1>

    @if(empty($items))
        <div class="py-12 text-center text-gray-500">
            Your pantry is empty. Add items to track what you have at home.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 font-medium text-gray-700">Product</th>
                        <th class="px-4 py-3 font-medium text-gray-700">Quantity</th>
                        <th class="px-4 py-3 font-medium text-gray-700">Expiry</th>
                        <th class="px-4 py-3 font-medium text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $item['product']['name'] ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $item['quantity'] ?? '—' }}
                                {{ $item['measure']['abbreviation'] ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $item['expiry-date'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if(!empty($item['is-expired']))
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Expired</span>
                                @elseif(!empty($item['is-past-best-before']))
                                    <span class="rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-medium text-yellow-700">Past best before</span>
                                @else
                                    <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Fresh</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
