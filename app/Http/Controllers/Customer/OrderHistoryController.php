<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderHistoryController extends Controller
{
    // 1. MENAMPILKAN HALAMAN RIWAYAT PESANAN KUSTOMER
    public function index(Request $request)
    {
        // Mengambil daftar pesanan (orders) hanya milik pengguna (kustomer) yang sedang login saat ini
        $orders = $request->user()->orders()
            // Memuat relasi 'orderItems' beserta relasi 'menu' di dalamnya,
            // sehingga kita bisa tahu pesanan ini berisi makanan apa saja
            ->with(['orderItems.menu'])
            // Mengurutkan data riwayat dari yang paling baru (terakhir dipesan) hingga yang paling lama
            ->orderBy('created_at', 'desc')
            ->get(); // Mengeksekusi query dan mengambil hasilnya

        // Mengirimkan data daftar pesanan ($orders) tersebut ke tampilan antarmuka Vue.js
        return Inertia::render('Customer/History/Index', [
            'orders' => $orders
        ]);
    }
}
