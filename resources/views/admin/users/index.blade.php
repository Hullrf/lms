@extends('layouts.admin')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="flex justify-between items-center mb-6">
    <p class="text-sm text-gray-500">{{ $users->total() }} usuarios registrados</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
            <tr>
                <th class="px-6 py-3 text-left">Nombre</th>
                <th class="px-6 py-3 text-left">Email</th>
                <th class="px-6 py-3 text-left">Rol</th>
                <th class="px-6 py-3 text-left">Registrado</th>
                <th class="px-6 py-3 text-left">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            @php
                $colors = ['admin'=>'bg-red-100 text-red-700','instructor'=>'bg-blue-100 text-blue-700','student'=>'bg-green-100 text-green-700'];
            @endphp
            <tbody x-data="{ open: false }" class="border-b border-gray-100">
                <tr class="hover:bg-gray-50 cursor-pointer" @click="open = !open">
                    <td class="px-6 py-4 font-medium text-gray-900 flex items-center gap-2">
                        <span x-text="open ? '▾' : '▸'" class="text-gray-400 text-xs"></span>
                        {{ $user->name }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $colors[$user->role] }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="px-6 py-4" @click.stop>
                        @if($user->id !== auth()->id())
                            <div class="flex items-center gap-3">
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <select name="role" class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-400">
                                        <option value="student"    @selected($user->role === 'student')>Estudiante</option>
                                        <option value="instructor" @selected($user->role === 'instructor')>Instructor</option>
                                        <option value="admin"      @selected($user->role === 'admin')>Admin</option>
                                    </select>
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">Guardar</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                      onsubmit="return confirm('¿Eliminar usuario?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">Tu cuenta</span>
                        @endif
                    </td>
                </tr>

                {{-- Fila expandible: cursos inscritos --}}
                <tr x-show="open" x-transition style="display:none">
                    <td colspan="5" class="px-10 py-3 bg-indigo-50">
                        @if($user->role === 'student' && $user->enrollments->isNotEmpty())
                            <p class="text-xs font-semibold text-indigo-700 mb-2">
                                Cursos inscritos ({{ $user->enrollments_count }})
                            </p>
                            <div class="space-y-2">
                                @foreach($user->enrollments as $enrollment)
                                <div class="flex items-center gap-4">
                                    <span class="text-sm text-gray-700 w-56 truncate">{{ $enrollment->course->title }}</span>
                                    <div class="flex items-center gap-2 flex-1">
                                        <div class="w-32 bg-gray-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $enrollment->progress === 100 ? 'bg-green-500' : 'bg-indigo-500' }}"
                                                 style="width: {{ $enrollment->progress }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $enrollment->progress }}%</span>
                                    </div>
                                    @if($enrollment->progress === 100)
                                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Completado</span>
                                    @elseif($enrollment->progress > 0)
                                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">En progreso</span>
                                    @else
                                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Sin iniciar</span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        @elseif($user->role === 'student')
                            <p class="text-xs text-gray-400 italic">Este estudiante no está inscrito en ningún curso.</p>
                        @else
                            <p class="text-xs text-gray-400 italic">Los usuarios con rol {{ $user->role }} no tienen inscripciones.</p>
                        @endif
                    </td>
                </tr>
            </tbody>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $users->links() }}
    </div>
</div>
@endsection