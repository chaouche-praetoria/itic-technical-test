<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DomainController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-domains');
        
        return Inertia::render('Admin/Domains/Index', [
            'domains' => Domain::with('themes')->withCount(['questions', 'themes'])->latest()->get(),
            'allThemes' => Theme::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-domains');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        Domain::create($validated);

        return back()->with('success', 'Domaine créé.');
    }

    public function update(Request $request, Domain $domain)
    {
        Gate::authorize('manage-domains');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        $domain->update($validated);
        return back()->with('success', 'Domaine mis à jour.');
    }

    public function storeTheme(Request $request, Domain $domain)
    {
        Gate::authorize('manage-themes');

        $request->validate(['name' => 'required|string|max:100']);

        $slug = Str::slug($request->name);
        $theme = Theme::firstOrCreate(
            ['slug' => $slug],
            ['name' => $request->name]
        );

        $domain->themes()->syncWithoutDetaching([$theme->id]);

        return back()->with('success', 'Thème ajouté au domaine.');
    }

    public function updateTheme(Request $request, Theme $theme)
    {
        Gate::authorize('manage-themes');

        $validated = $request->validate(['name' => 'required|string|max:100']);
        
        $theme->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return back()->with('success', 'Thème mis à jour.');
    }

    public function destroyTheme(Theme $theme)
    {
        Gate::authorize('manage-themes');

        $theme->delete();
        return back()->with('success', 'Thème supprimé définitivement.');
    }

    public function detachTheme(Domain $domain, Theme $theme)
    {
        Gate::authorize('manage-themes');

        $domain->themes()->detach($theme->id);
        return back()->with('success', 'Thème retiré de ce domaine.');
    }
}
