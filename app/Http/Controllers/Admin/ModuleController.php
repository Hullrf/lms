<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function store(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'sort_order'  => 'integer|min:0',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? $course->modules()->count();
        $module = $course->modules()->create($data);

        if ($request->wantsJson()) {
            return response()->json(['id' => $module->id, 'title' => $module->title]);
        }

        return back()->with('success', 'Módulo creado.');
    }

    public function update(Request $request, Module $module)
    {
        $this->authorize('update', $module->course);

        $data = $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'sort_order'  => 'integer|min:0',
        ]);

        $module->update($data);
        return back()->with('success', 'Módulo actualizado.');
    }

    public function destroy(Module $module)
    {
        $this->authorize('update', $module->course);

        $courseId = $module->course_id;
        $module->delete();
        return redirect()->route('admin.courses.show', $courseId)
                         ->with('success', 'Módulo eliminado.');
    }
}