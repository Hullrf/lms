<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Http\Requests\Admin\UpdateLessonRequest;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Support\Str;

class LessonController extends Controller
{
    public function store(StoreLessonRequest $request, Module $module)
    {
        $data = $request->validated();

        $data['slug']       = Str::slug($data['title']);
        $data['sort_order'] = $data['sort_order'] ?? $module->lessons()->count();
        $lesson = $module->lessons()->create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'id'    => $lesson->id,
                'title' => $lesson->title,
                'type'  => $lesson->type,
            ]);
        }

        return back()->with('success', 'Lección creada.');
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson)
    {
        $data = $request->validated();
        $lesson->update($data);
        return back()->with('success', 'Lección actualizada.');
    }

    public function destroy(Lesson $lesson)
    {
        $this->authorize('update', $lesson->module->course);

        $courseId = $lesson->module->course_id;
        $lesson->delete();
        return redirect()->route('admin.courses.show', $courseId)
                         ->with('success', 'Lección eliminada.');
    }
}
