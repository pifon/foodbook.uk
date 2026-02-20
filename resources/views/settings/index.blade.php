@extends('layouts.app')
@section('title', 'Settings — Foodbook')

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
        <p class="mt-1 text-gray-600">Manage your account and preferences.</p>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Profile</h2>
        <dl class="space-y-3 text-sm">
            <div class="flex gap-3">
                <dt class="w-28 shrink-0 font-medium text-gray-500">Username</dt>
                <dd class="text-gray-900">{{ $user['username'] ?? '' }}</dd>
            </div>
            <div class="flex gap-3">
                <dt class="w-28 shrink-0 font-medium text-gray-500">Email</dt>
                <dd class="text-gray-900">{{ $user['email'] ?? '' }}</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Preferences</h2>

        @if($preferences)
            <form class="space-y-4" method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-gray-700">Spice tolerance</label>
                    <select
                        name="spice-tolerance"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
                    >
                        <option value="1" @selected(($preferences['spice-tolerance'] ?? 0) == 1)>Mild</option>
                        <option value="2" @selected(($preferences['spice-tolerance'] ?? 0) == 2)>Medium</option>
                        <option value="3" @selected(($preferences['spice-tolerance'] ?? 0) == 3)>Hot</option>
                        <option value="4" @selected(($preferences['spice-tolerance'] ?? 0) == 4)>Very hot</option>
                        <option value="5" @selected(($preferences['spice-tolerance'] ?? 0) == 5)>Extreme</option>
                    </select>
                </div>

                @if(session('success'))
                    <div class="text-sm text-green-600">{{ session('success') }}</div>
                @endif

                <button
                    type="submit"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700"
                >
                    Save preferences
                </button>
            </form>
        @else
            <p class="text-sm text-gray-500">Could not load preferences.</p>
        @endif
    </section>
</div>
@endsection
