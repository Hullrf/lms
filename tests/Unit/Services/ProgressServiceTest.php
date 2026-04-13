<?php

namespace Tests\Unit\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $service;
    private User $student;
    private Course $course;
    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProgressService(new CertificateService());

        $instructor    = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course  = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'is_free'       => true,
        ]);
        $this->module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);
        Enrollment::create([
            'user_id'     => $this->student->id,
            'course_id'   => $this->course->id,
            'progress'    => 0,
            'enrolled_at' => now(),
        ]);
    }

    private function makeLesson(int $sortOrder = 1): Lesson
    {
        return Lesson::create([
            'module_id'  => $this->module->id,
            'title'      => "Lesson $sortOrder",
            'slug'       => "lesson-$sortOrder-" . uniqid(),
            'type'       => 'video',
            'sort_order' => $sortOrder,
        ]);
    }

    public function test_complete_lesson_creates_lesson_progress(): void
    {
        $lesson = $this->makeLesson();

        $progress = $this->service->completeLesson($this->student, $lesson, 30);

        $this->assertInstanceOf(LessonProgress::class, $progress);
        $this->assertTrue($progress->completed);
        $this->assertEquals(30, $progress->last_position);
        $this->assertDatabaseHas('lesson_progress', [
            'user_id'   => $this->student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
        ]);
    }

    public function test_complete_lesson_is_idempotent(): void
    {
        $lesson = $this->makeLesson();

        $this->service->completeLesson($this->student, $lesson);
        $this->service->completeLesson($this->student, $lesson);

        $this->assertDatabaseCount('lesson_progress', 1);
    }

    public function test_recalculate_returns_correct_percentage(): void
    {
        $lesson1 = $this->makeLesson(1);
        $lesson2 = $this->makeLesson(2);

        $this->service->completeLesson($this->student, $lesson1);
        $percent = $this->service->recalculateCourseProgress($this->student, $this->course);

        $this->assertEquals(50, $percent);
        $this->assertDatabaseHas('enrollments', [
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
            'progress'  => 50,
        ]);
    }

    public function test_recalculate_sets_completed_at_when_100_percent(): void
    {
        $lesson = $this->makeLesson(1);

        $this->service->completeLesson($this->student, $lesson);
        $percent = $this->service->recalculateCourseProgress($this->student, $this->course);

        $this->assertEquals(100, $percent);
        $enrollment = $this->student->enrollments()->where('course_id', $this->course->id)->first();
        $this->assertNotNull($enrollment->completed_at);
    }

    public function test_recalculate_issues_certificate_when_100_percent(): void
    {
        $lesson = $this->makeLesson(1);

        $this->service->completeLesson($this->student, $lesson);
        $this->service->recalculateCourseProgress($this->student, $this->course);

        $this->assertDatabaseHas('certificates', [
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_get_next_lesson_returns_next_uncompleted_lesson(): void
    {
        $lesson1 = $this->makeLesson(1);
        $lesson2 = $this->makeLesson(2);

        $this->service->completeLesson($this->student, $lesson1);

        $next = $this->service->getNextLesson($this->student, $this->course, $lesson1);

        $this->assertNotNull($next);
        $this->assertEquals($lesson2->id, $next['id']);
    }

    public function test_get_next_lesson_returns_null_when_already_completed(): void
    {
        $lesson1 = $this->makeLesson(1);
        $lesson2 = $this->makeLesson(2);

        $this->service->completeLesson($this->student, $lesson1);
        $this->service->completeLesson($this->student, $lesson2);

        $next = $this->service->getNextLesson($this->student, $this->course, $lesson1);

        $this->assertNull($next);
    }

    public function test_get_next_lesson_returns_null_at_last_lesson(): void
    {
        $lesson = $this->makeLesson(1);

        $next = $this->service->getNextLesson($this->student, $this->course, $lesson);

        $this->assertNull($next);
    }
}
