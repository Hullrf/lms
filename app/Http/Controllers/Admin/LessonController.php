<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LessonController extends Controller
{
    public function store(Request $request, Module $module)
    {
        $this->authorize('update', $module->course);

        $data = $request->validate([
            'title'          => 'required|string|max:200',
            'content'        => 'nullable|string',
            'video_url'      => 'nullable|url',
            'video_duration' => 'nullable|integer',
            'type'           => 'required|in:video,text,quiz,file',
            'is_preview'     => 'boolean',
            'sort_order'     => 'integer|min:0',
        ]);

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

    public function update(Request $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->module->course);

        $data = $request->validate([
            'title'          => 'required|string|max:200',
            'content'        => 'nullable|string',
            'video_url'      => 'nullable|url',
            'video_duration' => 'nullable|integer',
            'type'           => 'required|in:video,text,quiz,file',
            'is_preview'     => 'boolean',
            'sort_order'     => 'integer|min:0',
        ]);

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