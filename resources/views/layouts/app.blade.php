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

    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="{{ route('home') }}" class="text-xl font-bold text-indigo-600">
                    {{ config('app.name') }}
                </a>
                <div class="flex items-center gap-4">
                    <a href="{{ route('courses.index') }}" class="text-sm text-gray-600 hover:text-indigo-600">Cursos</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-indigo-600">Mi aprendizaje</a>
                        @if(auth()->user()->isAdmin() || auth()->user()->isInstructor())
                            <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 font-medium">
                                {{ auth()->user()->isAdmin() ? 'Admin' : 'Mi panel' }}
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button class="text-sm text-gray-500 hover:text-red-500">Salir</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-indigo-600">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Registrarse</a>
                        <a href="{{ route('profile.edit') }}" class="text-sm text-gray-600 hover:text-indigo-600">Mi perfil</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

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