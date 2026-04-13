<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuizService $service;
    private Lesson $lesson;
    private QuizQuestion $question1;
    private QuizQuestion $question2;
    private QuizOption $correct1;
    private QuizOption $correct2;
    private QuizOption $wrong1;
    private QuizOption $wrong2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuizService();

        $instructor = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'is_free'       => true,
        ]);
        $module = Module::create([
            'course_id'  => $course->id,
            'title'      => 'Module 1',
            'sort_order' => 1,
        ]);
        $this->lesson = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Quiz Lesson',
            'slug'       => 'quiz-lesson',
            'type'       => 'quiz',
            'sort_order' => 1,
        ]);

        // Question 1 with options
        $this->question1 = QuizQuestion::create([
            'lesson_id'  => $this->lesson->id,
            'question'   => 'Question 1',
            'sort_order' => 1,
        ]);
        $this->correct1 = QuizOption::create([
            'question_id' => $this->question1->id,
            'text'        => 'Correct 1',
            'is_correct'  => true,
        ]);
        $this->wrong1 = QuizOption::create([
            'question_id' => $this->question1->id,
            'text'        => 'Wrong 1',
            'is_correct'  => false,
        ]);

        // Question 2 with options
        $this->question2 = QuizQuestion::create([
            'lesson_id'  => $this->lesson->id,
            'question'   => 'Question 2',
            'sort_order' => 2,
        ]);
        $this->correct2 = QuizOption::create([
            'question_id' => $this->question2->id,
            'text'        => 'Correct 2',
            'is_correct'  => true,
        ]);
        $this->wrong2 = QuizOption::create([
            'question_id' => $this->question2->id,
            'text'        => 'Wrong 2',
            'is_correct'  => false,
        ]);
    }

    public function test_grade_returns_100_when_all_correct(): void
    {
        $answers = [
            $this->question1->id => $this->correct1->id,
            $this->question2->id => $this->correct2->id,
        ];

        $result = $this->service->grade($this->lesson, $answers);

        $this->assertEquals(100, $result['score']);
        $this->assertTrue($result['passed']);
        $this->assertEquals(2, $result['correct']);
        $this->assertEquals(2, $result['total']);
    }

    public function test_grade_returns_0_when_all_wrong(): void
    {
        $answers = [
            $this->question1->id => $this->wrong1->id,
            $this->question2->id => $this->wrong2->id,
        ];

        $result = $this->service->grade($this->lesson, $answers);

        $this->assertEquals(0, $result['score']);
        $this->assertFalse($result['passed']);
        $this->assertEquals(0, $result['correct']);
    }

    public function test_grade_returns_50_with_one_correct(): void
    {
        $answers = [
            $this->question1->id => $this->correct1->id,
            $this->question2->id => $this->wrong2->id,
        ];

        $result = $this->service->grade($this->lesson, $answers);

        $this->assertEquals(50, $result['score']);
        $this->assertFalse($result['passed']); // 50 < 70 (default)
    }

    public function test_grade_respects_custom_passing_score(): void
    {
        $this->lesson->update(['passing_score' => 40]);

        $answers = [
            $this->question1->id => $this->correct1->id,
            $this->question2->id => $this->wrong2->id,
        ];

        $result = $this->service->grade($this->lesson->fresh(), $answers);

        $this->assertEquals(50, $result['score']);
        $this->assertTrue($result['passed']); // 50 >= 40 (custom)
    }

    public function test_grade_marks_correct_and_wrong_per_question(): void
    {
        $answers = [
            $this->question1->id => $this->correct1->id,
            $this->question2->id => $this->wrong2->id,
        ];

        $result = $this->service->grade($this->lesson, $answers);

        $this->assertTrue($result['results'][$this->question1->id]['correct']);
        $this->assertFalse($result['results'][$this->question2->id]['correct']);
        $this->assertEquals($this->correct1->id, $result['results'][$this->question1->id]['correct_option']);
        $this->assertEquals($this->wrong2->id, $result['results'][$this->question2->id]['selected']);
    }

    public function test_grade_handles_unanswered_questions(): void
    {
        $result = $this->service->grade($this->lesson, []);

        $this->assertEquals(0, $result['score']);
        $this->assertFalse($result['passed']);
        $this->assertNull($result['results'][$this->question1->id]['selected']);
    }
}
