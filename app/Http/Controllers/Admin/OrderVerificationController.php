<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

use Inertia\Inertia;

class OrderVerificationController extends Controller
{
    // 1. MENAMPILKAN DAFTAR SEMUA PESANAN
    public function index()
    {
        // Mengambil semua data pesanan dari database beserta data user (pelanggan) yang memesannya
        // Data diurutkan berdasarkan waktu pembuatan (created_at) dari yang paling baru ke yang paling lama
        $orders = Order::with('user')->orderBy('created_at', 'desc')->get();
        
        // Mengirimkan data pesanan tersebut ke halaman frontend (Vue.js) menggunakan Inertia
        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders
        ]);
    }

    // 2. MEMPERBARUI STATUS PEMBAYARAN PESANAN
    public function update(Request $request, Order $order)
    {
        // Melakukan validasi input dari request yang dikirimkan oleh admin
        // Memastikan field 'status' wajib diisi dan nilainya hanya boleh 'paid' (lunas) atau 'rejected' (ditolak)
        $validated = $request->validate([
            'status' => 'required|in:paid,rejected',
        ]);

        // Memperbarui kolom 'payment_status' pada database pesanan sesuai dengan status yang divalidasi
        $order->update([
            'payment_status' => $validated['status'],
        ]);

        // Mengembalikan pengguna (admin) ke halaman sebelumnya dengan membawa pesan sukses (session flash message)
        return back()->with('success', 'Order status updated to ' . $validated['status']);
    }
}
