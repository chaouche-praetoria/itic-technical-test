<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Domain;
use App\Models\Question;
use App\Models\TestSession;
use App\Models\TestTemplate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_candidates' => Candidate::count(),
            'total_questions' => Question::count(),
            'total_templates' => TestTemplate::count(),
            'total_sessions' => TestSession::count(),
            'completed_sessions' => TestSession::where('status', 'completed')->count(),
            'avg_score' => round(TestSession::where('status', 'completed')->avg('score') ?? 0, 2),
        ];

        $recentSessions = TestSession::with(['candidate', 'template'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'candidate_id' => $s->candidate_id,
                'candidate' => $s->candidate->full_name,
                'template' => $s->template->name,
                'status' => $s->status,
                'score' => $s->score,
                'completed_at' => $s->completed_at?->toDateTimeString(),
            ]);

        $scoresByDomain = DB::table('test_sessions')
            ->join('test_templates', 'test_sessions.test_template_id', '=', 'test_templates.id')
            ->join('domains', 'test_templates.domain_id', '=', 'domains.id')
            ->where('test_sessions.status', 'completed')
            ->select('domains.name', DB::raw('AVG(test_sessions.score) as avg_score'), DB::raw('COUNT(*) as total'))
            ->groupBy('domains.id', 'domains.name')
            ->get();

        $scoresByLevel = DB::table('test_sessions')
            ->join('test_templates', 'test_sessions.test_template_id', '=', 'test_templates.id')
            ->join('academic_levels', 'test_templates.academic_level_id', '=', 'academic_levels.id')
            ->where('test_sessions.status', 'completed')
            ->select('academic_levels.name', DB::raw('AVG(test_sessions.score) as avg_score'), DB::raw('COUNT(*) as total'))
            ->groupBy('academic_levels.id', 'academic_levels.name')
            ->get();

        return Inertia::render('Admin/Dashboard', compact('stats', 'recentSessions', 'scoresByDomain', 'scoresByLevel'));
    }
}
