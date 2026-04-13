<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private Course $course;

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
    }

    // ── store ──────────────────────────────────────────────────────────────

    public function test_store_requires_title(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('admin.courses.modules.store', $this->course), [])
            ->assertSessionHasErrors('title');
    }

    public function test_store_rejects_description_over_1000_chars(): void
    {
        $data = [
            'title'       => 'Módulo válido',
            'description' => str_repeat('a', 1001),
        ];

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.modules.store', $this->course), $data)
            ->assertSessionHasErrors('description');
    }

    public function test_store_accepts_description_at_1000_chars(): void
    {
        $data = [
            'title'       => 'Módulo válido',
            'description' => str_repeat('a', 1000),
        ];

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.modules.store', $this->course), $data)
            ->assertSessionDoesntHaveErrors('description');
    }

    public function test_store_denied_when_instructor_does_not_own_course(): void
    {
        $other = User::factory()->create(['role' => 'instructor']);

        $this->actingAs($other)
            ->post(route('admin.courses.modules.store', $this->course), ['title' => 'Módulo'])
            ->assertForbidden();
    }

    // ── update ─────────────────────────────────────────────────────────────

    public function test_update_requires_title(): void
    {
        $module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Módulo existente',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->instructor)
            ->patch(route('admin.modules.update', $module), [])
            ->assertSessionHasErrors('title');
    }

    public function test_update_rejects_description_over_1000_chars(): void
    {
        $module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Módulo existente',
            'sort_order' => 1,
        ]);

        $data = [
            'title'       => 'Módulo existente',
            'description' => str_repeat('b', 1001),
        ];

        $this->actingAs($this->instructor)
            ->patch(route('admin.modules.update', $module), $data)
            ->assertSessionHasErrors('description');
    }

    public function test_update_denied_when_instructor_does_not_own_course(): void
    {
        $other = User::factory()->create(['role' => 'instructor']);
        $module = Module::create([
            'course_id'  => $this->course->id,
            'title'      => 'Módulo',
            'sort_order' => 1,
        ]);

        $this->actingAs($other)
            ->patch(route('admin.modules.update', $module), ['title' => 'Módulo'])
            ->assertForbidden();
    }
}
