<?php

namespace Tests\Unit\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateServiceTest extends TestCase
{
    use RefreshDatabase;

    private CertificateService $service;
    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CertificateService();

        $instructor = User::factory()->create();
        $this->student = User::factory()->create();
        $this->course = Course::create([
            'instructor_id' => $instructor->id,
            'title'         => 'Test Course',
            'slug'          => 'test-course',
            'is_free'       => true,
        ]);
    }

    private function enroll(int $progress): Enrollment
    {
        return Enrollment::create([
            'user_id'     => $this->student->id,
            'course_id'   => $this->course->id,
            'progress'    => $progress,
            'enrolled_at' => now(),
        ]);
    }

    public function test_issues_certificate_when_progress_is_100(): void
    {
        $this->enroll(100);

        $certificate = $this->service->issueIfCompleted($this->student, $this->course);

        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertDatabaseHas('certificates', [
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_returns_null_when_progress_is_below_100(): void
    {
        $this->enroll(80);

        $result = $this->service->issueIfCompleted($this->student, $this->course);

        $this->assertNull($result);
        $this->assertDatabaseMissing('certificates', [
            'user_id'   => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    public function test_returns_null_when_not_enrolled(): void
    {
        $result = $this->service->issueIfCompleted($this->student, $this->course);

        $this->assertNull($result);
    }

    public function test_does_not_duplicate_certificate_on_repeated_calls(): void
    {
        $this->enroll(100);

        $cert1 = $this->service->issueIfCompleted($this->student, $this->course);
        $cert2 = $this->service->issueIfCompleted($this->student, $this->course);

        $this->assertEquals($cert1->id, $cert2->id);
        $this->assertDatabaseCount('certificates', 1);
    }
}
