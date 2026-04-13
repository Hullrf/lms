<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class ProgressService
{
    public function __construct(
        private readonly CertificateService $certificateService
    ) {}

    public function completeLesson(User $user, Lesson $lesson, int $position = 0): LessonProgress
    {
        return LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'completed'     => true,
                'completed_at'  => now(),
                'last_position' => $position,
            ]
        );
    }

    public function recalculateCourseProgress(User $user, Course $course): int
    {
        $lessonIds = Lesson::whereIn(
            'module_id',
            $course->modules()->pluck('id')
        )->pluck('id');

        $totalLessons = $lessonIds->count();

        if ($totalLessons === 0) {
            return 0;
        }

        $completed = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('completed', true)
            ->count();

        $percent = (int) round($completed / $totalLessons * 100);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment) {
            $enrollment->progress     = $percent;
            $enrollment->completed_at = $percent === 100 ? now() : null;
            $enrollment->save();
        }

        if ($percent === 100) {
            $this->certificateService->issueIfCompleted($user, $course);
        }

        return $percent;
    }

    public function getNextLesson(User $user, Course $course, Lesson $currentLesson): ?array
    {
        $allLessons = $course->modules()
            ->orderBy('sort_order')
            ->with(['lessons' => fn($q) => $q->orderBy('sort_order')])
            ->get()
            ->flatMap(fn($m) => $m->lessons);

        $currentIndex = $allLessons->search(fn($l) => $l->id === $currentLesson->id);
        $nextLesson   = $currentIndex !== false ? $allLessons->get($currentIndex + 1) : null;

        if (!$nextLesson || $nextLesson->isCompletedBy($user)) {
            return null;
        }

        return [
            'id'    => $nextLesson->id,
            'title' => $nextLesson->title,
            'type'  => $nextLesson->type,
            'url'   => route('lesson.show', [$course->slug, $nextLesson->slug]),
        ];
    }
}
