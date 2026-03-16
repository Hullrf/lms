@extends('layouts.app')

@section('title', $lesson->title)

@section('content')

<div class="flex h-[calc(100vh-64px)] relative" x-data="{ sidebarOpen: true }">

    {{-- Sidebar del curso --}}
    <aside class="bg-white border-r border-gray-200 flex-shrink-0 overflow-hidden transition-all duration-300"
           :class="sidebarOpen ? 'w-80' : 'w-0 border-r-0'">
        <div class="w-80 h-full overflow-y-auto">
        <div class="p-4 border-b border-gray-200">
            <a href="{{ route('courses.show', $course->slug) }}"
                class="text-sm text-indigo-600 hover:underline">← {{ $course->title }}</a>

            {{-- Barra de progreso global --}}
            @php
            $enrollment = auth()->user()->enrollments()->where('course_id', $course->id)->first();
            @endphp
            @if($enrollment)
            <div class="mt-2">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Progreso del curso</span>
                    <span id="progress-label">{{ $enrollment->progress }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div id="progress-bar" class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $enrollment->progress }}%"></div>
                </div>
            </div>
            @endif
        </div>

        {{-- Lista de módulos y lecciones --}}
        @foreach($course->modules as $module)
        <div class="border-b border-gray-100">
            <div class="px-4 py-3 bg-gray-50 text-sm font-semibold text-gray-700">
                {{ $module->title }}
            </div>
            @foreach($module->lessons as $item)
            @php
            $itemIndex = $allLessons->search(fn($l) => $l->id === $item->id);
            $isActive = $item->id === $lesson->id;
            $isCompleted = $item->isCompletedBy(auth()->user());
            $isLocked = !$item->is_preview && $itemIndex > 0 && !$allLessons[$itemIndex - 1]->isCompletedBy(auth()->user()) && !$isCompleted;
            @endphp
            @if($isLocked)
            <div class="flex items-center gap-2 px-4 py-2.5 text-sm border-l-4 border-transparent text-gray-400 cursor-not-allowed select-none">
                <span class="flex-shrink-0 text-xs">🔒</span>
                <span class="line-clamp-2">{{ $item->title }}</span>
            </div>
            @else
            <div class="flex items-center gap-2 px-3 py-2.5 border-l-4 transition
                        {{ $isActive ? 'border-indigo-600 bg-indigo-50' : 'border-transparent hover:bg-gray-50' }}">
                <span class="flex-shrink-0 text-xs">
                    {{ $item->type === 'video' ? '▶️' : ($item->type === 'quiz' ? '📝' : '📄') }}
                </span>
                <a href="{{ route('lesson.show', [$course->slug, $item->slug]) }}"
                   class="flex-1 text-sm line-clamp-2 {{ $isActive ? 'text-indigo-700 font-medium' : 'text-gray-700' }}">
                    {{ $item->title }}
                </a>
                <input type="checkbox"
                       data-lesson="{{ $item->id }}"
                       {{ $isCompleted ? 'checked' : '' }}
                       {{ $isCompleted ? 'disabled' : '' }}
                       class="lesson-check flex-shrink-0 w-4 h-4 rounded cursor-pointer accent-indigo-600 disabled:opacity-60"
                       title="{{ $isCompleted ? 'Completada' : 'Marcar como completada' }}">
            </div>
            @endif
            @endforeach
        </div>
        @endforeach
        </div>{{-- fin overflow-y-auto --}}
    </aside>

    {{-- Botón toggle pegado al borde entre sidebar y contenido --}}
    <button @click="sidebarOpen = !sidebarOpen"
            class="absolute top-4 z-20 flex items-center justify-center w-6 h-10 bg-white border border-gray-300 rounded-r-lg shadow-sm hover:bg-indigo-50 hover:border-indigo-300 transition-all duration-300"
            :style="sidebarOpen ? 'left: 320px' : 'left: 0px'"
            :title="sidebarOpen ? 'Ocultar panel' : 'Mostrar panel'">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 transition-transform duration-300"
             :class="sidebarOpen ? '' : 'rotate-180'"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>

    {{-- Área de contenido --}}
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <div class="max-w-3xl mx-auto px-6 py-8">

            @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
            @endif

            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $lesson->title }}</h1>

            {{-- Video --}}
            @if($lesson->video_url)
            <div class="aspect-video bg-black rounded-xl overflow-hidden mb-6 shadow">
                @if(str_contains($lesson->video_url, 'youtube'))
                @php
                preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $lesson->video_url, $m);
                $videoId = $m[1] ?? '';
                @endphp
                <iframe class="w-full h-full"
                    src="https://www.youtube.com/embed/{{ $videoId }}"
                    frameborder="0" allowfullscreen></iframe>
                @else
                <video controls class="w-full h-full" src="{{ $lesson->video_url }}"></video>
                @endif
            </div>
            @endif

            {{-- Contenido de texto --}}
            @if($lesson->content)
            <div class="prose max-w-none text-gray-700 mb-6">
                {!! $lesson->content !!}
            </div>
            @endif

            {{-- Recursos descargables --}}
            @if($lesson->resources->count())
            <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">📎 Recursos</h3>
                <ul class="space-y-2">
                    @foreach($lesson->resources as $resource)
                    <li>
                        <a href="{{ Storage::url($resource->file_path) }}"
                            download
                            class="flex items-center gap-2 text-sm text-indigo-600 hover:underline">
                            ⬇️ {{ $resource->name }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Quiz --}}
            @if($lesson->type === 'quiz')
                @if($quizQuestions->isEmpty())
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700">
                        Este quiz aún no tiene preguntas configuradas.
                    </div>
                @else
                    @if($quizResults)
                        {{-- Resultados del quiz --}}
                        <div class="mb-6 p-5 rounded-xl border {{ $quizResults['passed'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                            <p class="font-semibold text-lg {{ $quizResults['passed'] ? 'text-green-700' : 'text-red-700' }}">
                                {{ $quizResults['passed'] ? '¡Aprobaste! 🎉' : 'No aprobaste esta vez' }}
                                — {{ $quizResults['score'] }}% ({{ $quizResults['correct'] }}/{{ $quizResults['total'] }} correctas)
                            </p>
                            @if(!$quizResults['passed'])
                                <p class="text-sm text-red-600 mt-1">Necesitas al menos 70% para pasar. Intenta de nuevo.</p>
                            @endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('quiz.submit', $lesson) }}" class="space-y-6">
                        @csrf
                        @foreach($quizQuestions as $i => $question)
                        <div class="bg-white border border-gray-200 rounded-xl p-5">
                            <p class="font-medium text-gray-800 mb-3">{{ $i + 1 }}. {{ $question->question }}</p>
                            <div class="space-y-2">
                                @foreach($question->options as $option)
                                @php
                                    $resultClass = '';
                                    if ($quizResults) {
                                        $qResult = $quizResults['results'][$question->id] ?? null;
                                        if ($qResult) {
                                            if ($option->is_correct) $resultClass = 'border-green-400 bg-green-50';
                                            elseif ((int)($qResult['selected'] ?? 0) === $option->id) $resultClass = 'border-red-400 bg-red-50';
                                        }
                                    }
                                @endphp
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition {{ $resultClass }}">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                           {{ $quizResults && (int)($quizResults['results'][$question->id]['selected'] ?? 0) === $option->id ? 'checked' : '' }}
                                           class="text-indigo-600">
                                    <span class="text-sm text-gray-700">{{ $option->text }}</span>
                                    @if($quizResults && $option->is_correct)
                                        <span class="ml-auto text-xs text-green-600 font-medium">✓ Correcta</span>
                                    @endif
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition">
                            {{ $quizResults ? 'Intentar de nuevo' : 'Enviar respuestas' }}
                        </button>
                    </form>
                @endif

            {{-- Lección normal: indicador de estado --}}
            @else
                @if($progress && $progress->completed)
                    <p id="lesson-status" class="text-sm text-green-600 font-medium flex items-center gap-2">
                        <span>✓</span> Lección marcada como completada
                    </p>
                @else
                    <p id="lesson-status" class="text-xs text-gray-400">Marca la lección como completada usando el checkbox en el panel lateral.</p>
                @endif
            @endif
        </div>
    </main>
</div>

<script>
document.querySelectorAll('.lesson-check').forEach(checkbox => {
    checkbox.addEventListener('change', async function () {
        if (!this.checked) return;

        this.disabled = true;
        const lessonId = this.dataset.lesson;

        try {
            const res = await fetch(`/progress/${lessonId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ completed: true })
            });

            const data = await res.json();

            // Actualizar barra de progreso del sidebar
            const bar = document.getElementById('progress-bar');
            const label = document.getElementById('progress-label');
            if (bar) bar.style.width = data.progress + '%';
            if (label) label.textContent = data.progress + '%';

            // Si la lección actual fue marcada, actualizar el indicador de estado
            if (lessonId == '{{ $lesson->id }}') {
                const status = document.getElementById('lesson-status');
                if (status) {
                    status.innerHTML = '<span>✓</span> Lección marcada como completada';
                    status.className = 'text-sm text-green-600 font-medium flex items-center gap-2';
                }
            }
        } catch (e) {
            this.checked = false;
            this.disabled = false;
        }
    });
});
</script>
@endsection
