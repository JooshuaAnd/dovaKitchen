<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    // 1. MENAMPILKAN HALAMAN PROFIL KUSTOMER
    public function edit(Request $request)
    {
        // Membuka halaman Vue (Customer/Profile/Edit) 
        // Sekaligus mengirimkan data diri pengguna yang sedang login saat ini (seperti nama, email, dsb.)
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
            'phone' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'], // Tanggal lahir tidak boleh hari ini atau masa depan
            // Validasi email ini unik, tapi abaikan jika emailnya adalah miliknya sendiri saat ini (supaya tidak error saat dia simpan tanpa ganti email)
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        // Mengisi objek pengguna dengan data baru yang sudah divalidasi
        $user->fill($validated);
        // Menyimpan pembaruan tersebut secara permanen ke database
        $user->save();

        // Mengembalikan pengguna ke halaman profil lagi dengan membawa pesan berhasil
        return back()->with('success', 'Profile updated successfully.');
    }

    // 3. MEMPERBARUI KATA SANDI (PASSWORD)
    public function updatePassword(Request $request)
    {
        // Melakukan validasi keamanan saat mengubah password
        $validated = $request->validate([
            // Memastikan pengguna memasukkan password lamanya dengan benar (syarat keamanan)
            'current_password' => ['required', 'current_password'],
            // Password baru harus sesuai standar Laravel (biasanya min 8 karakter) dan input konfirmasi (password_confirmation) harus sama (confirmed)
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Jika validasi lolos, perbarui password pengguna di database
        $request->user()->update([
            // Kata sandi wajib diacak/dienkripsi menggunakan sistem Hash Laravel
            'password' => Hash::make($validated['password']),
        ]);

        // Mengembalikan ke halaman pengaturan profil dengan konfirmasi keberhasilan
        return back()->with('success', 'Password updated successfully.');
    }
}
