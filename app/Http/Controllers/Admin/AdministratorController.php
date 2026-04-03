<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdministratorController extends Controller
{
    /**
     * Display a listing of the administrators.
     */
    public function index()
    {
        $administrators = User::with('roles')->get();

        return Inertia::render('Admin/Administrators/Index', [
            'administrators' => $administrators,
            'roles' => Role::all(),
        ]);
    }

    /**
     * Store a newly created administrator.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|lowercase|max:255|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->roles()->sync($request->roles);

        return redirect()->back()->with('success', 'Administrateur ajouté avec succès.');
    }

    /**
     * Update the specified administrator.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'lowercase', 'max:255', Rule::unique('users')->ignore($user->id)],
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->update($request->only('name', 'email'));

        if ($request->has('password') && $request->password) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->roles()->sync($request->roles);

        return redirect()->back()->with('success', 'Administrateur mis à jour.');
    }

    /**
     * Remove the specified administrator.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Administrateur supprimé.');
    }
}
