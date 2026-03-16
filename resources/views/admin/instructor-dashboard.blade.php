@extends('layouts.admin')

@section('title', 'Mi Dashboard')

@section('content')

{{-- Tarjetas resumen --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Mis cursos</p>
        <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $stats['my_courses'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Estudiantes inscritos</p>
        <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $stats['total_students'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Ingresos totales</p>
        <p class="text-3xl font-bold text-green-600 mt-1">${{ number_format($stats['total_revenue'], 2) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Progreso promedio</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['avg_progress'] }}%</p>
    </div>
</div>

{{-- Gráficos --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Matrículas — últimos 6 meses</h2>
        <canvas id="chartEnrollments" height="120"></canvas>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Top 5 mis cursos más populares</h2>
        @if($topCourses->isEmpty())
            <p class="text-sm text-gray-400 text-center py-10">Aún no tienes cursos con matrículas.</p>
        @else
            <canvas id="chartTopCourses" height="120"></canvas>
        @endif
    </div>
</div>

{{-- Matrículas recientes --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="font-semibold text-gray-800">Matrículas recientes en mis cursos</h2>
    </div>
    @if($recentEnrollments->isEmpty())
        <p class="text-sm text-gray-400 text-center py-8">Ningún estudiante inscrito todavía.</p>
    @else
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
                                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $e->progress }}%"></div>
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
    @endif
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartEnrollments'), {
    type: 'line',
    data: {
        labels: @json($monthLabels),
        datasets: [{
            label: 'Matrículas',
            data: @json($enrollmentSeries),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.1)',
            borderWidth: 2,
            pointRadius: 4,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});

@if($topCourses->isNotEmpty())
new Chart(document.getElementById('chartTopCourses'), {
    type: 'bar',
    data: {
        labels: @json($topCourses->pluck('title')->map(fn($t) => strlen($t) > 22 ? substr($t, 0, 22).'…' : $t)),
        datasets: [{
            label: 'Matrículas',
            data: @json($topCourses->pluck('enrollments_count')),
            backgroundColor: ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b'],
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
@endif
</script>
@endpush
