<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Mail\AdminCredentialsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

        Mail::to($user->email)->send(new AdminCredentialsMail($user, $request->password));

        return redirect()->back()->with('success', 'Administrateur ajouté avec succès et accès envoyés par mail.');
    }

    /**
     * Update the specified administrator.
     */
    public function update(Request $request, User $administrator)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'lowercase', 'max:255', Rule::unique('users')->ignore($administrator->id)],
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $administrator->update($request->only('name', 'email'));

        if ($request->has('password') && $request->password) {
            $administrator->update(['password' => Hash::make($request->password)]);
        }

        $administrator->roles()->sync($request->roles);

        return redirect()->back()->with('success', 'Administrateur mis à jour.');
    }

    /**
     * Remove the specified administrator.
     */
    public function destroy(User $administrator)
    {
        // Prevent deleting yourself
        if (auth()->id() === $administrator->id) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $administrator->delete();

        return redirect()->back()->with('success', 'Administrateur supprimé.');
    }

    /**
     * Validate a new registration (assign default role).
     */
    public function validateUser(Request $request, User $user)
    {
        $defaultRole = Role::where('name', 'admin')->first();
        if ($defaultRole) {
            $user->roles()->sync([$defaultRole->id]);
        }

        return redirect()->back()->with('success', 'Utilisateur validé avec succès.');
    }
}
