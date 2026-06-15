<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'Super Administrateur']);
        $admin = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrateur']);
        $teacher = Role::firstOrCreate(['name' => 'teacher'], ['label' => 'Professeur']);

        // Create Permissions
        $permissions = [
            'manage-admins' => 'Gérer les administrateurs',
            'manage-roles' => 'Gérer les rôles et permissions',
            'manage-domains' => 'Gérer les domaines',
            'manage-themes' => 'Gérer les thèmes',
            'manage-levels' => 'Gérer les niveaux académiques',
            'manage-questions' => 'Gérer la banque de questions',
            'manage-templates' => 'Gérer les templates de test',
            'manage-candidates' => 'Gérer les candidats',
            'view-results' => 'Voir les résultats des tests',
            'grade-sessions' => 'Évaluer les sessions',
            'manage-classes' => 'Gérer les classes et les étudiants',
            'manage-evaluations' => 'Gérer les évaluations',
            'grade-evaluations' => 'Corriger les tentatives d\'évaluation',
        ];

        $allPermissions = [];
        foreach ($permissions as $name => $label) {
            $allPermissions[] = Permission::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        // Give all permissions to admin role
        $admin->permissions()->sync(collect($allPermissions)->pluck('id'));

        // Teacher role: only class & evaluation related permissions
        $teacherPermissionNames = ['manage-classes', 'manage-evaluations', 'grade-evaluations'];
        $teacher->permissions()->sync(
            collect($allPermissions)
                ->filter(fn ($p) => in_array($p->name, $teacherPermissionNames))
                ->pluck('id')
        );

        // Give all permissions to Super Admin (implicitly or explicitly)
        // In this implementation, super-admin logic is in HasRoles trait
        
        // Assign Super Admin to the current/first user
        $user = User::first();
        if ($user) {
            $user->assignRole('super-admin');
        }
    }
}
