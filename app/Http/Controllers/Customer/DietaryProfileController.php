<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DietaryProfileController extends Controller
{
    // 1. MEMPERBARUI PROFIL ALERGI PENGGUNA
    public function update(Request $request)
    {
        // Memvalidasi data pilihan alergi yang dikirimkan oleh pengguna dari form
        $validated = $request->validate([
            'allergen_ids' => ['array'], // Data harus berupa array (karena bisa memilih lebih dari satu alergi)
            'allergen_ids.*' => ['exists:allergens,id'], // Setiap ID alergi yang dipilih harus benar-benar ada di tabel 'allergens'
        ]);

        // Menyimpan atau memperbarui data alergi pengguna ke dalam database (tabel pivot)
        // Fungsi sync() akan memastikan hanya alergi yang dikirimkan ini saja yang tersimpan.
        // Jika sebelumnya ada alergi lama yang tidak dikirim di sini, maka relasinya akan dihapus.
        // '?? []' memastikan jika form dikirim tanpa memilih satupun alergi, ia akan menggunakan array kosong (mereset semua alergi)
        $request->user()->allergens()->sync($validated['allergen_ids'] ?? []);

        // Mengarahkan pengguna langsung menuju halaman Katalog Utama (Menu) beserta pesan sukses
        return redirect()->route('catalog')->with('success', 'Dietary profile updated successfully.');
    }
}
