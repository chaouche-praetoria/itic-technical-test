<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AcademicLevelController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-levels');

        return Inertia::render('Admin/AcademicLevels/Index', [
            'levels' => AcademicLevel::with(['fallbackLevel'])
                ->withCount(['questions', 'testTemplates'])
                ->orderBy('order')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-levels');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'order' => 'nullable|integer|min:0',
            'fallback_level_id' => 'nullable|exists:academic_levels,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        AcademicLevel::create($validated);

        return back()->with('success', 'Niveau créé.');
    }

    public function update(Request $request, AcademicLevel $level)
    {
        Gate::authorize('manage-levels');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'order' => 'nullable|integer|min:0',
            'fallback_level_id' => 'nullable|exists:academic_levels,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        $level->update($validated);

        return back()->with('success', 'Niveau mis à jour.');
    }

    public function destroy(AcademicLevel $level)
    {
        Gate::authorize('manage-levels');

        if ($level->questions()->count() > 0 || $level->testTemplates()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer ce niveau car il est utilisé par des questions ou des templates.');
        }

        $level->delete();
        
        return back()->with('success', 'Niveau supprimé.');
    }
}
