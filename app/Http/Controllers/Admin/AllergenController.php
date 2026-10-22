<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Allergen;
use Illuminate\Http\Request;

class AllergenController extends Controller
{
    /**
     * Get all allergens/ingredients.
     */
    public function index()
    {
        return response()->json(Allergen::orderBy('name')->get());
    }

    /**
     * Store a new allergen/ingredient.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:allergens,name',
            'category' => 'required|string|max:50',
        ], [
            'name.required' => 'Nama bahan baku wajib diisi.',
            'name.unique' => 'Bahan baku dengan nama ini sudah terdaftar.',
            'category.required' => 'Kategori bahan baku wajib dipilih.',
        ]);

        $allergen = Allergen::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bahan baku baru berhasil ditambahkan.',
            'allergen' => $allergen,
        ], 201);
    }
}
