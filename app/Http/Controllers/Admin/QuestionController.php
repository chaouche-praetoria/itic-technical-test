<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\Domain;
use App\Models\Question;
use App\Models\Theme;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::with(['domains', 'academicLevel', 'themes', 'choices'])
            ->when($request->search, fn($q) => $q->where('statement', 'like', "%{$request->search}%"))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->domain_id, fn($q) => $q->whereHas('domains', fn($dq) => $dq->where('domains.id', $request->domain_id)))
            ->when($request->difficulty, fn($q) => $q->where('difficulty', $request->difficulty))
            ->latest();

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $query->paginate(20)->withQueryString(),
            'domains' => Domain::where('is_active', true)->get(),
            'filters' => $request->only(['search', 'type', 'domain_id', 'difficulty']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Questions/Form', [
            'domains' => Domain::with('themes')->where('is_active', true)->get(),
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:mcq,text,code',
            'domain_ids' => 'required|array|min:1',
            'domain_ids.*' => 'exists:domains,id',
            'academic_level_id' => 'required|exists:academic_levels,id',
            'theme_ids' => 'required|array|min:1',
            'theme_ids.*' => 'exists:themes,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'statement' => 'required|string',
            'multiple_answers' => 'boolean',
            'unit_tests' => 'nullable|string',
            'default_language' => 'nullable|string',
            'choices' => 'exclude_unless:type,mcq|required|array',
            'choices.*.text' => 'required|string',
            'choices.*.is_correct' => 'required|boolean',
        ]);

        $question = Question::create($validated);
        $question->domains()->sync($request->domain_ids);
        $question->themes()->sync($request->theme_ids);

        if ($request->type === 'mcq' && $request->choices) {
            foreach ($request->choices as $i => $choice) {
                $question->choices()->create([...$choice, 'order' => $i]);
            }
        }

        return redirect()->route('admin.questions.index')->with('success', 'Question créée avec succès.');
    }

    public function edit(Question $question)
    {
        return Inertia::render('Admin/Questions/Form', [
            'question' => $question->load(['choices', 'domains', 'themes']),
            'domains' => Domain::with('themes')->where('is_active', true)->get(),
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'type' => 'required|in:mcq,text,code',
            'domain_ids' => 'required|array|min:1',
            'domain_ids.*' => 'exists:domains,id',
            'academic_level_id' => 'required|exists:academic_levels,id',
            'theme_ids' => 'required|array|min:1',
            'theme_ids.*' => 'exists:themes,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'statement' => 'required|string',
            'multiple_answers' => 'boolean',
            'unit_tests' => 'nullable|string',
            'default_language' => 'nullable|string',
            'choices' => 'exclude_unless:type,mcq|required|array',
            'choices.*.text' => 'required|string',
            'choices.*.is_correct' => 'required|boolean',
        ]);

        $question->update($validated);
        $question->domains()->sync($request->domain_ids);
        $question->themes()->sync($request->theme_ids);
        

        if ($question->type === 'mcq') {
            $question->choices()->delete();
            foreach ($request->choices ?? [] as $i => $choice) {
                $question->choices()->create([...$choice, 'order' => $i]);
            }
        } else {
            $question->choices()->delete();
        }

        return redirect()->route('admin.questions.index')->with('success', 'Question mise à jour.');
    }

    public function destroy(Question $question)
    {
        $question->delete();
        return redirect()->route('admin.questions.index')->with('success', 'Question supprimée.');
    }
}
