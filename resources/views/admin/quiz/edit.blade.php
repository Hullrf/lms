@extends('layouts.admin')

@section('title', 'Quiz: ' . $lesson->title)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.courses.show', $lesson->module->course) }}"
       class="text-sm text-indigo-600 hover:underline">← {{ $lesson->module->course->title }}</a>
    <h2 class="text-xl font-bold text-gray-900 mt-1">Quiz: {{ $lesson->title }}</h2>
    <p class="text-sm text-gray-500 mt-1">Mínimo 70% de respuestas correctas para aprobar.</p>
</div>

{{-- Agregar pregunta --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="font-semibold text-gray-800 mb-4">Nueva pregunta</h3>
    <form method="POST" action="{{ route('admin.quiz.questions.store', $lesson) }}" class="flex gap-3">
        @csrf
        <input type="text" name="question" placeholder="Escribe la pregunta..." required
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
            + Añadir
        </button>
    </form>
</div>

{{-- Lista de preguntas --}}
<div class="space-y-4">
    @forelse($lesson->questions as $i => $question)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 flex justify-between items-center">
            <p class="font-medium text-gray-800">{{ $i + 1 }}. {{ $question->question }}</p>
            <form method="POST" action="{{ route('admin.quiz.questions.destroy', $question) }}"
                  onsubmit="return confirm('¿Eliminar pregunta y sus opciones?')">
                @csrf @method('DELETE')
                <button class="text-xs text-red-500 hover:underline">Eliminar</button>
            </form>
        </div>

        {{-- Opciones --}}
        <div class="px-6 py-3 space-y-2">
            @foreach($question->options as $option)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0">
                <div class="flex items-center gap-2">
                    @if($option->is_correct)
                        <span class="text-green-500 font-bold text-sm">✓</span>
                    @else
                        <span class="text-gray-300 text-sm">○</span>
                    @endif
                    <span class="text-sm text-gray-700">{{ $option->text }}</span>
                    @if($option->is_correct)
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Correcta</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.quiz.options.destroy', $option) }}">
                    @csrf @method('DELETE')
                    <button class="text-xs text-red-400 hover:underline">×</button>
                </form>
            </div>
            @endforeach

            {{-- Agregar opción --}}
            <form method="POST" action="{{ route('admin.quiz.options.store', $question) }}"
                  class="flex gap-2 pt-2">
                @csrf
                <input type="text" name="text" placeholder="Nueva opción..." required
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <label class="flex items-center gap-1 text-sm text-gray-600 whitespace-nowrap">
                    <input type="checkbox" name="is_correct" value="1" class="rounded text-green-500"> Es correcta
                </label>
                <button type="submit" class="bg-gray-700 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-gray-800">
                    Añadir
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-sm text-gray-400">
        No hay preguntas todavía. Añade la primera arriba.
    </div>
    @endforelse
</div>
@endsection
