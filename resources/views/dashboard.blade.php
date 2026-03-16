@extends('layouts.app')

@section('title', 'Mi aprendizaje')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Mi aprendizaje</h1>

    @if($enrollments->isEmpty())
    <div class="text-center py-20 bg-white rounded-xl border border-gray-200">
        <p class="text-gray-400 text-lg">Aún no estás matriculado en ningún curso.</p>
        <a href="{{ route('courses.index') }}"
            class="mt-4 inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700">
            Explorar cursos
        </a>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($enrollments as $enrollment)
        @php $course = $enrollment->course; @endphp
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="flex-shrink-0 h-36 bg-indigo-50 flex items-center justify-center overflow-hidden">
                @if($course->thumbnail)
                <img src="{{ Storage::url($course->thumbnail) }}" class="w-full h-36 object-cover">
                @else
                <span class="text-4xl">📚</span>
                @endif
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 line-clamp-2">{{ $course->title }}</h3>

                {{-- Barra de progreso --}}
                <div class="mt-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Progreso</span>
                        <span>{{ $enrollment->progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all"
                            style="width: {{ $enrollment->progress }}%"></div>
                    </div>
                </div>

                <a href="{{ route('lesson.show', [$course->slug, $course->modules->first()?->lessons->first()?->slug ?? '']) }}"
                    class="mt-4 w-full block text-center bg-indigo-600 text-white py-2 rounded-lg text-sm hover:bg-indigo-700">
                    {{ $enrollment->progress > 0 ? 'Continuar' : 'Empezar' }}
                </a>
                @if($enrollment->progress == 100)
                <a href="{{ route('certificates.show', $course->slug) }}"
                    class="mt-2 w-full block text-center bg-green-600 text-white py-2 rounded-lg text-sm hover:bg-green-700">
                    🏆 Ver certificado
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection