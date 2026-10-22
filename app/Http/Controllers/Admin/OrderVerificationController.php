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

        // Notifikasi ke WhatsApp Bot jika webhook aktif
        try {
            $order->loadMissing('user');
            if ($order->user && $order->user->phone) {
                \Illuminate\Support\Facades\Http::timeout(2)->post('http://127.0.0.1:3000/webhook/order-status', [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'status' => $validated['status'],
                    'phone' => $order->user->phone,
                    'customer_name' => $order->user->name,
                ]);
            }
        } catch (\Throwable $e) {
            // Abaikan jika server bot sedang tidak aktif agar tidak mengganggu transaksi admin
        }

        // Mengembalikan pengguna (admin) ke halaman sebelumnya dengan membawa pesan sukses (session flash message)
        return back()->with('success', 'Order status updated to ' . $validated['status']);
    }
}
