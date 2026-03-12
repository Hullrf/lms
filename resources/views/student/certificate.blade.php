@extends('layouts.app')

@section('title', 'Certificado')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10 text-center">
    <div class="bg-white border-4 border-indigo-600 rounded-2xl p-10 shadow-lg">
        <p class="text-indigo-400 font-semibold uppercase tracking-widest text-sm mb-2">Certificado de finalización</p>
        <h1 class="text-4xl font-bold text-gray-900 mt-4">{{ $user->name }}</h1>
        <p class="text-gray-500 mt-3 text-lg">Ha completado exitosamente el curso</p>
        <h2 class="text-2xl font-bold text-indigo-600 mt-3">{{ $course->title }}</h2>
        <p class="text-gray-400 mt-6 text-sm">Emitido el {{ $certificate->issued_at->format('d/m/Y') }}</p>
        <p class="text-gray-300 text-xs mt-1">Código de verificación: {{ $certificate->code }}</p>

        <div class="mt-8">
            <a href="{{ route('certificates.download', $course->slug) }}"
               class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-indigo-700">
                ⬇️ Descargar PDF
            </a>
        </div>
    </div>
</div>
@endsection