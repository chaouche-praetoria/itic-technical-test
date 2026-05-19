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

    /**
     * Test filtering questions by theme and academic level.
     */
    public function test_admin_can_filter_questions_by_theme_and_academic_level(): void
    {
        $theme2 = Theme::create([
            'domain_id' => $this->domain->id,
            'name' => 'JS',
            'slug' => 'js'
        ]);
        $level2 = AcademicLevel::create(['name' => 'M1', 'order' => 2]);

        // Create question A
        $qA = \App\Models\Question::create([
            'type' => 'text',
            'academic_level_id' => $this->level->id, // B3
            'difficulty' => 'easy',
            'statement' => 'Statement A',
            'points' => 5,
        ]);
        $qA->themes()->attach($this->theme->id); // PHP

        // Create question B
        $qB = \App\Models\Question::create([
            'type' => 'text',
            'academic_level_id' => $level2->id, // M1
            'difficulty' => 'easy',
            'statement' => 'Statement B',
            'points' => 5,
        ]);
        $qB->themes()->attach($theme2->id); // JS

        // Filter by theme PHP
        $response = $this->actingAs($this->admin)->get(route('admin.questions.index', [
            'theme_id' => $this->theme->id
        ]));
        $response->assertOk();
        $questions = $response->original->getData()['page']['props']['questions']['data'];
        $this->assertCount(1, $questions);
        $this->assertEquals('Statement A', $questions[0]['statement']);

        // Filter by level M1
        $response2 = $this->actingAs($this->admin)->get(route('admin.questions.index', [
            'academic_level_id' => $level2->id
        ]));
        $response2->assertOk();
        $questions2 = $response2->original->getData()['page']['props']['questions']['data'];
        $this->assertCount(1, $questions2);
        $this->assertEquals('Statement B', $questions2[0]['statement']);
    }

    /**
     * Test bulk deleting questions.
     */
    public function test_admin_can_bulk_delete_questions(): void
    {
        $q1 = \App\Models\Question::create([
            'type' => 'text',
            'academic_level_id' => $this->level->id,
            'difficulty' => 'easy',
            'statement' => 'Statement 1',
            'points' => 5,
        ]);
        $q2 = \App\Models\Question::create([
            'type' => 'text',
            'academic_level_id' => $this->level->id,
            'difficulty' => 'easy',
            'statement' => 'Statement 2',
            'points' => 5,
        ]);
        $q3 = \App\Models\Question::create([
            'type' => 'text',
            'academic_level_id' => $this->level->id,
            'difficulty' => 'easy',
            'statement' => 'Statement 3',
            'points' => 5,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.questions.bulk-destroy'), [
            'ids' => [$q1->id, $q2->id]
        ]);

        $response->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseMissing('questions', ['id' => $q1->id]);
        $this->assertDatabaseMissing('questions', ['id' => $q2->id]);
        $this->assertDatabaseHas('questions', ['id' => $q3->id]);
    }

    /**
     * Test non-admin cannot bulk delete questions.
     */
    public function test_non_admin_cannot_bulk_delete_questions(): void
    {
        $user = User::factory()->create();
        $q1 = \App\Models\Question::create([
            'type' => 'text',
            'academic_level_id' => $this->level->id,
            'difficulty' => 'easy',
            'statement' => 'Statement 1',
            'points' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('admin.questions.bulk-destroy'), [
            'ids' => [$q1->id]
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('questions', ['id' => $q1->id]);
    }
}
