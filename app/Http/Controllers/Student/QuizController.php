<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\ProgressService;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService     $quiz,
        private readonly ProgressService $progress,
    ) {}

    public function submit(Request $request, Lesson $lesson): JsonResponse
    {
        $user   = $request->user();
        $result = $this->quiz->grade($lesson, $request->input('answers', []));

        if ($result['passed']) {
            $course               = $lesson->module->course;
            $this->progress->completeLesson($user, $lesson);
            $result['progress']   = $this->progress->recalculateCourseProgress($user, $course);
            $result['nextLesson'] = $this->progress->getNextLesson($user, $course, $lesson);
        }

        return response()->json($result);
    }
}
