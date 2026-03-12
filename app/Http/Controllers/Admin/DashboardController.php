<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Order;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_courses'     => Course::count(),
            'total_students'    => User::where('role', 'student')->count(),
            'total_enrollments' => Enrollment::count(),
            'total_revenue'     => Order::where('status', 'paid')->sum('amount'),
        ];

        $recentEnrollments = Enrollment::with('user', 'course')
                                       ->latest('enrolled_at')
                                       ->take(10)
                                       ->get();

        return view('admin.dashboard', compact('stats', 'recentEnrollments'));
    }
}