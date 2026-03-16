<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $this->authorize('viewAny', Course::class);

        $query = Course::with('instructor', 'category');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $courses = $query->orderBy('title')->get();

        // Agrupar por categoría; sin categoría va al final
        $grouped = $courses->groupBy(fn($c) => $c->category?->name ?? '__sin_categoria__')
            ->sortKeys();

        // Separar "Sin categoría" y ponerla al final
        $sinCategoria = $grouped->pull('__sin_categoria__');
        if ($sinCategoria) {
            $grouped->put('Sin categoría', $sinCategoria);
        }

        // IDs editables para el instructor
        $editableCourseIds = auth()->user()->isAdmin()
            ? null
            : Course::where('instructor_id', auth()->id())->pluck('id')
                ->merge(auth()->user()->collaboratingCourses()->pluck('id'));

        return view('admin.courses.index', compact('grouped', 'editableCourseIds'));
    }

    public function create()
    {
        $this->authorize('create', Course::class);
        $categories = Category::all();
        return view('admin.courses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'price'       => 'required|numeric|min:0',
            'is_free'     => 'boolean',
            'level'       => 'required|in:beginner,intermediate,advanced',
            'status'      => 'required|in:draft,published,archived',
            'thumbnail'   => 'nullable|image|max:2048',
        ]);

        $data['slug']          = Str::slug($data['title']);
        $data['instructor_id'] = auth()->id();

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')
                                         ->store('thumbnails', 'public');
        }

        if ($data['status'] === 'published') {
            $data['published_at'] = now();
        }

        Course::create($data);

        return redirect()->route('admin.courses.index')
                         ->with('success', 'Curso creado exitosamente.');
    }

    public function edit(Course $course)
    {
        $this->authorize('update', $course);
        $categories = Category::all();
        return view('admin.courses.edit', compact('course', 'categories'));
    }

    public function update(Request $request, Course $course)
    {
        $this->authorize('update', $course);
        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'price'       => 'required|numeric|min:0',
            'is_free'     => 'boolean',
            'level'       => 'required|in:beginner,intermediate,advanced',
            'status'      => 'required|in:draft,published,archived',
            'thumbnail'   => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')
                                         ->store('thumbnails', 'public');
        }

        if ($data['status'] === 'published' && !$course->published_at) {
            $data['published_at'] = now();
        }

        $course->update($data);

        return redirect()->route('admin.courses.index')
                         ->with('success', 'Curso actualizado.');
    }

    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);
        $course->delete();
        return redirect()->route('admin.courses.index')
                         ->with('success', 'Curso eliminado.');
    }

    public function show(Course $course)
    {
        $this->authorize('view', $course);
        $course->load('modules.lessons', 'collaborators');

        $enrollments   = $course->enrollments()->with('user')->get();
        $totalEnrolled = $enrollments->count();
        $totalCompleted = $enrollments->where('progress', 100)->count();
        $avgProgress   = $totalEnrolled > 0 ? (int) round($enrollments->avg('progress')) : 0;
        $completionRate = $totalEnrolled > 0 ? (int) round($totalCompleted / $totalEnrolled * 100) : 0;

        // Distribución de progreso
        $progressGroups = [
            'Sin iniciar'   => $enrollments->where('progress', 0)->count(),
            'Iniciado'      => $enrollments->filter(fn($e) => $e->progress >= 1  && $e->progress <= 25)->count(),
            'En progreso'   => $enrollments->filter(fn($e) => $e->progress >= 26 && $e->progress <= 75)->count(),
            'Avanzado'      => $enrollments->filter(fn($e) => $e->progress >= 76 && $e->progress <= 99)->count(),
            'Completado'    => $enrollments->where('progress', 100)->count(),
        ];

        // Matrículas por mes (últimos 6 meses)
        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));
        $enrollmentsRaw = $course->enrollments()
            ->selectRaw('DATE_FORMAT(enrolled_at, "%Y-%m") as month, COUNT(*) as total')
            ->where('enrolled_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthLabels      = $months->map(fn($m) => Carbon::parse($m)->translatedFormat('M Y'))->values();
        $enrollmentSeries = $months->map(fn($m) => (int) ($enrollmentsRaw[$m] ?? 0))->values();

        // Tasa de completación por lección
        $lessonStats = $course->modules->flatMap(function ($module) use ($totalEnrolled) {
            return $module->lessons->map(function ($lesson) use ($totalEnrolled) {
                $completed = $lesson->progressRecords()->where('completed', true)->count();
                return [
                    'title'     => $lesson->title,
                    'type'      => $lesson->type,
                    'completed' => $completed,
                    'rate'      => $totalEnrolled > 0 ? (int) round($completed / $totalEnrolled * 100) : 0,
                ];
            });
        });

        // Lista de estudiantes ordenada por progreso
        $studentStats = $enrollments->sortByDesc('progress')->values();

        return view('admin.courses.show', compact(
            'course',
            'totalEnrolled',
            'totalCompleted',
            'avgProgress',
            'completionRate',
            'progressGroups',
            'monthLabels',
            'enrollmentSeries',
            'lessonStats',
            'studentStats',
        ));
    }
}