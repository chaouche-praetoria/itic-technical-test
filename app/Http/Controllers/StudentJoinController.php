<?php

namespace App\Http\Controllers;

use App\Models\ClassInvitation;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StudentJoinController extends Controller
{
    /**
     * Show the join form. The {token} is either a classroom join_token
     * (QR code / shared link) or a per-email invitation token.
     */
    public function show(string $token)
    {
        [$classroom, $invitation] = $this->resolve($token);

        $classroom->loadMissing('academicLevel', 'teacher');

        return Inertia::render('Class/Join', [
            'token' => $token,
            'classroom' => [
                'name' => $classroom->name,
                'description' => $classroom->description,
                'level' => $classroom->academicLevel?->name,
                'teacher' => $classroom->teacher?->name,
            ],
            'prefillEmail' => $invitation?->email,
        ]);
    }

    public function store(Request $request, string $token)
    {
        [$classroom, $invitation] = $this->resolve($token);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $student = $classroom->students()->updateOrCreate(
            ['email' => $validated['email']],
            $validated,
        );

        if ($invitation && $invitation->status !== 'accepted') {
            $invitation->update(['status' => 'accepted', 'accepted_at' => now()]);
        }

        // Ensure an attempt exists for each published evaluation of the class
        $attempts = [];
        foreach ($classroom->evaluations()->where('is_published', true)->with('questions')->get() as $evaluation) {
            $attempt = $this->ensureAttempt($evaluation, $student);
            $attempts[] = [
                'token' => $attempt->token,
                'title' => $evaluation->title,
                'subject' => $evaluation->subject,
                'time_limit_minutes' => $evaluation->time_limit_minutes,
                'questions_count' => $evaluation->questions->count(),
                'status' => $attempt->status,
            ];
        }

        return Inertia::render('Class/Joined', [
            'classroom' => ['name' => $classroom->name],
            'student' => ['name' => $student->full_name],
            'evaluations' => $attempts,
        ]);
    }

    private function ensureAttempt(Evaluation $evaluation, Student $student)
    {
        return $evaluation->attempts()->firstOrCreate(
            ['student_id' => $student->id],
            ['token' => Str::random(40), 'status' => 'pending'],
        );
    }

    /**
     * @return array{0: ClassRoom, 1: ?ClassInvitation}
     */
    private function resolve(string $token): array
    {
        $classroom = ClassRoom::where('join_token', $token)->where('is_active', true)->first();
        if ($classroom) {
            return [$classroom, null];
        }

        $invitation = ClassInvitation::where('token', $token)->firstOrFail();

        return [$invitation->classroom, $invitation];
    }
}
