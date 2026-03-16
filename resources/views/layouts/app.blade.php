<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Aprende en línea')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <div x-data="{ open: false }">

    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="{{ route('home') }}" class="text-xl font-bold text-indigo-600">
                    {{ config('app.name') }}
                </a>
                <div class="flex items-center gap-3">
                    <a href="{{ route('courses.index') }}" class="text-sm text-gray-600 hover:text-indigo-600">Cursos</a>

                    {{-- Login / Logout en el navbar --}}
                    @auth
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button class="text-sm text-gray-500 hover:text-red-500 transition">Cerrar sesión</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-indigo-600">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="text-sm bg-indigo-600 text-white px-4 py-1.5 rounded-lg hover:bg-indigo-700 transition">Registrarse</a>
                    @endauth

                    {{-- Botón hamburguesa (solo autenticados) --}}
                    @auth
                    <button @click="open = true"
                            class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 transition"
                            aria-label="Menú">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Overlay --}}
    <div x-show="open"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-black/40 z-40"
         style="display:none"></div>

    {{-- Drawer lateral --}}
    <div x-show="open"
         x-transition:enter="transition-transform duration-250 ease-out"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-72 bg-white shadow-2xl z-50 flex flex-col"
         style="display:none">

        {{-- Cabecera del drawer --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <span class="font-semibold text-gray-800">Menú</span>
            <button @click="open = false"
                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none"
                    aria-label="Cerrar">&times;</button>
        </div>

        {{-- Enlaces --}}
        <nav class="flex-1 px-6 py-6 space-y-1 overflow-y-auto">
            {{-- Info del usuario --}}
            <div class="mb-4 pb-4 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-400">{{ auth()->user()->email }}</p>
            </div>

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                📚 Mi aprendizaje
            </a>

            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition">
                👤 Mi perfil
            </a>

            @if(auth()->user()->isAdmin() || auth()->user()->isInstructor())
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-indigo-600 font-medium hover:bg-indigo-50 transition">
                ⚙️ {{ auth()->user()->isAdmin() ? 'Panel de administración' : 'Mi panel de instructor' }}
            </a>
            @endif
        </nav>
    </div>

    </div>{{-- fin x-data --}}

    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 text-sm text-center">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 text-sm text-center">
            {{ session('error') }}
        </div>
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 py-6 text-center text-sm text-gray-400">
        © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
    </footer>

</body>
</html>