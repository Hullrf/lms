<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function update(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'completed'     => true,
                'completed_at'  => now(),
                'last_position' => $request->input('position', 0),
            ]
        );

        // Cargar el curso desde el módulo
        $module = $lesson->module()->first();
        $course = $module->course()->first();

        // Todas las lecciones del curso
        $lessonIds = Lesson::whereIn('module_id',
            $course->modules()->pluck('id')
        )->pluck('id');

        $totalLessons = $lessonIds->count();

        $completed = LessonProgress::where('user_id', $user->id)
                                   ->whereIn('lesson_id', $lessonIds)
                                   ->where('completed', true)
                                   ->count();

        $percent = $totalLessons > 0 ? (int) round($completed / $totalLessons * 100) : 0;

        // Buscar y actualizar la matrícula
        $enrollment = Enrollment::where('user_id', $user->id)
                                 ->where('course_id', $course->id)
                                 ->first();

        if ($enrollment) {
            $enrollment->progress     = $percent;
            $enrollment->completed_at = $percent === 100 ? now() : null;
            $enrollment->save();
        }

        return response()->json(['progress' => $percent]);
    }
}