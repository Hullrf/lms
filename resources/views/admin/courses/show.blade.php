@extends('layouts.admin')

@section('title', $course->title)

@section('content')
@php $canEdit = $course->isEditableBy(auth()->user()); @endphp

<div class="flex justify-between items-start mb-6">
    <div>
        <a href="{{ route('admin.courses.index') }}" class="text-sm text-indigo-600 hover:underline">← Cursos</a>
        <h2 class="text-xl font-bold text-gray-900 mt-1">{{ $course->title }}</h2>
        @if(!$canEdit)
            <span class="text-xs text-gray-400 italic mt-1 block">Solo lectura</span>
        @endif
    </div>
    @if($canEdit)
    <a href="{{ route('admin.courses.edit', $course) }}"
       class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600">
        Editar curso
    </a>
    @endif
</div>

{{-- Módulos --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-gray-800">Módulos y Lecciones</h3>
    </div>

    {{-- Formulario nuevo módulo (solo editores) --}}
    @if($canEdit)
    <form id="form-new-module"
          data-url="{{ route('admin.courses.modules.store', $course) }}"
          class="flex gap-3 mb-6 p-4 bg-gray-50 rounded-lg">
        @csrf
        <input type="text" name="title" placeholder="Nombre del módulo" required
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
            + Añadir módulo
        </button>
    </form>
    @endif

    {{-- Lista de módulos --}}
    <div class="space-y-4" id="modules-list">
        @forelse($course->modules as $module)
            <div class="border border-gray-200 rounded-lg overflow-hidden" data-module-block="{{ $module->id }}">
                <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                    <span class="font-medium text-gray-800">{{ $module->title }}</span>
                    @if($canEdit)
                    <form method="POST" action="{{ route('admin.modules.destroy', $module) }}"
                          onsubmit="return confirm('¿Eliminar módulo y todas sus lecciones?')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-500 hover:underline">Eliminar</button>
                    </form>
                    @endif
                </div>

                {{-- Lecciones --}}
                <ul class="divide-y divide-gray-100" id="lessons-{{ $module->id }}">
                    @foreach($module->lessons as $lesson)
                        <li class="text-sm border-b border-gray-50 last:border-0">
                            <div class="px-4 py-2 flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span>{{ $lesson->type === 'video' ? '▶️' : ($lesson->type === 'quiz' ? '📝' : '📄') }}</span>
                                    <span class="text-gray-700">{{ $lesson->title }}</span>
                                    @if($lesson->is_preview)
                                        <span class="text-xs text-green-600 font-medium">Preview</span>
                                    @endif
                                    @if($lesson->type === 'quiz' && $canEdit)
                                        <a href="{{ route('admin.quiz.edit', $lesson) }}" class="text-xs text-indigo-500 hover:underline ml-2">Editar quiz</a>
                                    @endif
                                </div>
                                @if($canEdit)
                                <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}"
                                      onsubmit="return confirm('¿Eliminar lección?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 hover:underline">Eliminar</button>
                                </form>
                                @endif
                            </div>

                            {{-- Recursos de esta lección --}}
                            @if($lesson->resources->isNotEmpty() || $canEdit)
                            <div class="px-4 pb-3 space-y-1" x-data="{ addingResource: false }">
                                @foreach($lesson->resources as $res)
                                <div class="flex items-center justify-between bg-gray-50 rounded px-3 py-1.5">
                                    <div class="flex items-center gap-2 text-xs text-gray-600 min-w-0">
                                        <span>{{ $res->type === 'file' ? '📎' : '🔗' }}</span>
                                        <span class="truncate">{{ $res->name }}</span>
                                        <span class="text-gray-400 shrink-0">({{ $res->sourceLabel() }})</span>
                                    </div>
                                    @if($canEdit)
                                    <form method="POST" action="{{ route('admin.resources.destroy', $res) }}" class="ml-2 shrink-0">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-400 hover:text-red-600">×</button>
                                    </form>
                                    @endif
                                </div>
                                @endforeach

                                @if($canEdit)
                                <div>
                                    <button @click="addingResource = !addingResource"
                                            class="text-xs text-indigo-500 hover:underline mt-1">
                                        + Añadir enlace
                                    </button>
                                    <form x-show="addingResource" method="POST"
                                          action="{{ route('admin.lessons.resources.store', $lesson) }}"
                                          class="mt-2 flex gap-2" style="display:none">
                                        @csrf
                                        <input type="text" name="name" placeholder="Nombre del recurso" required
                                               class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                        <input type="url" name="url" placeholder="https://..." required
                                               class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                        <button type="submit"
                                                class="bg-indigo-600 text-white px-3 py-1 rounded text-xs hover:bg-indigo-700">
                                            Añadir
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                {{-- Formulario nueva lección (solo editores) --}}
                @if($canEdit)
                <form class="lesson-create-form p-3 bg-gray-50 border-t border-gray-100 flex gap-2"
                      data-url="{{ route('admin.modules.lessons.store', $module) }}"
                      data-module="{{ $module->id }}">
                    @csrf
                    <input type="text" name="title" placeholder="Nueva lección..." required
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <select name="type" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="video">Video</option>
                        <option value="text">Texto</option>
                        <option value="file">Archivo</option>
                        <option value="quiz">Quiz</option>
                    </select>
                    <input type="url" name="video_url" placeholder="URL del video (opcional)"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-indigo-700">
                        Añadir
                    </button>
                </form>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-4">
                @if($canEdit) Aún no hay módulos. Agrega uno arriba. @else Este curso no tiene módulos aún. @endif
            </p>
        @endforelse
    </div>
</div>

{{-- ── Colaboradores ────────────────────────────────────────── --}}
@php $isOwner = auth()->id() === $course->instructor_id || auth()->user()->isAdmin(); @endphp
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="font-semibold text-gray-800 mb-4">Colaboradores</h3>

    @if($course->collaborators->isEmpty())
        <p class="text-sm text-gray-400 mb-4">Este curso no tiene colaboradores.</p>
    @else
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($course->collaborators as $collab)
            <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-200 rounded-full px-3 py-1">
                <span class="text-sm text-indigo-700">{{ $collab->name }}</span>
                @if($isOwner)
                <form method="POST" action="{{ route('admin.courses.collaborators.destroy', [$course, $collab]) }}">
                    @csrf @method('DELETE')
                    <button class="text-indigo-400 hover:text-red-500 text-xs font-bold leading-none">×</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    @endif

    @if($isOwner)
    <form method="POST" action="{{ route('admin.courses.collaborators.store', $course) }}" class="flex gap-3">
        @csrf
        <input type="email" name="email" placeholder="Email del instructor colaborador..." required
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700">
            Añadir colaborador
        </button>
    </form>
    @endif
</div>

{{-- ── Panel de estadísticas ─────────────────────────────────── --}}
<div class="mt-8">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Estadísticas del curso</h3>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500">Estudiantes inscritos</p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $totalEnrolled }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500">Han completado</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ $totalCompleted }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500">Progreso promedio</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $avgProgress }}%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs text-gray-500">Tasa de finalización</p>
            <p class="text-3xl font-bold text-purple-600 mt-1">{{ $completionRate }}%</p>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Matrículas por mes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Matrículas — últimos 6 meses</h4>
            <canvas id="chartCourseEnrollments" height="140"></canvas>
        </div>

        {{-- Distribución de progreso --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Distribución de progreso</h4>
            @if($totalEnrolled > 0)
                <canvas id="chartProgressDist" height="140"></canvas>
            @else
                <p class="text-sm text-gray-400 text-center py-10">Sin estudiantes inscritos aún.</p>
            @endif
        </div>
    </div>

    {{-- Completación por lección --}}
    @if($lessonStats->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm mb-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Completación por lección</h4>
        <div class="space-y-3">
            @foreach($lessonStats as $stat)
            <div>
                <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span class="flex items-center gap-1">
                        {{ $stat['type'] === 'video' ? '▶️' : ($stat['type'] === 'quiz' ? '📝' : '📄') }}
                        {{ $stat['title'] }}
                    </span>
                    <span class="font-medium">{{ $stat['rate'] }}% ({{ $stat['completed'] }}/{{ $totalEnrolled }})</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full {{ $stat['rate'] >= 70 ? 'bg-green-500' : ($stat['rate'] >= 40 ? 'bg-yellow-400' : 'bg-red-400') }}"
                         style="width: {{ $stat['rate'] }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tabla de estudiantes --}}
    @if($studentStats->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700">Estudiantes inscritos</h4>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Estudiante</th>
                    <th class="px-6 py-3 text-left">Progreso</th>
                    <th class="px-6 py-3 text-left">Inscrito el</th>
                    <th class="px-6 py-3 text-left">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($studentStats as $enrollment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-800">{{ $enrollment->user->name }}</td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-28 bg-gray-200 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full {{ $enrollment->progress === 100 ? 'bg-green-500' : 'bg-indigo-500' }}"
                                     style="width: {{ $enrollment->progress }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500">{{ $enrollment->progress }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $enrollment->enrolled_at->format('d/m/Y') }}</td>
                    <td class="px-6 py-3">
                        @if($enrollment->progress === 100)
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Completado</span>
                        @elseif($enrollment->progress > 0)
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">En progreso</span>
                        @else
                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-medium">Sin iniciar</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const lessonIcons = { video: '▶️', quiz: '📝', text: '📄', file: '📄' };

// ── Crear módulo sin recarga ───────────────────────────────────
const formModule = document.getElementById('form-new-module');
if (formModule) {
    formModule.addEventListener('submit', async function (e) {
        e.preventDefault();
        const titleInput = this.querySelector('[name="title"]');
        const title = titleInput.value.trim();
        if (!title) return;

        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;

        const res = await fetch(this.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ title })
        });

        if (res.ok) {
            const mod = await res.json();
            appendModule(mod);
            titleInput.value = '';

            // Quitar mensaje "sin módulos" si existía
            const empty = document.querySelector('#modules-list p');
            if (empty) empty.remove();
        }

        btn.disabled = false;
    });
}

function appendModule(mod) {
    const list = document.getElementById('modules-list');
    const div = document.createElement('div');
    div.className = 'border border-gray-200 rounded-lg overflow-hidden';
    div.dataset.moduleBlock = mod.id;
    div.innerHTML = `
        <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
            <span class="font-medium text-gray-800">${mod.title}</span>
            <form method="POST" action="/admin/modules/${mod.id}" onsubmit="return confirm('¿Eliminar módulo y todas sus lecciones?')">
                <input type="hidden" name="_token" value="${CSRF}">
                <input type="hidden" name="_method" value="DELETE">
                <button class="text-xs text-red-500 hover:underline">Eliminar</button>
            </form>
        </div>
        <ul class="divide-y divide-gray-100" id="lessons-${mod.id}"></ul>
        <form class="lesson-create-form p-3 bg-gray-50 border-t border-gray-100 flex gap-2"
              data-url="/admin/modules/${mod.id}/lessons" data-module="${mod.id}">
            <input type="hidden" name="_token" value="${CSRF}">
            <input type="text" name="title" placeholder="Nueva lección..." required
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <select name="type" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                <option value="video">Video</option>
                <option value="text">Texto</option>
                <option value="file">Archivo</option>
                <option value="quiz">Quiz</option>
            </select>
            <input type="url" name="video_url" placeholder="URL del video (opcional)"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-indigo-700">
                Añadir
            </button>
        </form>
    `;
    list.appendChild(div);
    attachLessonForm(div.querySelector('.lesson-create-form'));
}

// ── Crear lección sin recarga ─────────────────────────────────
function attachLessonForm(form) {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const titleInput = this.querySelector('[name="title"]');
        const typeSelect = this.querySelector('[name="type"]');
        const videoInput = this.querySelector('[name="video_url"]');
        const moduleId   = this.dataset.module;

        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;

        const body = { title: titleInput.value.trim(), type: typeSelect.value };
        if (videoInput && videoInput.value) body.video_url = videoInput.value;

        const res = await fetch(this.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        });

        if (res.ok) {
            const lesson = await res.json();
            appendLesson(lesson, moduleId);
            titleInput.value = '';
            if (videoInput) videoInput.value = '';
        }

        btn.disabled = false;
    });
}

