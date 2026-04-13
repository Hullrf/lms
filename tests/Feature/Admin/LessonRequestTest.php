<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private Course $course;
    private Module $module;
    private array $validData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['role' => 'instructor']);

        $this->course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'status'        => 'draft',
            'is_free'       => true,
            'level'         => 'beginner',
        ]);

        $this->module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Módulo 1',
            'sort_order' => 1,
        ]);

        $this->validData = [
            'title' => 'Mi lección',
            'type'  => 'text',
        ];
    }

    // ── store ──────────────────────────────────────────────────────────────

    public function test_store_requires_title(): void
    {
        $data = $this->validData;
        unset($data['title']);

        $this->actingAs($this->instructor)
            ->postJson(route('admin.modules.lessons.store', $this->module), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_store_requires_video_url_for_video_type(): void
    {
        $data = array_merge($this->validData, ['type' => 'video']);
        // no video_url

        $this->actingAs($this->instructor)
            ->postJson(route('admin.modules.lessons.store', $this->module), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('video_url');
    }

    public function test_store_video_url_not_required_for_text_type(): void
    {
        $data = array_merge($this->validData, ['type' => 'text']);

        $this->actingAs($this->instructor)
            ->postJson(route('admin.modules.lessons.store', $this->module), $data)
            ->assertSuccessful()
            ->assertJsonMissingValidationErrors('video_url');
    }

    public function test_store_rejects_invalid_video_url(): void
    {
        $data = array_merge($this->validData, ['type' => 'video', 'video_url' => 'not-a-url']);

        $this->actingAs($this->instructor)
            ->postJson(route('admin.modules.lessons.store', $this->module), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('video_url');
    }

    public function test_store_rejects_invalid_type(): void
    {
        $data = array_merge($this->validData, ['type' => 'audio']);

        $this->actingAs($this->instructor)
            ->postJson(route('admin.modules.lessons.store', $this->module), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_store_rejects_passing_score_above_100(): void
    {
        $data = array_merge($this->validData, ['passing_score' => 101]);

        $this->actingAs($this->instructor)
            ->postJson(route('admin.modules.lessons.store', $this->module), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('passing_score');
    }

    public function test_store_rejects_negative_passing_score(): void
    {
        $data = array_merge($this->validData, ['passing_score' => -1]);

        $this->actingAs($this->instructor)
            ->postJson(route('admin.modules.lessons.store', $this->module), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('passing_score');
    }

    public function test_store_accepts_valid_passing_score(): void
    {
        $data = array_merge($this->validData, ['passing_score' => 80]);

        $this->actingAs($this->instructor)
            ->postJson(route('admin.modules.lessons.store', $this->module), $data)
            ->assertSuccessful()
            ->assertJsonMissingValidationErrors('passing_score');
    }

    public function test_store_denied_when_instructor_does_not_own_course(): void
    {
        $other = User::factory()->create(['role' => 'instructor']);

        $this->actingAs($other)
            ->postJson(route('admin.modules.lessons.store', $this->module), $this->validData)
            ->assertForbidden();
    }

    // ── update ─────────────────────────────────────────────────────────────

    public function test_update_requires_video_url_for_video_type(): void
    {
        $lesson = Lesson::create([
            'module_id'  => $this->module->id,
            'title'      => 'Lección existente',
            'slug'       => 'leccion-existente',
            'type'       => 'video',
            'sort_order' => 1,
        ]);

        $data = ['title' => 'Lección existente', 'type' => 'video'];
        // no video_url

        $this->actingAs($this->instructor)
            ->putJson(route('admin.lessons.update', $lesson), $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('video_url');
    }

    public function test_update_denied_when_instructor_does_not_own_course(): void
    {
        $other = User::factory()->create(['role' => 'instructor']);
        $lesson = Lesson::create([
            'module_id'  => $this->module->id,
            'title'      => 'Lección',
            'slug'       => 'leccion',
            'type'       => 'text',
            'sort_order' => 1,
        ]);

        $data = ['title' => 'Lección', 'type' => 'text'];

        $this->actingAs($other)
            ->putJson(route('admin.lessons.update', $lesson), $data)
            ->assertForbidden();
    }
}
