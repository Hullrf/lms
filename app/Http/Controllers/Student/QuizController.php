<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;

class QuizController extends Controller
{
    public function submit(\Illuminate\Http\Request $request, Lesson $lesson)
    {
        $answers   = $request->input('answers', []);
        $questions = $lesson->questions()->with('options')->orderBy('sort_order')->get();

        $total   = $questions->count();
        $correct = 0;
        $results = [];

        foreach ($questions as $question) {
            $selectedId   = isset($answers[$question->id]) ? (int) $answers[$question->id] : null;
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect     = $selectedId && $correctOption && $selectedId === $correctOption->id;

            if ($isCorrect) $correct++;

            $results[$question->id] = [
                'correct'        => $isCorrect,
                'selected'       => $selectedId,
                'correct_option' => $correctOption?->id,
            ];
        }

        $score  = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $passed = $score >= 70;

        if ($passed) {
            $user = auth()->user();

            LessonProgress::updateOrCreate(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                ['completed' => true, 'completed_at' => now(), 'last_position' => 0]
            );

            // Actualizar progreso de la matrícula
            $module    = $lesson->module;
            $course    = $module->course;
            $lessonIds = \App\Models\Lesson::whereIn('module_id', $course->modules()->pluck('id'))->pluck('id');
            $totalLessons = $lessonIds->count();
            $completedCount = LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->where('completed', true)
                ->count();

            $percent    = $totalLessons > 0 ? (int) round($completedCount / $totalLessons * 100) : 0;
            $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();

            if ($enrollment) {
                $enrollment->progress     = $percent;
                $enrollment->completed_at = $percent === 100 ? now() : null;
                $enrollment->save();
            }
        }

        $course = $lesson->module->course;

        return redirect()
            ->route('lesson.show', [$course->slug, $lesson->slug])
            ->with('quiz_results', [
                'score'   => $score,
                'passed'  => $passed,
                'correct' => $correct,
                'total'   => $total,
                'results' => $results,
            ]);
    }
}
