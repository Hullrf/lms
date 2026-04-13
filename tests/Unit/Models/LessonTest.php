<?php

namespace Tests\Unit\Models;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    private function makeLesson(array $attributes = []): Lesson
    {
        $instructor = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course-' . uniqid(),
            'is_free'       => true,
        ]);
        $module = Module::create([
            'course_id'  => $course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);

        return Lesson::create(array_merge([
            'module_id'  => $module->id,
            'title'      => 'Lesson 1',
            'slug'       => 'lesson-1-' . uniqid(),
            'type'       => 'video',
            'sort_order' => 1,
        ], $attributes));
    }

    public function test_passing_score_returns_70_when_null(): void
    {
        $lesson = $this->makeLesson(['passing_score' => null]);
        $this->assertSame(70, $lesson->passingScore());
    }

    public function test_passing_score_returns_custom_value(): void
    {
        $lesson = $this->makeLesson(['passing_score' => 85]);
        $this->assertSame(85, $lesson->passingScore());
    }

    public function test_passing_score_returns_zero_when_set_to_zero(): void
    {
        $lesson = $this->makeLesson(['passing_score' => 0]);
        $this->assertSame(0, $lesson->passingScore());
    }
}