function appendLesson(lesson, moduleId) {
    const ul = document.getElementById(`lessons-${moduleId}`);
    if (!ul) return;
    const icon = lessonIcons[lesson.type] || '📄';
    const li = document.createElement('li');
    li.className = 'text-sm border-b border-gray-50 last:border-0';
    li.innerHTML = `
        <div class="px-4 py-2 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span>${icon}</span>
                <span class="text-gray-700">${lesson.title}</span>
                ${lesson.type === 'quiz' ? `<a href="/admin/lessons/${lesson.id}/quiz" class="text-xs text-indigo-500 hover:underline ml-2">Editar quiz</a>` : ''}
            </div>
            <form method="POST" action="/admin/lessons/${lesson.id}" onsubmit="return confirm('¿Eliminar lección?')">
                <input type="hidden" name="_token" value="${CSRF}">
                <input type="hidden" name="_method" value="DELETE">
                <button class="text-xs text-red-500 hover:underline">Eliminar</button>
            </form>
        </div>
    `;
    ul.appendChild(li);
}

// Adjuntar listener a formularios de lección existentes
document.querySelectorAll('.lesson-create-form').forEach(attachLessonForm);
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartCourseEnrollments'), {
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

@if($totalEnrolled > 0)
new Chart(document.getElementById('chartProgressDist'), {
    type: 'doughnut',
    data: {
        labels: @json(array_keys($progressGroups)),
        datasets: [{
            data: @json(array_values($progressGroups)),
            backgroundColor: ['#e5e7eb','#93c5fd','#fbbf24','#818cf8','#34d399'],
            borderWidth: 2,
        }]
    },
    options: {
        cutout: '60%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
        }
    }
});
@endif
</script>
@endpush