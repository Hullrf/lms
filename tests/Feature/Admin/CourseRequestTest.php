<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private array $validData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->create(['role' => 'instructor']);

        $this->validData = [
            'title'  => 'Mi Curso de Prueba',
            'level'  => 'beginner',
            'status' => 'draft',
            'is_free' => '1',
        ];
    }

    // ── store ──────────────────────────────────────────────────────────────

    public function test_store_requires_title(): void
    {
        $data = $this->validData;
        unset($data['title']);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionHasErrors('title');
    }

    public function test_store_requires_price_when_not_free(): void
    {
        $data = array_merge($this->validData, ['is_free' => '0']);
        unset($data['price']);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionHasErrors('price');
    }

    public function test_store_price_not_required_when_free(): void
    {
        $data = array_merge($this->validData, ['is_free' => '1']);
        unset($data['price']);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionDoesntHaveErrors('price');
    }

    public function test_store_rejects_invalid_level(): void
    {
        $data = array_merge($this->validData, ['level' => 'expert']);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionHasErrors('level');
    }

    public function test_store_rejects_invalid_status(): void
    {
        $data = array_merge($this->validData, ['status' => 'hidden']);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionHasErrors('status');
    }

    public function test_store_rejects_invalid_intro_video_url(): void
    {
        $data = array_merge($this->validData, ['intro_video' => 'not-a-url']);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionHasErrors('intro_video');
    }

    public function test_store_accepts_valid_intro_video_url(): void
    {
        $data = array_merge($this->validData, ['intro_video' => 'https://youtube.com/watch?v=abc123']);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionDoesntHaveErrors('intro_video');
    }

    public function test_store_rejects_nonexistent_category(): void
    {
        $data = array_merge($this->validData, ['category_id' => 9999]);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionHasErrors('category_id');
    }

    public function test_store_accepts_existing_category(): void
    {
        $category = Category::create(['name' => 'Programación', 'slug' => 'programacion']);
        $data = array_merge($this->validData, ['category_id' => $category->id]);

        $this->actingAs($this->instructor)
            ->post(route('admin.courses.store'), $data)
            ->assertSessionDoesntHaveErrors('category_id');
    }

    public function test_store_denied_for_student(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post(route('admin.courses.store'), $this->validData)
            ->assertForbidden();
    }

    // ── update ─────────────────────────────────────────────────────────────

    public function test_update_requires_price_when_not_free(): void
    {
        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title'         => 'Curso existente',
            'slug'          => 'curso-existente',
            'status'        => 'draft',
            'is_free'       => false,
            'price'         => 100,
            'level'         => 'beginner',
        ]);

        $data = [
            'title'   => 'Curso existente',
            'level'   => 'beginner',
            'status'  => 'draft',
            'is_free' => '0',
            // no price
        ];

        $this->actingAs($this->instructor)
            ->put(route('admin.courses.update', $course), $data)
            ->assertSessionHasErrors('price');
    }

    public function test_update_denied_for_other_instructor(): void
    {
        $other = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'instructor_id' => $other->id,
            'title'         => 'Ajeno',
            'slug'          => 'ajeno',
            'status'        => 'draft',
            'is_free'       => true,
            'level'         => 'beginner',
        ]);

        $data = [
            'title'   => 'Modificado',
            'level'   => 'beginner',
            'status'  => 'draft',
            'is_free' => '1',
        ];

        $this->actingAs($this->instructor)
            ->put(route('admin.courses.update', $course), $data)
            ->assertForbidden();
    }

    public function test_update_allowed_for_course_owner(): void
    {
        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title'         => 'Mi Curso',
            'slug'          => 'mi-curso',
            'status'        => 'draft',
            'is_free'       => true,
            'level'         => 'beginner',
        ]);

        $data = [
            'title'   => 'Mi Curso Actualizado',
            'level'   => 'intermediate',
            'status'  => 'draft',
            'is_free' => '1',
        ];

        $this->actingAs($this->instructor)
            ->put(route('admin.courses.update', $course), $data)
            ->assertSessionDoesntHaveErrors();
    }
}
