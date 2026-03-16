<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->isInstructor()) {
            return $this->instructorDashboard();
        }

        // ── Tarjetas resumen ──────────────────────────────────────────
        $stats = [
            'total_courses'     => Course::count(),
            'total_students'    => User::where('role', 'student')->count(),
            'total_enrollments' => Enrollment::count(),
            'total_revenue'     => Order::where('status', 'paid')->sum('amount'),
        ];

        // ── Últimas 6 matrículas recientes ────────────────────────────
        $recentEnrollments = Enrollment::with('user', 'course')
            ->latest('enrolled_at')
            ->take(10)
            ->get();

        // ── Series temporales: últimos 6 meses ────────────────────────
        $months = collect(range(5, 0))
            ->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $enrollmentsRaw = Enrollment::selectRaw('DATE_FORMAT(enrolled_at, "%Y-%m") as month, COUNT(*) as total')
            ->where('enrolled_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        $revenueRaw = Order::selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month, SUM(amount) as total')
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthLabels      = $months->map(fn ($m) => Carbon::parse($m)->translatedFormat('M Y'))->values();
        $enrollmentSeries = $months->map(fn ($m) => (int) ($enrollmentsRaw[$m] ?? 0))->values();
        $revenueSeries    = $months->map(fn ($m) => (float) ($revenueRaw[$m] ?? 0))->values();

        // ── Cursos por categoría ──────────────────────────────────────
        $byCategory = Category::withCount('courses')
            ->having('courses_count', '>', 0)
            ->orderByDesc('courses_count')
            ->get();

        // ── Usuarios por rol ─────────────────────────────────────────
        $byRole = User::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        // ── Top 5 cursos más populares ────────────────────────────────
        $topCourses = Course::withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentEnrollments',
            'monthLabels',
            'enrollmentSeries',
            'revenueSeries',
            'byCategory',
            'byRole',
            'topCourses',
        ));
    }

    private function instructorDashboard()
    {
        $user      = auth()->user();
        $courseIds = Course::where('instructor_id', $user->id)->pluck('id');

        $stats = [
            'my_courses'     => $courseIds->count(),
            'total_students' => Enrollment::whereIn('course_id', $courseIds)->distinct('user_id')->count('user_id'),
            'total_revenue'  => Order::whereIn('course_id', $courseIds)->where('status', 'paid')->sum('amount'),
            'avg_progress'   => (int) round(Enrollment::whereIn('course_id', $courseIds)->avg('progress') ?? 0),
        ];

        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));

        $enrollmentsRaw = Enrollment::selectRaw('DATE_FORMAT(enrolled_at, "%Y-%m") as month, COUNT(*) as total')
            ->whereIn('course_id', $courseIds)
            ->where('enrolled_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthLabels      = $months->map(fn($m) => Carbon::parse($m)->translatedFormat('M Y'))->values();
        $enrollmentSeries = $months->map(fn($m) => (int) ($enrollmentsRaw[$m] ?? 0))->values();

        $topCourses = Course::where('instructor_id', $user->id)
            ->withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get();

        $recentEnrollments = Enrollment::with('user', 'course')
            ->whereIn('course_id', $courseIds)
            ->latest('enrolled_at')
            ->take(10)
            ->get();

        return view('admin.instructor-dashboard', compact(
            'stats',
            'monthLabels',
            'enrollmentSeries',
            'topCourses',
            'recentEnrollments',
        ));
    }
}
