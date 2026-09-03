<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // 1. PROSES PEMBUATAN PESANAN (CHECKOUT)
    public function store(Request $request)
    {
        // Memvalidasi data keranjang belanja yang dikirim dari frontend
        $validated = $request->validate([
            'items' => ['required', 'array'], // Keranjang harus berupa array dan tidak boleh kosong
            'items.*.menu_id' => ['required', 'exists:menus,id'], // ID Menu harus ada di database
            'items.*.service_date' => ['required', 'date'], // Tanggal layanan (pengiriman) harus format tanggal valid
            'items.*.portion_quantity' => ['required', 'integer', 'min:1'], // Jumlah porsi minimal 1
        ]);

        // 2. MENGGUNAKAN DATABASE TRANSACTION
        // Fungsi DB::transaction memastikan jika ada satu proses yang gagal (error) di dalam blok ini,
        // maka seluruh perubahan database akan dibatalkan (rollback) untuk menjaga konsistensi data.
        return DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $now = \Carbon\Carbon::now('Asia/Jakarta');
            $subtotalAmount = 0; // Total harga sebelum diskon
            $orderItemsData = []; // Array untuk menampung detail item pesanan sementara
            
            // 3. MENGHITUNG TOTAL HARGA DAN MENYIAPKAN DATA ITEM PESANAN
            // Melakukan perulangan untuk setiap item di keranjang belanja
            foreach ($validated['items'] as $item) {
                // Mencari data asli menu dari database berdasarkan ID
                $menu = Menu::findOrFail($item['menu_id']);
                
                // Menghitung subtotal untuk item ini (harga satuan * jumlah porsi)
                $subtotal = $menu->price * $item['portion_quantity'];
                
                // Memasukkan data detail item ke dalam array sementara
                $orderItemsData[] = [
                    'menu_id' => $menu->id,
                    'service_date' => $item['service_date'],
                    'portion_quantity' => $item['portion_quantity'],
                    'unit_price' => $menu->price,
                    'subtotal' => $subtotal,
                ];
                
                // Menambahkan subtotal item ini ke total keseluruhan (subtotalAmount)
                $subtotalAmount += $subtotal;
            }

            // 4. PENGECEKAN REWARD / KADO ULANG TAHUN
            $discountAmount = 0;
            $discountType = null;
            $isBirthday = false;

            // Memastikan apakah hari ini adalah hari ulang tahun kustomer
            if ($user->date_of_birth) {
                $dob = \Carbon\Carbon::parse($user->date_of_birth, 'Asia/Jakarta');
                $isBirthday = ($dob->month === $now->month && $dob->day === $now->day);
            }

            // Memeriksa status klaim diskon dari Session
            $hasClaimedInSession = session()->get('claimed_birthday_voucher', false);
            // Memastikan pengguna belum pernah menggunakan kado diskon di tahun yang sama
            $hasNotUsedThisYear = ($user->birthday_reward_claimed_year !== $now->year);

            // Jika semua syarat terpenuhi (Hari ini ultah + Sudah klik klaim diskon + Belum dipakai + Total belanja >= 50rb)
            if ($isBirthday && $hasClaimedInSession && $hasNotUsedThisYear && $subtotalAmount >= 50000) {
                // Menghitung besaran diskon yaitu 20% dari Subtotal
                $discountAmount = round($subtotalAmount * 0.20);
                $discountType = 'Kado Ulang Tahun (Diskon 20%)';

                // Mencatat di profil pengguna bahwa ia sudah menggunakan kado ulang tahunnya untuk tahun ini
                $user->update([
                    'birthday_reward_claimed_year' => $now->year
                ]);
                
                // Menghapus data klaim diskon dari Session karena sudah terpakai
                session()->forget('claimed_birthday_voucher');
            }

            // Menghitung total akhir yang harus dibayar setelah dikurangi diskon
            $finalTotal = $subtotalAmount - $discountAmount;

            // 5. MENYIMPAN DATA PESANAN (ORDER) KE DATABASE
            $order = Order::create([
                'user_id' => $user->id,
                'order_code' => 'ORD-' . strtoupper(Str::random(8)), // Men-generate kode pesanan unik secara acak
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'discount_type' => $discountType,
                'total_amount' => $finalTotal,
                'payment_status' => 'pending_payment', // Status awal selalu menunggu pembayaran
            ]);

            // 6. MENYIMPAN DETAIL ITEM PESANAN (ORDER ITEMS) KE DATABASE
            // Memasukkan array $orderItemsData yang sudah disiapkan sebelumnya 
            // agar berelasi dengan ID order yang baru saja dibuat
            foreach ($orderItemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }

            // 7. MENGALIHKAN PENGGUNA KE HALAMAN DETAIL PESANAN UNTUK PEMBAYARAN
            return redirect()->route('orders.show', $order->id)->with('success', 'Order created successfully.');
        });
    }
}
