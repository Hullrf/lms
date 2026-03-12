@extends('layouts.app')

@section('title', 'Catálogo de cursos')

@section('content')

<div class="max-w-7xl mx-auto px-4 py-10">

    {{-- Encabezado --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Todos los cursos</h1>
        <p class="text-gray-500 mt-1">Aprende a tu ritmo con nuestros cursos en línea</p>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('courses.index') }}" class="flex flex-wrap gap-3 mb-8">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Buscar curso..."
               class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">

        <select name="category" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="">Todas las categorías</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>

        <select name="level" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="">Todos los niveles</option>
            <option value="beginner"     {{ request('level') == 'beginner'     ? 'selected' : '' }}>Principiante</option>
            <option value="intermediate" {{ request('level') == 'intermediate' ? 'selected' : '' }}>Intermedio</option>
            <option value="advanced"     {{ request('level') == 'advanced'     ? 'selected' : '' }}>Avanzado</option>
        </select>

        <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-indigo-700">
            Filtrar
        </button>
        <a href="{{ route('courses.index') }}" class="text-sm text-gray-500 px-3 py-2 hover:text-indigo-600">Limpiar</a>
    </form>

    {{-- Grid de cursos --}}
    @if($courses->isEmpty())
        <p class="text-center text-gray-400 py-20">No se encontraron cursos.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($courses as $course)
                <a href="{{ route('courses.show', $course->slug) }}"
                   class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    {{-- Thumbnail --}}
                    <div class="h-40 bg-indigo-100 flex items-center justify-center overflow-hidden">
                        @if($course->thumbnail)
                            <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}"
                                 class="w-full h-full object-cover">
                        @else
                            <span class="text-4xl">📚</span>
                        @endif
                    </div>

                    <div class="p-4">
                        <span class="text-xs text-indigo-600 font-medium uppercase tracking-wide">
                            {{ $course->category?->name ?? 'General' }}
                        </span>
                        <h3 class="font-semibold text-gray-900 mt-1 line-clamp-2">{{ $course->title }}</h3>
                        <p class="text-xs text-gray-500 mt-1">{{ $course->instructor->name }}</p>

                        <div class="flex items-center justify-between mt-3">
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                                {{ ['beginner'=>'Principiante','intermediate'=>'Intermedio','advanced'=>'Avanzado'][$course->level] }}
                            </span>
                            <span class="font-bold text-indigo-600">
                                {{ $course->isFree() ? 'Gratis' : '$'.number_format($course->price, 2) }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $courses->links() }}
        </div>
    @endif
</div>
@endsection