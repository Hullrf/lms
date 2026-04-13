<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreModuleRequest;
use App\Http\Requests\Admin\UpdateModuleRequest;
use App\Models\Course;
use App\Models\Module;

class ModuleController extends Controller
{
    public function store(StoreModuleRequest $request, Course $course)
    {
        $data = $request->validated();

        $data['sort_order'] = $data['sort_order'] ?? $course->modules()->count();
        $module = $course->modules()->create($data);

        if ($request->wantsJson()) {
            return response()->json(['id' => $module->id, 'title' => $module->title]);
        }

        return back()->with('success', 'Módulo creado.');
    }

    public function update(UpdateModuleRequest $request, Module $module)
    {
        $module->update($request->validated());
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
