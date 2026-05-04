<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\Domain;
use App\Models\TestTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TestTemplateController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-templates');

        return Inertia::render('Admin/Templates/Index', [
            'templates' => TestTemplate::with(['domain', 'academicLevel'])
                ->withCount('testSessions')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create()
    {
        Gate::authorize('manage-templates');

        return Inertia::render('Admin/Templates/Form', [
            'domains' => Domain::with('themes')->where('is_active', true)->get(),
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-templates');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domain_id' => 'required|exists:domains,id',
            'academic_level_id' => 'required|exists:academic_levels,id',
            'duration_minutes' => 'required|integer|min:5',
            'question_timer' => 'boolean',
            'question_time_seconds' => 'nullable|integer|min:10',
            'single_attempt' => 'boolean',
            'link_expiry_hours' => 'required|integer|min:1',
            'rules' => 'required|array|min:1',
            'rules.*.theme_id' => 'required|exists:themes,id',
            'rules.*.question_type' => 'required|in:mcq,text,code',
            'rules.*.difficulty' => 'nullable|in:easy,medium,hard',
            'rules.*.count' => 'required|integer|min:1',
        ]);

        $template = TestTemplate::create($validated);

        foreach ($request->rules as $rule) {
            $template->rules()->create($rule);
        }

        return redirect()->route('admin.templates.index')->with('success', 'Template créé avec succès.');
    }

    public function edit(TestTemplate $template)
    {
        Gate::authorize('manage-templates');

        return Inertia::render('Admin/Templates/Form', [
            'template' => $template->load('rules.theme'),
            'domains' => Domain::with('themes')->where('is_active', true)->get(),
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function update(Request $request, TestTemplate $template)
    {
        Gate::authorize('manage-templates');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domain_id' => 'required|exists:domains,id',
            'academic_level_id' => 'required|exists:academic_levels,id',
            'duration_minutes' => 'required|integer|min:5',
            'question_timer' => 'boolean',
            'question_time_seconds' => 'nullable|integer|min:10',
            'single_attempt' => 'boolean',
            'link_expiry_hours' => 'required|integer|min:1',
            'rules' => 'required|array|min:1',
            'rules.*.theme_id' => 'required|exists:themes,id',
            'rules.*.question_type' => 'required|in:mcq,text,code',
            'rules.*.difficulty' => 'nullable|in:easy,medium,hard',
            'rules.*.count' => 'required|integer|min:1',
        ]);

        $template->update($validated);
        $template->rules()->delete();

        foreach ($request->rules as $rule) {
            $template->rules()->create($rule);
        }

        return redirect()->route('admin.templates.index')->with('success', 'Template mis à jour.');
    }

    public function destroy(TestTemplate $template)
    {
        Gate::authorize('manage-templates');

        $template->delete();
        return redirect()->route('admin.templates.index')->with('success', 'Template supprimé.');
    }
}
