<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CollaboratorController extends Controller
{
    public function store(Request $request, Course $course)
    {
        // Solo el dueño del curso puede añadir colaboradores
        if (auth()->id() !== $course->instructor_id && !auth()->user()->isAdmin()) {
            abort(403, 'Solo el dueño del curso puede gestionar colaboradores.');
        }

        $request->validate(['email' => 'required|email|exists:users,email']);

        $instructor = User::where('email', $request->email)
            ->where('role', 'instructor')
            ->first();

        if (!$instructor) {
            return back()->with('error', 'No se encontró ningún instructor con ese email.');
        }

        if ($instructor->id === $course->instructor_id) {
            return back()->with('error', 'Ese instructor ya es el dueño del curso.');
        }

        $course->collaborators()->syncWithoutDetaching([$instructor->id]);

        return back()->with('success', "{$instructor->name} añadido como colaborador.");
    }

    public function destroy(Course $course, User $user)
    {
        if (auth()->id() !== $course->instructor_id && !auth()->user()->isAdmin()) {
            abort(403, 'Solo el dueño del curso puede gestionar colaboradores.');
        }

        $course->collaborators()->detach($user->id);

        return back()->with('success', 'Colaborador eliminado.');
    }
}
