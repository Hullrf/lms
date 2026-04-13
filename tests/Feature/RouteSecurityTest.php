<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ── Guest access ──────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_profile(): void
    {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_patch_profile(): void
    {
        $this->patch(route('profile.update'), ['name' => 'x', 'email' => 'x@x.com'])
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_certificates(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso',
            'slug'          => 'curso',
            'status'        => 'published',
            'is_free'       => true,
            'level'         => 'beginner',
        ]);

        $this->get(route('certificates.show', $course))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_checkout(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso',
            'slug'          => 'curso',
            'status'        => 'published',
            'is_free'       => false,
            'price'         => 50,
            'level'         => 'beginner',
        ]);

        $this->get(route('checkout', $course))
            ->assertRedirect(route('login'));
    }

    // ── Unverified user access ─────────────────────────────────────────────

    public function test_unverified_user_cannot_access_checkout(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $unverified = User::factory()->unverified()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso',
            'slug'          => 'curso-checkout',
            'status'        => 'published',
            'is_free'       => false,
            'price'         => 50,
            'level'         => 'beginner',
        ]);

        $this->actingAs($unverified)
            ->get(route('checkout', $course))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_access_certificates(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $unverified = User::factory()->unverified()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso',
            'slug'          => 'curso-cert',
            'status'        => 'published',
            'is_free'       => true,
            'level'         => 'beginner',
        ]);

        $this->actingAs($unverified)
            ->get(route('certificates.show', $course))
            ->assertRedirect(route('verification.notice'));
    }

    // ── Enrolled middleware ────────────────────────────────────────────────

    public function test_unenrolled_user_cannot_post_progress(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student    = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso pagado',
            'slug'          => 'curso-pagado',
            'status'        => 'published',
            'is_free'       => false,
            'price'         => 30,
            'level'         => 'beginner',
        ]);
        $module = Module::create([
            'course_id'  => $course->id,
            'title'      => 'Módulo 1',
            'sort_order' => 1,
        ]);
        $lesson = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Lección 1',
            'slug'       => 'leccion-1',
            'type'       => 'video',
            'sort_order' => 1,
        ]);

        $this->actingAs($student)
            ->postJson(route('progress.update', $lesson))
            ->assertRedirect(route('courses.show', $course->slug));
    }

    public function test_enrolled_user_can_post_progress(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student    = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso pagado',
            'slug'          => 'curso-pagado-2',
            'status'        => 'published',
            'is_free'       => false,
            'price'         => 30,
            'level'         => 'beginner',
        ]);
        $module = Module::create([
            'course_id'  => $course->id,
            'title'      => 'Módulo 1',
            'sort_order' => 1,
        ]);
        $lesson = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Lección 1',
            'slug'       => 'leccion-enrolled',
            'type'       => 'video',
            'sort_order' => 1,
        ]);
        Enrollment::create([
            'user_id'     => $student->id,
            'course_id'   => $course->id,
            'progress'    => 0,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($student)
            ->postJson(route('progress.update', $lesson))
            ->assertOk();
    }

    public function test_unenrolled_user_cannot_submit_quiz(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student    = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso pagado',
            'slug'          => 'curso-quiz',
            'status'        => 'published',
            'is_free'       => false,
            'price'         => 30,
            'level'         => 'beginner',
        ]);
        $module = Module::create([
            'course_id'  => $course->id,
            'title'      => 'Módulo 1',
            'sort_order' => 1,
        ]);
        $lesson = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Quiz lección',
            'slug'       => 'quiz-leccion',
            'type'       => 'quiz',
            'sort_order' => 1,
        ]);

        $this->actingAs($student)
            ->postJson(route('quiz.submit', $lesson))
            ->assertRedirect(route('courses.show', $course->slug));
    }

    public function test_unenrolled_user_cannot_view_lesson(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student    = User::factory()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso pagado',
            'slug'          => 'curso-lesson',
            'status'        => 'published',
            'is_free'       => false,
            'price'         => 30,
            'level'         => 'beginner',
        ]);
        $module = Module::create([
            'course_id'  => $course->id,
            'title'      => 'Módulo 1',
            'sort_order' => 1,
        ]);
        $lesson = Lesson::create([
            'module_id'  => $module->id,
            'title'      => 'Lección bloqueada',
            'slug'       => 'leccion-bloqueada',
            'type'       => 'video',
            'sort_order' => 1,
        ]);

        $this->actingAs($student)
            ->get(route('lesson.show', [$course->slug, $lesson->slug]))
            ->assertRedirect(route('courses.show', $course->slug));
    }

    public function test_unverified_user_cannot_download_certificate(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $unverified = User::factory()->unverified()->create();
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Curso',
            'slug'          => 'curso-cert-dl',
            'status'        => 'published',
            'is_free'       => true,
            'level'         => 'beginner',
        ]);

        $this->actingAs($unverified)
            ->get(route('certificates.download', $course))
            ->assertRedirect(route('verification.notice'));
    }

    // ── Public routes still accessible ────────────────────────────────────

    public function test_public_home_is_accessible_to_guest(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_public_courses_index_is_accessible_to_guest(): void
    {
        $this->get(route('courses.index'))->assertOk();
    }
}
