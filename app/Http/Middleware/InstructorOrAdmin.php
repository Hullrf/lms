<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InstructorOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || (!auth()->user()->isAdmin() && !auth()->user()->isInstructor())) {
            abort(403, 'Acceso no autorizado.');
        }
        return $next($request);
    }
}
