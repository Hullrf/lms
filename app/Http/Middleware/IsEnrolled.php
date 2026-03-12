<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Course;

class IsEnrolled
{
    public function handle(Request $request, Closure $next)
    {
        $course = $request->route('course');

        if (is_string($course)) {
            $course = Course::where('slug', $course)->firstOrFail();
        }

        if (!$course->isFree() && !auth()->user()->isEnrolledIn($course)) {
            return redirect()->route('courses.show', $course->slug)
                             ->with('error', 'Debes matricularte para acceder a este curso.');
        }

        return $next($request);
    }
}