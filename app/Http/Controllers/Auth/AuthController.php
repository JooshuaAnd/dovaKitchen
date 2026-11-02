<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Admin login.
    public function login(Request $request)
    {
        // Memvalidasi input email dan password yang dikirim dari form login
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Mencoba mencocokkan kredensial dengan data di database (proses otentikasi)
        if (Auth::attempt($credentials) && auth()->user()->is_admin) {
            // Jika berhasil, buat ulang session untuk mencegah serangan Session Fixation
            $request->session()->regenerate();

            return redirect()->route('admin.menus.index');
        }

        Auth::logout();

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
