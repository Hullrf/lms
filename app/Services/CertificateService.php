<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Str;

class CertificateService
{
    public function issueIfCompleted(User $user, Course $course): ?Certificate
    {
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment || $enrollment->progress !== 100) {
            return null;
        }

        return Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['code' => Str::uuid(), 'issued_at' => now()]
        );
    }
}
