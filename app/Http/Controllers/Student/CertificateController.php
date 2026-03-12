<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $course = Course::where('slug', $slug)->firstOrFail();
        $user   = $request->user();

        $enrollment = $user->enrollments()
                           ->where('course_id', $course->id)
                           ->where('progress', 100)
                           ->first();

        if (!$enrollment) {
            return redirect()->route('courses.show', $course->slug)
                             ->with('error', 'Debes completar el curso para obtener el certificado.');
        }

        $certificate = Certificate::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['code' => Str::uuid(), 'issued_at' => now()]
        );

        return view('student.certificate', compact('certificate', 'course', 'user'));
    }

    public function download(Request $request, string $slug)
    {
        $course      = Course::where('slug', $slug)->firstOrFail();
        $user        = $request->user();

        $certificate = Certificate::where('user_id', $user->id)
                                  ->where('course_id', $course->id)
                                  ->firstOrFail();

        $pdf = Pdf::loadView('student.certificate_pdf', compact('certificate', 'course', 'user'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download('certificado-'.$course->slug.'.pdf');
    }
}