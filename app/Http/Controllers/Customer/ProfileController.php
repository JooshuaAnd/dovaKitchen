<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    // 1. MENAMPILKAN HALAMAN PROFIL KUSTOMER
    public function edit(Request $request)
    {
        // Membuka halaman Vue (Customer/Profile/Edit) 
        // Mengirimkan data profil customer yang sedang login.
        return Inertia::render('Customer/Profile/Edit', [
            'user' => $request->user(),
        ]);
    }

    // 2. MEMPERBARUI DATA INFORMASI PROFIL
    public function update(Request $request)
    {
        // Mengambil objek model pengguna yang sedang aktif (login)
        $user = $request->user();

        // Melakukan validasi input dari formulir edit profil
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ]);

        // Mengisi objek pengguna dengan data baru yang sudah divalidasi
        $user->fill($validated);
        // Menyimpan pembaruan tersebut secara permanen ke database
        $user->save();

        // Mengembalikan pengguna ke halaman profil lagi dengan membawa pesan berhasil
        return back()->with('success', 'Profile updated successfully.');
    }

}
