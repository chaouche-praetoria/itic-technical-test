<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all(),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'label' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => str($request->name)->slug()->value(),
            'label' => $request->label,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->back()->with('success', 'Rôle créé avec succès.');
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        // Protection against modifying super-admin
        if ($role->name === 'super-admin') {
            return redirect()->back()->with('error', 'Le rôle Super Admin ne peut pas être modifié.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'label' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => str($request->name)->slug()->value(),
            'label' => $request->label,
        ]);

        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->back()->with('success', 'Rôle mis à jour.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Protection
        if ($role->name === 'super-admin') {
            return redirect()->back()->with('error', 'Le rôle Super Admin ne peut pas être supprimé.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', 'Ce rôle est assigné à des utilisateurs et ne peut pas être supprimé.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Rôle supprimé.');
    }
}
