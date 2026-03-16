@extends('layouts.admin')

@section('title', $course->title)

@section('content')
<div class="flex justify-between items-start mb-6">
    <div>
        <a href="{{ route('admin.courses.index') }}" class="text-sm text-indigo-600 hover:underline">← Cursos</a>
        <h2 class="text-xl font-bold text-gray-900 mt-1">{{ $course->title }}</h2>
    </div>
    <a href="{{ route('admin.courses.edit', $course) }}"
       class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600">
        Editar curso
    </a>
</div>

{{-- Módulos --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-gray-800">Módulos y Lecciones</h3>
    </div>

    {{-- Formulario nuevo módulo --}}
    <form method="POST" action="{{ route('admin.courses.modules.store', $course) }}"
          class="flex gap-3 mb-6 p-4 bg-gray-50 rounded-lg">
        @csrf
        <input type="text" name="title" placeholder="Nombre del módulo" required
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
            + Añadir módulo
        </button>
    </form>

    {{-- Lista de módulos --}}
    <div class="space-y-4">
        @forelse($course->modules as $module)
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                    <span class="font-medium text-gray-800">{{ $module->title }}</span>
                    <form method="POST" action="{{ route('admin.modules.destroy', $module) }}"
                          onsubmit="return confirm('¿Eliminar módulo y todas sus lecciones?')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-500 hover:underline">Eliminar</button>
                    </form>
                </div>

                {{-- Lecciones --}}
                <ul class="divide-y divide-gray-100">
                    @foreach($module->lessons as $lesson)
                        <li class="px-4 py-2 flex justify-between items-center text-sm">
                            <div class="flex items-center gap-2">
                                <span>{{ $lesson->type === 'video' ? '▶️' : ($lesson->type === 'quiz' ? '📝' : '📄') }}</span>
                                <span class="text-gray-700">{{ $lesson->title }}</span>
                                @if($lesson->is_preview)
                                    <span class="text-xs text-green-600 font-medium">Preview</span>
                                @endif
                                @if($lesson->type === 'quiz')
                                    <a href="{{ route('admin.quiz.edit', $lesson) }}" class="text-xs text-indigo-500 hover:underline ml-2">Editar quiz</a>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}"
                                  onsubmit="return confirm('¿Eliminar lección?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:underline">Eliminar</button>
                            </form>
                        </li>
                    @endforeach
                </ul>

                {{-- Formulario nueva lección --}}
                <form method="POST" action="{{ route('admin.modules.lessons.store', $module) }}"
                      class="p-3 bg-gray-50 border-t border-gray-100 flex gap-2">
                    @csrf
                    <input type="text" name="title" placeholder="Nueva lección..." required
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <select name="type" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="video">Video</option>
                        <option value="text">Texto</option>
                        <option value="file">Archivo</option>
                        <option value="quiz">Quiz</option>
                    </select>
                    <input type="url" name="video_url" placeholder="URL del video (opcional)"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-indigo-700">
                        Añadir
                    </button>
                </form>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-4">Aún no hay módulos. Agrega uno arriba.</p>
        @endforelse
    </div>
</div>
@endsection