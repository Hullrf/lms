<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - @yield('title', 'Panel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="w-64 bg-gray-900 text-white flex flex-col min-h-screen fixed">
        <div class="p-6 border-b border-gray-700">
            <a href="{{ route('home') }}" class="text-lg font-bold text-indigo-400">
                {{ config('app.name') }}
            </a>
            <p class="text-xs text-gray-400 mt-1">Panel de administración</p>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                📊 Dashboard
            </a>
            <a href="{{ route('admin.courses.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.courses*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                📚 Cursos
            </a>
            <a href="{{ route('admin.users.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.users*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                👥 Usuarios
            </a>
            <a href="{{ route('admin.categories.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.categories*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                🏷️ Categorías
            </a>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <p class="text-xs text-gray-400">{{ auth()->user()->name }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-xs text-red-400 hover:text-red-300 mt-1">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    {{-- Contenido principal --}}
    <div class="ml-64 flex-1 flex flex-col">
        <header class="bg-white shadow-sm px-8 py-4">
            <h1 class="text-lg font-semibold text-gray-800">@yield('title', 'Dashboard')</h1>
        </header>

        @if(session('success'))
        <div class="mx-8 mt-4 bg-green-100 border border-green-300 text-green-800 px-4 py-3 text-sm rounded-lg">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mx-8 mt-4 bg-red-100 border border-red-300 text-red-800 px-4 py-3 text-sm rounded-lg">
            {{ session('error') }}
        </div>
        @endif

        <main class="p-8 flex-1">
            @yield('content')
        </main>
    </div>

@stack('scripts')
</body>

</html>