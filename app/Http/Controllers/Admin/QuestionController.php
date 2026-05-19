<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\Domain;
use App\Models\Question;
use App\Models\Theme;
use App\Services\Judge0Service;
use App\Imports\QuestionsImport;
use App\Exports\QuestionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function __construct(private Judge0Service $judge0) {}

    public function index(Request $request)
    {
        Gate::authorize('manage-questions');

        $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? (int) $request->per_page : 20;

        $query = Question::with(['domains', 'academicLevel', 'themes', 'choices'])
            ->when($request->search, fn($q) => $q->where('statement', 'like', "%{$request->search}%"))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->domain_id, fn($q) => $q->whereHas('domains', fn($dq) => $dq->where('domains.id', $request->domain_id)))
            ->when($request->theme_id, fn($q) => $q->whereHas('themes', fn($tq) => $tq->where('themes.id', $request->theme_id)))
            ->when($request->academic_level_id, fn($q) => $q->where('academic_level_id', $request->academic_level_id))
            ->when($request->difficulty, fn($q) => $q->where('difficulty', $request->difficulty))
            ->latest();

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $query->paginate($perPage)->withQueryString(),
            'domains' => Domain::where('is_active', true)->get(),
            'themes' => Theme::orderBy('name')->get(),
            'academicLevels' => AcademicLevel::orderBy('order')->get(),
            'filters' => $request->only(['search', 'type', 'domain_id', 'theme_id', 'academic_level_id', 'difficulty', 'per_page']),
        ]);
    }

    public function create()
    {
        Gate::authorize('manage-questions');

        return Inertia::render('Admin/Questions/Form', [
            'domains' => Domain::with('themes')->where('is_active', true)->get(),
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-questions');

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
            'initial_code' => 'nullable|string',
            'default_language' => 'nullable|string',
            'choices' => [
                'exclude_unless:type,mcq',
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $texts = collect($value)
                        ->map(fn($c) => trim($c['text'] ?? ''))
                        ->filter()
                        ->map(fn($t) => strtolower($t));
                    
                    if ($texts->count() !== $texts->unique()->count()) {
                        $fail('Les options de réponse ne peuvent pas être identiques.');
                    }
                }
            ],
            'choices.*.text' => 'nullable|string',
            'choices.*.is_correct' => 'required|boolean',
            'choices.*.image' => 'nullable|image|max:2048',
            'explanation' => 'nullable|string',
            'points' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('questions', 'public');
        }

        $question = Question::create($validated);
        $question->domains()->sync($request->domain_ids);
        $question->themes()->sync($request->theme_ids);

        if ($request->type === 'mcq' && $request->choices) {
            foreach ($request->choices as $i => $choice) {
                $choiceData = [
                    'text' => $choice['text'] ?? null,
                    'is_correct' => $choice['is_correct'],
                    'order' => $i
                ];

                if ($request->hasFile("choices.$i.image")) {
                    $choiceData['image_path'] = $request->file("choices")[$i]['image']->store('choices', 'public');
                }

                $question->choices()->create($choiceData);
            }
        }

        return redirect()->route('admin.questions.index')->with('success', 'Question créée avec succès.');
    }

    public function edit(Question $question)
    {
        Gate::authorize('manage-questions');

        return Inertia::render('Admin/Questions/Form', [
            'question' => $question->load(['choices', 'domains', 'themes']),
            'domains' => Domain::with('themes')->where('is_active', true)->get(),
            'levels' => AcademicLevel::orderBy('order')->get(),
        ]);
    }

    public function update(Request $request, Question $question)
    {
        Gate::authorize('manage-questions');

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
            'initial_code' => 'nullable|string',
            'default_language' => 'nullable|string',
            'choices' => [
                'exclude_unless:type,mcq',
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $texts = collect($value)
                        ->map(fn($c) => trim($c['text'] ?? ''))
                        ->filter()
                        ->map(fn($t) => strtolower($t));
                    
                    if ($texts->count() !== $texts->unique()->count()) {
                        $fail('Les options de réponse ne peuvent pas être identiques.');
                    }
                }
            ],
            'choices.*.text' => 'nullable|string',
            'choices.*.is_correct' => 'required|boolean',
            'choices.*.image' => 'nullable|image|max:2048',
            'choices.*.image_path' => 'nullable|string',
            'choices.*.id' => [
                'nullable',
                Rule::exists('question_choices', 'id')->where('question_id', $question->id)
            ],
            'explanation' => 'nullable|string',
            'points' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($question->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($question->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('questions', 'public');
        }

        $question->update($validated);
        $question->domains()->sync($request->domain_ids);
        $question->themes()->sync($request->theme_ids);
        

        if ($validated['type'] === 'mcq') {
            $incomingIds = collect($validated['choices'])->pluck('id')->filter()->all();
            
            // Delete choices that are not in the request
            $question->choices()->whereNotIn('id', $incomingIds)->delete();

            foreach ($validated['choices'] as $i => $choice) {
                $choiceData = [
                    'text' => $choice['text'] ?? null,
                    'is_correct' => $choice['is_correct'],
                    'order' => $i
                ];

                if ($request->hasFile("choices.$i.image")) {
                    $choiceData['image_path'] = $request->file("choices")[$i]['image']->store('choices', 'public');
                } elseif (!empty($choice['image_path'])) {
                    $choiceData['image_path'] = $choice['image_path'];
                }

                if (!empty($choice['id'])) {
                    $question->choices()->where('id', $choice['id'])->update($choiceData);
                } else {
                    $question->choices()->create($choiceData);
                }
            }
        } else {
            $question->choices()->delete();
        }

        return redirect()->route('admin.questions.index')->with('success', 'Question mise à jour.');
    }

    public function destroy(Question $question)
    {
        Gate::authorize('manage-questions');

        $question->delete();
        return back()->with('success', 'Question supprimée.');
    }

    public function test(Request $request)
    {
        Gate::authorize('manage-questions');

        $request->validate([
            'code' => 'required|string',
            'language' => 'required|string',
            'unit_tests' => 'required|string',
        ]);

        $result = $this->judge0->execute($request->code, $request->language, $request->unit_tests);

        return response()->json($result);
    }

    public function import(Request $request)
    {
        Gate::authorize('manage-questions');

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            Excel::import(new QuestionsImport, $request->file('file'));
            return redirect()->route('admin.questions.index')->with('success', 'Importation terminée avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de l\'importation : ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        Gate::authorize('manage-questions');

        $query = Question::with(['domains', 'academicLevel', 'themes', 'choices'])
            ->when($request->search, fn($q) => $q->where('statement', 'like', "%{$request->search}%"))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->domain_id, fn($q) => $q->whereHas('domains', fn($dq) => $dq->where('domains.id', $request->domain_id)))
            ->when($request->theme_id, fn($q) => $q->whereHas('themes', fn($tq) => $tq->where('themes.id', $request->theme_id)))
            ->when($request->academic_level_id, fn($q) => $q->where('academic_level_id', $request->academic_level_id))
            ->when($request->difficulty, fn($q) => $q->where('difficulty', $request->difficulty))
            ->latest();

        return Excel::download(new QuestionsExport($query->get()), 'questions_export_' . now()->format('Y-m-d_H-i') . '.xlsx');
    }

    public function bulkDestroy(Request $request)
    {
        Gate::authorize('manage-questions');

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:questions,id',
        ]);

        Question::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', count($validated['ids']) . ' questions supprimées.');
    }

    public function downloadTemplate()
    {
        Gate::authorize('manage-questions');

        $headers = [
            'domaine', 'niveau', 'themes', 'difficulte', 'enonce', 'points', 'explication', 'reponse_multiple',
            'choix_1', 'correct_1', 'choix_2', 'correct_2', 'choix_3', 'correct_3', 'choix_4', 'correct_4', 'choix_5', 'correct_5', 'choix_6', 'correct_6'
        ];

        $example = [
            'Informatique', 'Bachelor', 'PHP, Laravel', 'medium', 'Quelle est la fonction pour afficher du texte en PHP ?', '2', 'La fonction echo est la plus commune.', '0',
            'echo', '1', 'print_text', '0', 'display', '0', 'show', '0', '', '0', '', '0'
        ];

        $callback = function() use ($headers, $example) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, $example);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=template_questions_qcm.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }
}
