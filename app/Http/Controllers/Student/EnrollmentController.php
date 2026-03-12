<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $course = Course::published()->where('slug', $slug)->firstOrFail();
        $user   = $request->user();

        if ($user->isEnrolledIn($course)) {
            return redirect()->route('lesson.show', [
                $course->slug,
                $course->modules->first()?->lessons->first()?->slug ?? ''
            ]);
        }

        // Si el curso no es gratuito, redirigir a checkout
        if (!$course->isFree()) {
            return redirect()->route('checkout', $course->slug);
        }

        Enrollment::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'enrolled_at' => now(),
        ]);

        return redirect()->route('lesson.show', [
            $course->slug,
            $course->modules->first()?->lessons->first()?->slug ?? ''
        ])->with('success', '¡Matriculado exitosamente!');
    }
}