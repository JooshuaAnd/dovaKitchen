<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Allergen;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::with('allergens')->orderBy('created_at', 'desc')->get();
        return Inertia::render('Admin/Menus/Index', [
            'menus' => $menus
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Menus/Create', [
            'allergens' => Allergen::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'allergen_ids' => 'nullable|array',
            'allergen_ids.*' => 'exists:allergens,id',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('menus', 'public');
            $validated['photo_path'] = '/storage/' . $path;
        }
        unset($validated['photo']);

        $menu = Menu::create($validated);
        
        if (isset($validated['allergen_ids'])) {
            $menu->allergens()->sync($validated['allergen_ids']);
        }

        return redirect()->route('admin.menus.index')->with('success', 'Menu created successfully.');
    }

    public function edit(Menu $menu)
    {
        $menu->load('allergens');
        return Inertia::render('Admin/Menus/Edit', [
            'menu' => $menu,
            'allergens' => Allergen::all()
        ]);
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'allergen_ids' => 'nullable|array',
            'allergen_ids.*' => 'exists:allergens,id',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('menus', 'public');
            $validated['photo_path'] = '/storage/' . $path;
        }
        unset($validated['photo']);

        $menu->update($validated);
        
        $menu->allergens()->sync($validated['allergen_ids'] ?? []);

        return redirect()->route('admin.menus.index')->with('success', 'Menu updated successfully.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted successfully.');
    }
}
