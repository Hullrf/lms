<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(private readonly ProgressService $progress) {}

    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $user    = $request->user();
        $course  = $lesson->module->course;

        $this->progress->completeLesson($user, $lesson, $request->input('position', 0));
        $percent    = $this->progress->recalculateCourseProgress($user, $course);
        $nextLesson = $this->progress->getNextLesson($user, $course, $lesson);

        return response()->json([
            'progress'   => $percent,
            'nextLesson' => $nextLesson,
        ]);
    }
}
