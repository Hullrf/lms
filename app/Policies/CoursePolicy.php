<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, Course $course): bool
    {
        // Todos los instructores pueden ver cualquier curso
        return $user->isAdmin() || $user->isInstructor();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, Course $course): bool
    {
        // Dueño, colaboradores y admins pueden editar
        return $course->isEditableBy($user);
    }

    public function delete(User $user, Course $course): bool
    {
        // Solo el dueño y los admins pueden eliminar
        return $user->isAdmin() || $course->instructor_id === $user->id;
    }
}
