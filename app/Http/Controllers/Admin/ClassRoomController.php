<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ClassInvitationMail;
use App\Models\AcademicLevel;
use App\Models\ClassInvitation;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ClassRoomController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-classes');

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $this->scopedQuery()
                ->with(['academicLevel', 'teacher'])
                ->withCount(['students', 'evaluations'])
                ->latest()
                ->paginate(20),
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function create()
    {
        Gate::authorize('manage-classes');

        return Inertia::render('Admin/Classes/Form', [
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-classes');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'academic_level_id' => 'required|exists:academic_levels,id',
        ]);

        $validated['user_id'] = $request->user()->id;

        ClassRoom::create($validated);

        return redirect()->route('admin.classes.index')->with('success', 'Classe créée avec succès.');
    }

    public function show(ClassRoom $class)
    {
        Gate::authorize('manage-classes');
        $this->authorizeOwnership($class);

        return Inertia::render('Admin/Classes/Show', [
            'classroom' => $class->load(['academicLevel', 'teacher', 'students', 'invitations']),
            'joinUrl' => $class->joinUrl(),
            'evaluations' => $class->evaluations()->withCount('questions')->latest()->get(),
        ]);
    }

    public function edit(ClassRoom $class)
    {
        Gate::authorize('manage-classes');
        $this->authorizeOwnership($class);

        return Inertia::render('Admin/Classes/Form', [
            'classroom' => $class,
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function update(Request $request, ClassRoom $class)
    {
        Gate::authorize('manage-classes');
        $this->authorizeOwnership($class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'academic_level_id' => 'required|exists:academic_levels,id',
            'is_active' => 'boolean',
        ]);

        $class->update($validated);

        return redirect()->route('admin.classes.show', $class)->with('success', 'Classe mise à jour.');
    }

    public function destroy(ClassRoom $class)
    {
        Gate::authorize('manage-classes');
        $this->authorizeOwnership($class);

        $class->delete();

        return redirect()->route('admin.classes.index')->with('success', 'Classe supprimée.');
    }

    /**
     * Send email invitations to a list of addresses.
     */
    public function invite(Request $request, ClassRoom $class)
    {
        Gate::authorize('manage-classes');
        $this->authorizeOwnership($class);

        $validated = $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'email',
        ]);

        foreach ($validated['emails'] as $email) {
            $invitation = $class->invitations()->firstOrCreate(
                ['email' => $email, 'status' => 'pending'],
            );

            Mail::to($email)->send(new ClassInvitationMail($invitation));
        }

        return back()->with('success', 'Invitations envoyées.');
    }

    public function removeStudent(ClassRoom $class, Student $student)
    {
        Gate::authorize('manage-classes');
        $this->authorizeOwnership($class);

        abort_unless($student->classroom_id === $class->id, 404);

        $student->delete();

        return back()->with('success', 'Étudiant retiré de la classe.');
    }

    /**
     * Classes are owned by their teacher; super-admins/admins see everything.
     */
    private function scopedQuery()
    {
        $user = request()->user();
        $query = ClassRoom::query();

        if (! $user->isSuperAdmin() && ! $user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    private function authorizeOwnership(ClassRoom $class): void
    {
        $user = request()->user();

        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return;
        }

        abort_unless($class->user_id === $user->id, 403);
    }
}
