@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

{{-- Tarjetas de estadísticas --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total cursos</p>
        <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $stats['total_courses'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Estudiantes</p>
        <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $stats['total_students'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Matrículas</p>
        <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $stats['total_enrollments'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Ingresos totales</p>
        <p class="text-3xl font-bold text-green-600 mt-1">${{ number_format($stats['total_revenue'], 2) }}</p>
    </div>
</div>

{{-- Matrículas recientes --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="font-semibold text-gray-800">Matrículas recientes</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Estudiante</th>
                    <th class="px-6 py-3 text-left">Curso</th>
                    <th class="px-6 py-3 text-left">Progreso</th>
                    <th class="px-6 py-3 text-left">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($recentEnrollments as $e)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $e->user->name }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $e->course->title }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-24 bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width:{{ $e->progress }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $e->progress }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-gray-500">{{ $e->enrolled_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection