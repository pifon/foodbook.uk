<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Foodbook')</title>
    @include('components.build-assets')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <a href="{{ route('home') }}" class="text-3xl font-bold text-primary-600">
                    Foodbook
                </a>
            </div>

            <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
