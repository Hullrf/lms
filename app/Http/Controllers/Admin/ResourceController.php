<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonResource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function store(Request $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->module->course);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'url'  => 'required|url',
        ]);

        $lesson->resources()->create([
            'name' => $data['name'],
            'type' => 'link',
            'url'  => $data['url'],
        ]);

        return back()->with('success', 'Recurso añadido.');
    }

    public function destroy(LessonResource $resource)
    {
        $this->authorize('update', $resource->lesson->module->course);
        $resource->delete();
        return back()->with('success', 'Recurso eliminado.');
    }
}
