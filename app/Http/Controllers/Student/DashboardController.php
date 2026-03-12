<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $enrollments = $request->user()
            ->enrollments()
            ->with('course.modules')
            ->latest('enrolled_at')
            ->get();

        return view('dashboard', compact('enrollments'));
    }
}