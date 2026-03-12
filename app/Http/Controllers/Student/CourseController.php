<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::published()->with('instructor', 'category');

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) =>
                $q->where('slug', $request->category)
            );
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $courses    = $query->paginate(12);
        $categories = Category::all();

        return view('courses.index', compact('courses', 'categories'));
    }

    public function show(string $slug)
    {
        $course = Course::published()
            ->with(['instructor', 'category', 'modules.lessons', 'reviews.user'])
            ->where('slug', $slug)
            ->firstOrFail();

        $isEnrolled = auth()->check() && auth()->user()->isEnrolledIn($course);

        return view('courses.show', compact('course', 'isEnrolled'));
    }
}