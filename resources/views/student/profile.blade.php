@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">Mi perfil</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Formulario --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-5">Información personal</h2>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PATCH')

                {{-- Avatar --}}
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center overflow-hidden">
                        @if($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-2xl">👤</span>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Foto de perfil</label>
                        <input type="file" name="avatar" accept="image/*"
                               class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('email') border-red-400 @enderror">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biografía</label>
                    <textarea name="bio" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">{{ old('bio', $user->bio) }}</textarea>
                </div>

                <hr class="border-gray-200">
                <p class="text-sm font-medium text-gray-700">Cambiar contraseña <span class="text-gray-400 font-normal">(dejar en blanco para no cambiar)</span></p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                    <input type="password" name="password"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-indigo-700">
                    Guardar cambios
                </button>
            </form>
        </div>

        {{-- Sidebar: cursos matriculados --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="font-semibold text-gray-800 mb-4">Mis cursos</h2>
                @forelse($enrollments as $enrollment)
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-800 line-clamp-1">{{ $enrollment->course->title }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                <div class="bg-indigo-600 h-1.5 rounded-full" style="width:{{ $enrollment->progress }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500">{{ $enrollment->progress }}%</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Aún no estás matriculado en ningún curso.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection