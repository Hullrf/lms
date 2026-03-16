<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = \App\Models\User::latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function destroy(\App\Models\User $user)
    {
        $user->delete();
        return back()->with('success', 'Usuario eliminado.');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, \App\Models\User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }

        $request->validate([
            'role' => 'required|in:admin,instructor,student',
        ]);

        $user->update(['role' => $request->role]);

        return back()->with('success', "Rol de {$user->name} actualizado a {$request->role}.");
    }

    /**
     * Remove the specified resource from storage.
     */
}
