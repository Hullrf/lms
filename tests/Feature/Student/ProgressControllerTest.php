<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;
    private Lesson $lesson1;
    private Lesson $lesson2;

    protected function setUp(): void
    {
        parent::setUp();

        $instructor    = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course  = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'status'        => 'published',
            'is_free'       => true,
        ]);
        $module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);
        $this->lesson1 = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Lesson 1',
            'slug'       => 'lesson-1',
            'type'       => 'video',
            'sort_order' => 1,
            'is_preview' => true,
        ]);
        $this->lesson2 = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Lesson 2',
            'slug'       => 'lesson-2',
            'type'       => 'video',
            'sort_order' => 2,
        ]);
        Enrollment::create([
            'user_id'     => $this->student->id,
            'course_id'   => $this->course->id,
            'progress'    => 0,
            'enrolled_at' => now(),
        ]);
    }

    public function test_update_returns_json_with_progress(): void
    {
        $this->actingAs($this->student)
            ->postJson("/progress/{$this->lesson1->id}", ['position' => 10])
            ->assertOk()
            ->assertJsonStructure(['progress', 'nextLesson']);
    }

    public function test_update_marks_lesson_as_completed(): void
    {
        $this->actingAs($this->student)
            ->postJson("/progress/{$this->lesson1->id}");

        $this->assertDatabaseHas('lesson_progress', [
            'user_id'   => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
        ]);
    }

    public function test_update_calculates_correct_percentage(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/progress/{$this->lesson1->id}");

        $response->assertJson(['progress' => 50]); // 1 of 2 lessons
    }

    public function test_update_returns_next_lesson_when_available(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/progress/{$this->lesson1->id}");

        $response->assertJsonPath('nextLesson.id', $this->lesson2->id);
    }

    public function test_update_requires_authentication(): void
    {
        $this->postJson("/progress/{$this->lesson1->id}")
            ->assertUnauthorized();
    }
}
