@extends('layouts.admin')

@section('title', 'Editar Curso')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('admin.courses.index') }}" class="text-sm text-indigo-600 hover:underline">← Volver</a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-4">
        <form method="POST" action="{{ route('admin.courses.update', $course) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="title" value="{{ old('title', $course->title) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">{{ old('description', $course->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="category_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Sin categoría</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $course->category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nivel</label>
                    <select name="level" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="beginner"     {{ $course->level == 'beginner'     ? 'selected' : '' }}>Principiante</option>
                        <option value="intermediate" {{ $course->level == 'intermediate' ? 'selected' : '' }}>Intermedio</option>
                        <option value="advanced"     {{ $course->level == 'advanced'     ? 'selected' : '' }}>Avanzado</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio ($)</label>
                    <input type="number" name="price" value="{{ old('price', $course->price) }}" min="0" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="draft"      {{ $course->status == 'draft'      ? 'selected' : '' }}>Borrador</option>
                        <option value="published"  {{ $course->status == 'published'  ? 'selected' : '' }}>Publicado</option>
                        <option value="archived"   {{ $course->status == 'archived'   ? 'selected' : '' }}>Archivado</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_free" id="is_free" value="1" {{ $course->is_free ? 'checked' : '' }}
                       class="rounded text-indigo-600">
                <label for="is_free" class="text-sm text-gray-700">Curso gratuito</label>
            </div>

            @if($course->thumbnail)
                <div>
                    <p class="text-xs text-gray-500 mb-1">Portada actual:</p>
                    <img src="{{ Storage::url($course->thumbnail) }}" class="h-24 rounded-lg object-cover">
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva imagen de portada</label>
                <input type="file" name="thumbnail" accept="image/*"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-indigo-700">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.courses.index') }}" class="px-6 py-2 rounded-lg text-sm border border-gray-300 hover:bg-gray-50">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection