<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    // 1. MENAMPILKAN DAFTAR SEMUA PENGGUNA (KUSTOMER)
    public function index()
    {
        // Mengambil seluruh data user dari database
        $users = User::with('allergens') // Memuat relasi tabel 'allergens' agar kita tahu alergi yang dimiliki tiap user
            ->withCount('orders') // Menghitung total pesanan yang pernah dibuat oleh masing-masing user (menghasilkan atribut 'orders_count')
            ->withMax('orders', 'created_at') // Mencari tanggal pesanan terakhir yang dibuat oleh user (menghasilkan atribut 'orders_max_created_at')
            ->orderBy('created_at', 'desc') // Mengurutkan daftar user berdasarkan tanggal pendaftaran dari yang paling baru
            ->get(); // Mengeksekusi query dan mengambil hasilnya

        // Mengirimkan sekumpulan data user tersebut ke halaman frontend (Vue.js) menggunakan Inertia
        return Inertia::render('Admin/Users/Index', [
            'users' => $users
        ]);
    }

    // 2. MENGHAPUS DATA PENGGUNA (KUSTOMER)
    public function destroy(User $user)
    {
        // Mengeksekusi perintah hapus record user dari database
        $user->delete();

        // Mengembalikan pengguna (admin) ke halaman sebelumnya dengan membawa pesan sukses
        return redirect()->back()->with('success', 'User has been deleted successfully.');
    }
}
