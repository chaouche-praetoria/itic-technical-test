<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasRoles
{
    /**
     * Get all roles for the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Determine if the user has a specific role.
     */
    public function hasRole(string|array $role): bool
    {
        if (is_array($role)) {
            return collect($role)->intersect($this->roles->pluck('name'))->isNotEmpty();
        }

        return $this->roles->contains('name', $role);
    }

    /**
     * Determine if the user has a specific permission (via roles).
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles->flatMap->permissions->contains('name', $permission);
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }
}
