<?php

namespace Tests\Feature\Admin;

use App\Models\Candidate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Imports\CandidatesImport;

class CandidateTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup RBAC
        $manageCandidates = Permission::create(['name' => 'manage-candidates', 'label' => 'Gérer les candidats']);
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Admin']);
        $adminRole->permissions()->attach($manageCandidates);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /**
     * Test creating a candidate manually.
     */
    public function test_admin_can_create_candidate_manually(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.candidates.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '0601020304',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('candidates', [
            'email' => 'john.doe@example.com',
            'added_by' => 'manual',
        ]);
    }

    /**
     * Test Excel importer assigns the 'excel' source only on creation.
     */
    public function test_excel_import_sets_source_to_excel_on_creation_only(): void
    {
        $importer = new CandidatesImport();

        // 1. Create a candidate via import
        $row1 = [
            'email' => 'excel.new@example.com',
            'prenom' => 'Bob',
            'nom' => 'Smith',
            'telephone' => '0707070707',
            'formation' => 'Dev Web',
        ];

        $candidate1 = $importer->model($row1);
        $candidate1->save();

        $this->assertDatabaseHas('candidates', [
            'email' => 'excel.new@example.com',
            'added_by' => 'excel',
        ]);

        // 2. Update same candidate via import (should not alter origin)
        $row2 = [
            'email' => 'excel.new@example.com',
            'prenom' => 'Bob Updated',
            'nom' => 'Smith',
            'telephone' => '0707070707',
            'formation' => 'Dev Web Plus',
        ];

        $candidate2 = $importer->model($row2);
        $candidate2->save();

        $this->assertDatabaseHas('candidates', [
            'email' => 'excel.new@example.com',
            'first_name' => 'Bob Updated',
            'added_by' => 'excel', // preserved
        ]);
    }
}
