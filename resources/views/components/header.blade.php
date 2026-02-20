<header class="sticky top-0 z-40 border-b border-gray-200 bg-white">
    <div class="flex h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
        @if(session('api_token'))
            <button
                class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:hidden"
                onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full'); document.getElementById('sidebar-overlay').classList.toggle('hidden')"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        @endif

        <a href="{{ route('home') }}" class="text-xl font-bold text-primary-600">
            Foodbook
        </a>

        <nav class="ml-8 hidden items-center gap-6 sm:flex">
            <a
                href="{{ route('recipes.index') }}"
                class="text-sm font-medium transition hover:text-gray-900 {{ request()->routeIs('recipes.*') ? 'text-primary-600' : 'text-gray-600' }}"
            >
                Recipes
            </a>
        </nav>

        <div class="ml-auto flex items-center gap-3">
            @if(session('api_token'))
                <span class="hidden text-sm text-gray-600 sm:inline">
                    {{ session('user.username', '') }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
                        Log out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100">
                    Log in
                </a>
                <a href="{{ route('register') }}" class="rounded-lg bg-primary-600 px-4 py-1.5 text-sm font-medium text-white transition hover:bg-primary-700">
                    Sign up
                </a>
            @endif
        </div>
    </div>
</header>
