<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DietaryProfileController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'allergen_ids' => ['array'],
            'allergen_ids.*' => ['exists:allergens,id'],
        ]);

        $request->user()->allergens()->sync($validated['allergen_ids'] ?? []);

        return redirect()->route('catalog')->with('success', 'Dietary profile updated successfully.');
    }
}
