@extends('layouts.admin')

@section('title', 'Gestión de Cursos')

@section('content')
<div class="flex justify-between items-center mb-6">
    <p class="text-sm text-gray-500">{{ $courses->total() }} cursos en total</p>
    <a href="{{ route('admin.courses.create') }}"
       class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-indigo-700">
        + Nuevo curso
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
            <tr>
                <th class="px-6 py-3 text-left">Curso</th>
                <th class="px-6 py-3 text-left">Categoría</th>
                <th class="px-6 py-3 text-left">Precio</th>
                <th class="px-6 py-3 text-left">Estado</th>
                <th class="px-6 py-3 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($courses as $course)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $course->title }}</div>
                        <div class="text-xs text-gray-400">{{ $course->instructor->name }}</div>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $course->category?->name ?? '—' }}</td>
                    <td class="px-6 py-4 font-medium">
                        {{ $course->isFree() ? 'Gratis' : '$'.number_format($course->price,2) }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $colors = ['draft'=>'bg-gray-100 text-gray-600','published'=>'bg-green-100 text-green-700','archived'=>'bg-red-100 text-red-600'];
                            $labels = ['draft'=>'Borrador','published'=>'Publicado','archived'=>'Archivado'];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $colors[$course->status] }}">
                            {{ $labels[$course->status] }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @php $canEdit = is_null($editableCourseIds) || $editableCourseIds->contains($course->id); @endphp
                        <div class="flex gap-3 items-center">
                            <a href="{{ route('admin.courses.show', $course) }}"
                               class="text-indigo-600 hover:underline text-xs">Ver</a>
                            @if($canEdit)
                                <a href="{{ route('admin.courses.edit', $course) }}"
                                   class="text-yellow-600 hover:underline text-xs">Editar</a>
                                @if(is_null($editableCourseIds) || $course->instructor_id === auth()->id())
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
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $courses->links() }}
    </div>
</div>
@endsection