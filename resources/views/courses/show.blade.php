@extends('layouts.app')

@section('title', $course->title)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Columna principal --}}
        <div class="lg:col-span-2">
            <span class="text-sm text-indigo-600 font-medium">{{ $course->category?->name }}</span>
            <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ $course->title }}</h1>
            <p class="text-gray-600 mt-3">{{ $course->description }}</p>

            <div class="flex items-center gap-4 mt-4 text-sm text-gray-500">
                <span>👤 {{ $course->instructor->name }}</span>
                <span>📊 {{ ['beginner'=>'Principiante','intermediate'=>'Intermedio','advanced'=>'Avanzado'][$course->level] }}</span>
                <span>📖 {{ $course->lessons->count() }} lecciones</span>
            </div>

            {{-- Contenido del curso --}}
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Contenido del curso</h2>
                <div class="space-y-3">
                    @foreach($course->modules as $module)
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="bg-gray-50 px-4 py-3 font-medium text-gray-800 flex justify-between">
                                <span>{{ $module->title }}</span>
                                <span class="text-sm text-gray-500">{{ $module->lessons->count() }} lecciones</span>
                            </div>
                            <ul class="divide-y divide-gray-100">
                                @foreach($module->lessons as $lesson)
                                    <li class="px-4 py-2 flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $lesson->type === 'video' ? '▶️' : '📄' }}</span>
                                            <span class="{{ $lesson->is_preview ? 'text-indigo-600' : 'text-gray-700' }}">
                                                {{ $lesson->title }}
                                            </span>
                                            @if($lesson->is_preview)
                                                <span class="text-xs text-green-600 font-medium">Vista previa</span>
                                            @endif
                                        </div>
                                        @if($lesson->video_duration)
                                            <span class="text-gray-400">{{ gmdate('i:s', $lesson->video_duration) }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar de inscripción --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sticky top-6">
                @if($course->thumbnail)
                    <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}"
                         class="w-full h-40 object-cover rounded-lg mb-4">
                @endif

                <div class="text-3xl font-bold text-indigo-600 mb-4">
                    {{ $course->isFree() ? 'Gratis' : '$'.number_format($course->price, 2) }}
                </div>

                @auth
                    @if($isEnrolled)
                        <a href="{{ route('lesson.show', [$course->slug, $course->modules->first()?->lessons->first()?->slug ?? '']) }}"
                           class="w-full block text-center bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700">
                            Continuar aprendiendo →
                        </a>
                    @else
                        <form method="POST" action="{{ route('enroll', $course->slug) }}">
                            @csrf
                            <button type="submit"
                                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                                {{ $course->isFree() ? 'Matricularse gratis' : 'Comprar curso' }}
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       class="w-full block text-center bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700">
                        Iniciar sesión para matricularse
                    </a>
                @endauth
            </div>
        </div>

    </div>
</div>
@endsection