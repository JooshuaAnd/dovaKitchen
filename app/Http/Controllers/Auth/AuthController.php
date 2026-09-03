<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 1. PROSES PENDAFTARAN (REGISTRASI) AKUN BARU
    public function register(Request $request)
    {
        // Memvalidasi data yang diinput oleh calon pendaftar di form registrasi
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'date_of_birth' => 'required|date|before:today', // Tanggal lahir harus sebelum hari ini
            'email' => 'required|string|email|max:255|unique:users', // Email wajib unik (belum pernah terdaftar)
            'password' => 'required|string|min:8', // Password minimal 8 karakter
        ]);

        // Menyimpan data pengguna baru ke dalam database tabel 'users'
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'date_of_birth' => $request->date_of_birth,
            'password' => Hash::make($request->password), // Password harus di-hash (enkripsi) demi keamanan
        ]);

        // Memasukkan pengguna ke dalam sesi login secara otomatis setelah berhasil mendaftar
        Auth::login($user);

        // Mengarahkan (redirect) pengguna baru ke halaman pengaturan alergi (onboarding)
        return redirect()->route('dietary-setup');
    }

    // 2. PROSES MASUK (LOGIN) KE DALAM SISTEM
    public function login(Request $request)
    {
        // Memvalidasi input email dan password yang dikirim dari form login
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Mencoba mencocokkan kredensial dengan data di database (proses otentikasi)
        if (Auth::attempt($credentials)) {
            // Jika berhasil, buat ulang session untuk mencegah serangan Session Fixation
            $request->session()->regenerate();

            // Pengecekan peran (role) pengguna
            if (auth()->user()->is_admin) {
                // Jika pengguna adalah Admin, arahkan ke dashboard admin (Manajemen Menu)
                return redirect()->route('admin.menus.index');
            }

            // Jika pengguna adalah Kustomer biasa, arahkan ke halaman katalog menu
            return redirect()->route('catalog');
        }

        // Jika kredensial salah (email tidak ditemukan atau password tidak cocok), lemparkan error validasi
        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    // 3. PROSES KELUAR (LOGOUT) DARI SISTEM
    public function logout(Request $request)
    {
        // Menghapus sesi otentikasi pengguna saat ini
        Auth::logout();

        // Mengosongkan data sesi sepenuhnya untuk memastikan keamanan
        $request->session()->invalidate();
        
        // Memperbarui token CSRF (mencegah eksploitasi pada token sesi yang sudah tidak aktif)
        $request->session()->regenerateToken();

        // Mengembalikan pengguna ke halaman utama (Landing Page)
        return redirect('/');
    }
}
