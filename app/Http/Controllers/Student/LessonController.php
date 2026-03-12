<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;

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

        $user     = auth()->user();
        $progress = $user->progress()->where('lesson_id', $lesson->id)->first();

        return view('courses.learn', compact('course', 'lesson', 'progress'));
    }
}