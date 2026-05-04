<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicLevel;
use App\Models\Domain;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $domain;
    protected $theme;
    protected $level;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup RBAC
        $manageQuestions = Permission::create(['name' => 'manage-questions', 'label' => 'Gérer les questions']);
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Admin']);
        $adminRole->permissions()->attach($manageQuestions);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Setup dependencies
        $this->domain = Domain::create(['name' => 'Dev', 'slug' => 'dev', 'color' => '#000000']);
        $this->theme = Theme::create([
            'domain_id' => $this->domain->id,
            'name' => 'PHP',
            'slug' => 'php'
        ]);
        $this->level = AcademicLevel::create(['name' => 'B3', 'order' => 1]);
    }

    /**
     * Test creating a simple MCQ question.
     */
    public function test_admin_can_create_mcq_question(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.questions.store'), [
            'type' => 'mcq',
            'domain_ids' => [$this->domain->id],
            'academic_level_id' => $this->level->id,
            'theme_ids' => [$this->theme->id],
            'difficulty' => 'easy',
            'statement' => 'What is PHP?',
            'points' => 5,
            'choices' => [
                ['text' => 'A language', 'is_correct' => true],
                ['text' => 'A fruit', 'is_correct' => false],
            ],
        ]);

        $response->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseHas('questions', [
            'statement' => 'What is PHP?',
            'type' => 'mcq'
        ]);
    }

    /**
     * Test creating a question with images.
     */
    public function test_admin_can_create_question_with_images(): void
    {
        Storage::fake('public');

        $questionImage = UploadedFile::fake()->image('question.jpg');
        $choiceImage = UploadedFile::fake()->image('choice.jpg');

        $response = $this->actingAs($this->admin)->post(route('admin.questions.store'), [
            'type' => 'mcq',
            'domain_ids' => [$this->domain->id],
            'academic_level_id' => $this->level->id,
            'theme_ids' => [$this->theme->id],
            'difficulty' => 'medium',
            'statement' => 'Identify this logo',
            'points' => 10,
            'image' => $questionImage,
            'choices' => [
                ['text' => 'Option A', 'is_correct' => true, 'image' => $choiceImage],
                ['text' => 'Option B', 'is_correct' => false],
            ],
        ]);

        $response->assertRedirect();
        
        // Verify storage
        Storage::disk('public')->assertExists('questions/' . $questionImage->hashName());
        Storage::disk('public')->assertExists('choices/' . $choiceImage->hashName());
    }

    /**
     * Test creating a code question with unit tests.
     */
    public function test_admin_can_create_code_question(): void
    {
        $unitTests = "assert addition(1, 2) == 3";
        
        $response = $this->actingAs($this->admin)->post(route('admin.questions.store'), [
            'type' => 'code',
            'domain_ids' => [$this->domain->id],
            'academic_level_id' => $this->level->id,
            'theme_ids' => [$this->theme->id],
            'difficulty' => 'hard',
            'statement' => 'Write a function that adds two numbers.',
            'points' => 20,
            'default_language' => 'python',
            'initial_code' => 'def addition(a, b):',
            'unit_tests' => $unitTests,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('questions', [
            'type' => 'code',
            'unit_tests' => $unitTests
        ]);
    }

    /**
     * Test validation.
     */
    public function test_question_creation_requires_mandatory_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.questions.store'), []);

        $response->assertSessionHasErrors([
            'type', 'domain_ids', 'academic_level_id', 'theme_ids', 'difficulty', 'statement', 'points'
        ]);
    }

    /**
     * Test unauthorized access.
     */
    public function test_non_admin_cannot_create_question(): void
    {
        $user = User::factory()->create();
        // User has no role/permission

        $response = $this->actingAs($user)->post(route('admin.questions.store'), [
            'statement' => 'Illegal question'
        ]);

        $response->assertForbidden();
    }
}
