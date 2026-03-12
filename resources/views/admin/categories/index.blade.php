@extends('layouts.admin')

@section('title', 'Gestión de Categorías')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    {{-- Formulario crear categoría --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="font-semibold text-gray-800 mb-5">Nueva categoría</h2>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="description" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">{{ old('description') }}</textarea>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm hover:bg-indigo-700">
                    + Crear categoría
                </button>
            </form>
        </div>
    </div>

    {{-- Lista de categorías --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-800">Categorías ({{ $categories->total() }})</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Nombre</th>
                        <th class="px-6 py-3 text-left">Slug</th>
                        <th class="px-6 py-3 text-left">Cursos</th>
                        <th class="px-6 py-3 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50" x-data="{ editing: false }">
                            <td class="px-6 py-3">
                                <span x-show="!editing" class="font-medium text-gray-900">{{ $category->name }}</span>
                                <form x-show="editing" method="POST"
                                      action="{{ route('admin.categories.update', $category) }}">
                                    @csrf @method('PUT')
                                    <div class="flex gap-2">
                                        <input type="text" name="name" value="{{ $category->name }}" required
                                               class="border border-gray-300 rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                        <button type="submit" class="text-xs bg-indigo-600 text-white px-3 py-1 rounded-lg">
                                            Guardar
                                        </button>
                                        <button type="button" x-on:click="editing = false"
                                                class="text-xs text-gray-500 px-2">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $category->slug }}</td>
                            <td class="px-6 py-3">
                                <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">
                                    {{ $category->courses_count }}
                                </span>
                            </td>
                            <td class="px-6 py-3 flex gap-3 items-center">
                                <button x-on:click="editing = !editing"
                                        class="text-xs text-yellow-600 hover:underline">
                                    Editar
                                </button>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                      onsubmit="return confirm('¿Eliminar categoría?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                No hay categorías aún.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $categories->links() }}
            </div>
        </div>
    </div>

</div>
@endsection