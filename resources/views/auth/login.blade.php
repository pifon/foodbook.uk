@extends('layouts.auth')
@section('title', 'Log in — Foodbook')

@section('content')
<div>
    <h2 class="text-xl font-semibold text-gray-900">Log in to your account</h2>
    <p class="mt-1 text-sm text-gray-600">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-700">Sign up</a>
    </p>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('login') }}">
        @csrf

        @error('login')
            <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div>
            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
            <input
                id="username"
                name="username"
                type="text"
                required
                autocomplete="username"
                value="{{ old('username') }}"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
            />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
            />
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50"
        >
            Log in
        </button>
    </form>
</div>
@endsection
