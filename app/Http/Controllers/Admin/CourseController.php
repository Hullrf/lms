<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Course::class);

        $courses = auth()->user()->isAdmin()
            ? Course::with('instructor', 'category')->latest()->paginate(15)
            : Course::with('instructor', 'category')
                    ->where('instructor_id', auth()->id())
                    ->latest()->paginate(15);

        return view('admin.courses.index', compact('courses'));
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
        $course->load('modules.lessons');
        return view('admin.courses.show', compact('course'));
    }
}