@extends('layouts.app')

@section('title', $lesson->title)

@section('content')

<div class="flex h-[calc(100vh-64px)]">

    {{-- Sidebar del curso --}}
    <aside class="w-80 bg-white border-r border-gray-200 overflow-y-auto flex-shrink-0">
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
                    <span>{{ $enrollment->progress }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $enrollment->progress }}%"></div>
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
            $isActive = $item->id === $lesson->id;
            $isCompleted = $item->isCompletedBy(auth()->user());
            @endphp
            <a href="{{ route('lesson.show', [$course->slug, $item->slug]) }}"
                class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-4 transition
                              {{ $isActive ? 'border-indigo-600 bg-indigo-50 text-indigo-700 font-medium' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
                <span class="flex-shrink-0">
                    @if($isCompleted)
                    <span class="text-green-500">✓</span>
                    @elseif($isActive)
                    <span class="text-indigo-600">▶</span>
                    @else
                    <span class="text-gray-400">○</span>
                    @endif
                </span>
                <span class="line-clamp-2">{{ $item->title }}</span>
            </a>
            @endforeach
        </div>
        @endforeach
    </aside>

    {{-- Área de contenido --}}
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <div class="max-w-3xl mx-auto px-6 py-8">
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

            {{-- Botón marcar como completada --}}
            <button id="btn-complete"
                onclick="markComplete({{ $lesson->id }})"
                class="{{ ($progress && $progress->completed) ? 'bg-green-600 cursor-default' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white px-6 py-3 rounded-lg font-medium transition">
                {{ ($progress && $progress->completed) ? '✓ Lección completada' : 'Marcar como completada' }}
            </button>
        </div>
    </main>
</div>

<script>
    async function markComplete(lessonId) {
        const btn = document.getElementById('btn-complete');
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        try {
            const res = await fetch(`/progress/${lessonId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    completed: true
                })
            });
            const data = await res.json();
            btn.textContent = '✓ Lección completada';
            btn.classList.replace('bg-indigo-600', 'bg-green-600');
            btn.classList.remove('hover:bg-indigo-700');
        } catch (e) {
            btn.disabled = false;
            btn.textContent = 'Marcar como completada';
        }
    }
</script>
@endsection