<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\QuizQuestion;

class LessonController extends Controller
{
    public function show(string $courseSlug, string $lessonSlug)
    {
        $course = Course::published()
            ->with('modules.lessons')
            ->where('slug', $courseSlug)
            ->firstOrFail();

        $lesson = Lesson::where('slug', $lessonSlug)
            ->whereHas('module', fn($q) => $q->where('course_id', $course->id))
            ->with('resources')
            ->firstOrFail();

        $user = auth()->user();

        // Obtener todas las lecciones del curso en orden
        $allLessons = $course->modules->flatMap->lessons->values();
        $lessonIndex = $allLessons->search(fn($l) => $l->id === $lesson->id);

        // Auto-completar lecciones preview al visitarlas
        if ($lesson->is_preview) {
            LessonProgress::updateOrCreate(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                ['completed' => true, 'completed_at' => now(), 'last_position' => 0]
            );
        }

        // Verificar secuencia para lecciones no-preview
        if (!$lesson->is_preview && $lessonIndex > 0) {
            $previousLesson = $allLessons[$lessonIndex - 1];
            $previousCompleted = $previousLesson->isCompletedBy($user);

            if (!$previousCompleted) {
                return redirect()
                    ->route('lesson.show', [$course->slug, $previousLesson->slug])
                    ->with('error', 'Debes completar la lección anterior antes de continuar.');
            }
        }

        $progress = $user->progress()->where('lesson_id', $lesson->id)->first();

        // Datos del quiz si aplica
        $quizQuestions = $lesson->type === 'quiz'
            ? $lesson->questions()->with('options')->orderBy('sort_order')->get()
            : collect();

        return view('courses.learn', compact('course', 'lesson', 'progress', 'allLessons', 'quizQuestions'));
    }
}
