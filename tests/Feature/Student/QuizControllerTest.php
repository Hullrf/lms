<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Course $course;
    private Lesson $lesson;
    private QuizQuestion $question;
    private QuizOption $correctOption;
    private QuizOption $wrongOption;

    protected function setUp(): void
    {
        parent::setUp();

        $instructor    = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course  = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'is_free'       => true,
        ]);
        $module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);
        $this->lesson = Lesson::create([
            'module_id'   => $module->id,
            'title'       => 'Quiz Lesson',
            'slug'        => 'quiz-lesson',
            'type'        => 'quiz',
            'sort_order'  => 1,
            'is_preview'  => true,
        ]);
        $this->question = QuizQuestion::create([
            'lesson_id'  => $this->lesson->id,
            'question'   => 'What is 2+2?',
            'sort_order' => 1,
        ]);
        $this->correctOption = QuizOption::create([
            'question_id' => $this->question->id,
            'text'        => '4',
            'is_correct'  => true,
        ]);
        $this->wrongOption = QuizOption::create([
            'question_id' => $this->question->id,
            'text'        => '5',
            'is_correct'  => false,
        ]);
        Enrollment::create([
            'user_id'     => $this->student->id,
            'course_id'   => $this->course->id,
            'progress'    => 0,
            'enrolled_at' => now(),
        ]);
    }

    public function test_submit_returns_json_response(): void
    {
        $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->correctOption->id],
            ])
            ->assertOk()
            ->assertJsonStructure(['score', 'passed', 'correct', 'total', 'results']);
    }

    public function test_submit_with_correct_answer_marks_lesson_completed(): void
    {
        $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->correctOption->id],
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id'   => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => true,
        ]);
    }

    public function test_submit_with_wrong_answer_does_not_mark_completed(): void
    {
        $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->wrongOption->id],
            ]);

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id'   => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => true,
        ]);
    }

    public function test_submit_passed_includes_progress_in_response(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->correctOption->id],
            ]);

        $response->assertJsonPath('passed', true);
        $response->assertJsonPath('progress', 100);
    }

    public function test_submit_failed_does_not_include_progress_in_response(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/quiz/{$this->lesson->id}", [
                'answers' => [$this->question->id => $this->wrongOption->id],
            ]);

        $response->assertJsonPath('passed', false);
        $response->assertJsonMissingPath('progress');
    }

    public function test_submit_requires_authentication(): void
    {
        $this->postJson("/quiz/{$this->lesson->id}", [])
            ->assertUnauthorized();
    }
}
