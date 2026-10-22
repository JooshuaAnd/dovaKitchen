<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentProofController extends Controller
{
    // 1. MENYIMPAN BUKTI PEMBAYARAN YANG DIUNGGAH OLEH KUSTOMER
    public function store(Request $request, Order $order)
    {
        // Pengecekan Keamanan (Otorisasi)
        // Memastikan bahwa pesanan (order) yang sedang diunggah buktinya ini benar-benar milik user yang sedang login
        // Jika ID pemilik pesanan berbeda dengan ID kustomer yang mengakses fungsi ini, lemparkan error 403 (Forbidden/Dilarang)
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        // Memvalidasi file gambar yang diunggah
        // Syaratnya: wajib diisi ('required'), harus berupa file gambar ('image'), dan ukuran maksimal 2MB (2048 KB)
        $validated = $request->validate([
            'payment_proof' => ['required', 'image', 'max:2048'],
        ]);

        // Memastikan bahwa ada file yang terlampir pada request dengan nama 'payment_proof'
        if ($request->hasFile('payment_proof')) {
            // Jika ada, simpan file gambar tersebut ke dalam direktori 'storage/app/public/payment_proofs'
            $path = $request->file('payment_proof')->store('payment_proofs', 'public');
            
            // Memperbarui data pesanan (Order) di database
            $order->update([
                'payment_proof_path' => $path, // Menyimpan lokasi/path file buktinya
                'payment_status' => 'awaiting_verification', // Mengubah status pesanan dari 'Menunggu Pembayaran' menjadi 'Menunggu Verifikasi Admin'
            ]);
        }

        // Mengarahkan pengguna (kustomer) ke halaman riwayat pesanan (/history) dengan membawa pesan sukses
        return redirect()->route('history.index')->with('success', 'Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi.');
    }
}
