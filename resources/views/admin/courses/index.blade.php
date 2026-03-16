@extends('layouts.admin')

@section('title', 'Gestión de Cursos')

@section('content')

{{-- Encabezado --}}
<div class="flex justify-between items-center mb-6">
    <p class="text-sm text-gray-500">
        {{ $grouped->flatten()->count() }} cursos en {{ $grouped->count() }} categorías
    </p>
    <a href="{{ route('admin.courses.create') }}"
       class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-indigo-700">
        + Nuevo curso
    </a>
</div>

{{-- Filtros --}}
<form method="GET" class="flex gap-3 mb-6">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Buscar curso..."
           class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
    <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <option value="">Todos los estados</option>
        <option value="published" @selected(request('status') === 'published')>Publicado</option>
        <option value="draft"     @selected(request('status') === 'draft')>Borrador</option>
        <option value="archived"  @selected(request('status') === 'archived')>Archivado</option>
    </select>
    <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtrar</button>
    @if(request('search') || request('status'))
        <a href="{{ route('admin.courses.index') }}" class="text-sm text-gray-500 px-3 py-2 hover:text-indigo-600">Limpiar</a>
    @endif
</form>

{{-- Grupos por categoría --}}
@forelse($grouped as $categoryName => $courses)
<div x-data="{ open: true }" class="mb-4">

    {{-- Cabecera desplegable --}}
    <button @click="open = !open"
            class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-xl px-5 py-3 shadow-sm hover:bg-gray-50 transition">
        <div class="flex items-center gap-3">
            <span class="text-indigo-600 font-semibold text-sm">🏷️ {{ $categoryName }}</span>
            <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">
                {{ $courses->count() }} {{ $courses->count() === 1 ? 'curso' : 'cursos' }}
            </span>
        </div>
        <span x-text="open ? '▲' : '▼'" class="text-xs text-gray-400"></span>
    </button>

    {{-- Tabla de cursos de esta categoría --}}
    <div x-show="open" x-transition class="border border-t-0 border-gray-200 rounded-b-xl overflow-hidden bg-white">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs">
                <tr>
                    <th class="px-6 py-2 text-left">Curso</th>
                    <th class="px-6 py-2 text-left">Instructor</th>
                    <th class="px-6 py-2 text-left">Precio</th>
                    <th class="px-6 py-2 text-left">Estado</th>
                    <th class="px-6 py-2 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($courses as $course)
                @php
                    $canEdit = is_null($editableCourseIds) || $editableCourseIds->contains($course->id);
                    $canDelete = is_null($editableCourseIds) || $course->instructor_id === auth()->id();
                    $colors = ['draft'=>'bg-gray-100 text-gray-600','published'=>'bg-green-100 text-green-700','archived'=>'bg-red-100 text-red-600'];
                    $labels = ['draft'=>'Borrador','published'=>'Publicado','archived'=>'Archivado'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $course->title }}</td>
                    <td class="px-6 py-3 text-gray-500 text-xs">{{ $course->instructor->name }}</td>
                    <td class="px-6 py-3 font-medium text-gray-700">
                        {{ $course->isFree() ? 'Gratis' : '$'.number_format($course->price, 2) }}
                    </td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$course->status] }}">
                            {{ $labels[$course->status] }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex gap-3 items-center">
                            <a href="{{ route('admin.courses.show', $course) }}"
                               class="text-indigo-600 hover:underline text-xs">Ver</a>
                            @if($canEdit)
                                <a href="{{ route('admin.courses.edit', $course) }}"
                                   class="text-yellow-600 hover:underline text-xs">Editar</a>
                                @if($canDelete)
                                <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                                      onsubmit="return confirm('¿Eliminar este curso?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:underline text-xs">Eliminar</button>
                                </form>
                                @endif
                            @else
                                <span class="text-xs text-gray-400 italic">Solo lectura</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
        No se encontraron cursos.
    </div>
@endforelse

@endsection
