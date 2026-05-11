<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ThemeController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manage-themes');

        $query = Theme::with(['domains', 'questions'])
            ->withCount('questions')
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })
            ->orderBy('name');

        return Inertia::render('Admin/Themes/Index', [
            'themes' => $query->get(),
            'domains' => Domain::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-themes');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'domain_ids' => 'nullable|array',
            'domain_ids.*' => 'exists:domains,id',
        ]);

        $theme = Theme::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        if (!empty($validated['domain_ids'])) {
            $theme->domains()->sync($validated['domain_ids']);
        }

        return back()->with('success', 'Thématique créée avec succès.');
    }

    public function update(Request $request, Theme $theme)
    {
        Gate::authorize('manage-themes');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'domain_ids' => 'nullable|array',
            'domain_ids.*' => 'exists:domains,id',
        ]);

        $theme->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        $theme->domains()->sync($validated['domain_ids'] ?? []);

        return back()->with('success', 'Thématique mise à jour.');
    }

    public function destroy(Theme $theme)
    {
        Gate::authorize('manage-themes');

        if ($theme->questions()->exists()) {
            return back()->with('error', 'Impossible de supprimer cette thématique car elle est utilisée par des questions.');
        }

        $theme->delete();
        return back()->with('success', 'Thématique supprimée.');
    }
}
