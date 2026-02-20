<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Foodbook')</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <div class="min-h-screen">
        @include('components.header')

        <div class="flex">
            @if(session('api_token'))
                @include('components.sidebar')
            @endif

            <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-5xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
