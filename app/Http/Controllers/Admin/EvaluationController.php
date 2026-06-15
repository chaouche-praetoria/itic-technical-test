<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EvaluationInvitationMail;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\EvaluationAttempt;
use App\Models\Student;
use App\Services\EvaluationScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EvaluationController extends Controller
{
    public function __construct(
        private EvaluationScoringService $scoring,
    ) {}

    public function index()
    {
        Gate::authorize('manage-evaluations');

        return Inertia::render('Admin/Evaluations/Index', [
            'evaluations' => $this->scopedQuery()
                ->with(['classroom.academicLevel'])
                ->withCount(['questions', 'attempts'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create()
    {
        Gate::authorize('manage-evaluations');

        return Inertia::render('Admin/Evaluations/Form', [
            'classes' => $this->classOptions(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-evaluations');

        $validated = $this->validatePayload($request);

        $evaluation = Evaluation::create([
            'user_id' => $request->user()->id,
            'classroom_id' => $validated['classroom_id'],
            'title' => $validated['title'],
            'subject' => $validated['subject'] ?? null,
            'statement' => $validated['statement'],
            'time_limit_minutes' => $validated['time_limit_minutes'],
            'available_until' => $validated['available_until'] ?? null,
            ...$this->attachmentData($request),
        ]);

        $this->syncQuestions($evaluation, $request->input('questions', []));

        return redirect()->route('admin.evaluations.index')->with('success', 'Évaluation créée avec succès.');
    }

    public function edit(Evaluation $evaluation)
    {
        Gate::authorize('manage-evaluations');
        $this->authorizeOwnership($evaluation);

        return Inertia::render('Admin/Evaluations/Form', [
            'evaluation' => $evaluation->load('questions.choices'),
            'classes' => $this->classOptions(),
        ]);
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        Gate::authorize('manage-evaluations');
        $this->authorizeOwnership($evaluation);

        $validated = $this->validatePayload($request);

        $evaluation->update([
            'classroom_id' => $validated['classroom_id'],
            'title' => $validated['title'],
            'subject' => $validated['subject'] ?? null,
            'statement' => $validated['statement'],
            'time_limit_minutes' => $validated['time_limit_minutes'],
            'available_until' => $validated['available_until'] ?? null,
            ...$this->attachmentData($request, $evaluation),
        ]);

        // Replace questions wholesale (simple and predictable for an exam editor)
        $evaluation->questions()->delete();
        $this->syncQuestions($evaluation, $request->input('questions', []));

        return redirect()->route('admin.evaluations.index')->with('success', 'Évaluation mise à jour.');
    }

    public function destroy(Evaluation $evaluation)
    {
        Gate::authorize('manage-evaluations');
        $this->authorizeOwnership($evaluation);

        if ($evaluation->attachment_path) {
            Storage::disk('public')->delete($evaluation->attachment_path);
        }

        $evaluation->delete();

        return redirect()->route('admin.evaluations.index')->with('success', 'Évaluation supprimée.');
    }

    public function publish(Evaluation $evaluation)
    {
        Gate::authorize('manage-evaluations');
        $this->authorizeOwnership($evaluation);

        abort_if($evaluation->questions()->count() === 0, 422, 'Ajoutez au moins une question avant de publier.');

        $evaluation->update(['is_published' => ! $evaluation->is_published]);

        // On publish, make sure every enrolled student has a pending attempt.
        if ($evaluation->is_published) {
            foreach ($evaluation->classroom->students as $student) {
                $evaluation->attempts()->firstOrCreate(
                    ['student_id' => $student->id],
                    ['token' => Str::random(40), 'status' => 'pending'],
                );
            }
        }

        return back()->with('success', $evaluation->is_published ? 'Évaluation publiée.' : 'Évaluation dépubliée.');
    }

    public function attempts(Evaluation $evaluation)
    {
        Gate::authorize('manage-evaluations');
        $this->authorizeOwnership($evaluation);

        return Inertia::render('Admin/Evaluations/Attempts', [
            'evaluation' => $evaluation->load('questions.choices'),
            'attempts' => $evaluation->attempts()
                ->with(['student', 'answers'])
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Page to pick which students an evaluation is sent to.
     */
    public function sendForm(Evaluation $evaluation)
    {
        Gate::authorize('manage-evaluations');
        $this->authorizeOwnership($evaluation);

        $evaluation->load('classroom.academicLevel');
        $attempts = $evaluation->attempts()->get()->keyBy('student_id');

        $students = $evaluation->classroom->students()
            ->orderBy('last_name')
            ->get()
            ->map(fn (Student $s) => [
                'id' => $s->id,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'email' => $s->email,
                'invited_at' => $attempts->get($s->id)?->invited_at?->format('d/m/Y H:i'),
                'attempt_status' => $attempts->get($s->id)?->status,
            ]);

        return Inertia::render('Admin/Evaluations/Send', [
            'evaluation' => [
                'id' => $evaluation->id,
                'title' => $evaluation->title,
                'is_published' => $evaluation->is_published,
                'classroom' => [
                    'name' => $evaluation->classroom->name,
                    'level' => $evaluation->classroom->academicLevel?->name,
                ],
            ],
            'students' => $students,
        ]);
    }

    /**
     * Bulk-send the evaluation to the selected students: ensure each has an
     * attempt, email them their personal link, and stamp invited_at.
     */
    public function send(Request $request, Evaluation $evaluation)
    {
        Gate::authorize('manage-evaluations');
        $this->authorizeOwnership($evaluation);

        abort_unless($evaluation->is_published, 422, 'Publiez l\'évaluation avant de l\'envoyer.');

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
        ]);

        // Only students that actually belong to this evaluation's class.
        $students = $evaluation->classroom->students()
            ->whereIn('id', $validated['student_ids'])
            ->get();

        foreach ($students as $student) {
            $attempt = $evaluation->attempts()->firstOrCreate(
                ['student_id' => $student->id],
                ['token' => Str::random(40), 'status' => 'pending'],
            );

            Mail::to($student->email)->send(new EvaluationInvitationMail($attempt));

            $attempt->update(['invited_at' => now()]);
        }

        return back()->with('success', $students->count() . ' étudiant(s) notifié(s).');
    }

    /**
     * Generate (or fetch) the personal evaluation link for one student.
     */
    public function studentLink(Evaluation $evaluation, Student $student)
    {
        Gate::authorize('manage-evaluations');
        $this->authorizeOwnership($evaluation);

        abort_unless($student->classroom_id === $evaluation->classroom_id, 404);

        $attempt = $evaluation->attempts()->firstOrCreate(
            ['student_id' => $student->id],
            ['token' => Str::random(40), 'status' => 'pending'],
        );

        return response()->json([
            'url' => route('eval.start', $attempt->token),
        ]);
    }

    /**
     * Manually grade the (text) answers of an attempt, then recompute the score.
     */
    public function grade(Request $request, EvaluationAttempt $attempt)
    {
        Gate::authorize('grade-evaluations');
        $this->authorizeOwnership($attempt->evaluation);

        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*.answer_id' => 'required|exists:evaluation_answers,id',
            'grades.*.points_awarded' => 'required|integer|min:0',
        ]);

        foreach ($validated['grades'] as $grade) {
            $answer = $attempt->answers()->find($grade['answer_id']);
            if ($answer) {
                $answer->update(['points_awarded' => $grade['points_awarded']]);
            }
        }

        $this->scoring->scoreAttempt($attempt);

        return back()->with('success', 'Correction enregistrée.');
    }

    private function validatePayload(Request $request): array
    {
        // datetime-local sends '' when empty; normalise to null for the date rule.
        $request->merge(['available_until' => $request->input('available_until') ?: null]);

        return $request->validate([
            'classroom_id' => ['required', Rule::exists('classrooms', 'id')],
            'title' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'statement' => 'required|string',
            'time_limit_minutes' => 'required|integer|min:1',
            'available_until' => 'nullable|date',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf|max:5120',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:mcq,text',
            'questions.*.statement' => 'required|string',
            'questions.*.points' => 'required|integer|min:1',
            'questions.*.multiple_answers' => 'boolean',
            'questions.*.choices' => 'nullable|array',
            'questions.*.choices.*.text' => 'required_with:questions.*.choices|string',
            'questions.*.choices.*.is_correct' => 'nullable|boolean',
        ]);
    }

    /**
     * Build the attachment columns from an uploaded file (image or pdf).
     */
    private function attachmentData(Request $request, ?Evaluation $evaluation = null): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        if ($evaluation && $evaluation->attachment_path) {
            Storage::disk('public')->delete($evaluation->attachment_path);
        }

        $file = $request->file('attachment');
        $type = $file->getClientOriginalExtension() === 'pdf' || $file->getMimeType() === 'application/pdf'
            ? 'pdf'
            : 'image';

        return [
            'attachment_path' => $file->store('evaluations', 'public'),
            'attachment_type' => $type,
        ];
    }

    private function syncQuestions(Evaluation $evaluation, array $questions): void
    {
        foreach (array_values($questions) as $order => $q) {
            $question = $evaluation->questions()->create([
                'type' => $q['type'],
                'statement' => $q['statement'],
                'points' => $q['points'],
                'multiple_answers' => $q['multiple_answers'] ?? false,
                'order' => $order,
            ]);

            if ($q['type'] === 'mcq') {
                foreach (array_values($q['choices'] ?? []) as $cOrder => $choice) {
                    $question->choices()->create([
                        'text' => $choice['text'],
                        'is_correct' => $choice['is_correct'],
                        'order' => $cOrder,
                    ]);
                }
            }
        }
    }

    private function classOptions()
    {
        $user = request()->user();
        $query = ClassRoom::with('academicLevel')->where('is_active', true);

        if (! $user->isSuperAdmin() && ! $user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        return $query->orderBy('name')->get();
    }

    private function scopedQuery()
    {
        $user = request()->user();
        $query = Evaluation::query();

        if (! $user->isSuperAdmin() && ! $user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    private function authorizeOwnership(Evaluation $evaluation): void
    {
        $user = request()->user();

        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return;
        }

        abort_unless($evaluation->user_id === $user->id, 403);
    }
}
