<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DomainController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Domains/Index', [
            'domains' => Domain::withCount(['questions', 'themes'])->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
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
        $request->validate(['name' => 'required|string|max:100']);

        $domain->themes()->create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return back()->with('success', 'Thème créé.');
    }
}
