<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BirthdayRewardController extends Controller
{
    // 1. PROSES KLAIM KADO ULANG TAHUN OLEH PELANGGAN
    public function claim(Request $request)
    {
        // Mengambil data pengguna yang sedang login saat ini
        $user = $request->user();
        
        // Mendapatkan waktu dan tanggal hari ini berdasarkan zona waktu Jakarta
        $today = Carbon::now('Asia/Jakarta');

        // Memeriksa apakah pengguna sudah mengisi tanggal lahir di profilnya
        if (!$user->date_of_birth) {
            // Jika belum diisi, kembalikan ke halaman sebelumnya dengan pesan error
            return back()->with('error', 'Silakan lengkapi tanggal lahir di profil Anda terlebih dahulu.');
        }

        // Mengubah string tanggal lahir dari database menjadi objek Carbon (agar mudah dimanipulasi)
        $dob = Carbon::parse($user->date_of_birth, 'Asia/Jakarta');

        // 2. VERIFIKASI TANGGAL ULANG TAHUN
        // Membandingkan bulan dan tanggal hari ini dengan bulan dan tanggal lahir pengguna
        if ($dob->month !== $today->month || $dob->day !== $today->day) {
            // Jika tidak sama (bukan hari ulang tahunnya), maka tolak proses klaim
            return back()->with('error', 'Kado ulang tahun hanya dapat diklaim tepat pada hari ulang tahun Anda.');
        }

        // 3. VERIFIKASI APAKAH VOUCHER SUDAH DIKLAIM TAHUN INI
        // Mengecek apakah kolom 'birthday_reward_claimed_year' di database sama dengan tahun ini
        // (Ini mencegah pelanggan mengklaim voucher berkali-kali di hari ulang tahun yang sama)
        if ($user->birthday_reward_claimed_year === $today->year) {
            // Jika sudah diklaim tahun ini, tolak proses klaim
            return back()->with('error', 'Anda sudah menggunakan kado voucher ulang tahun untuk tahun ini.');
        }

        // 4. MENGAKTIFKAN VOUCHER KE DALAM SESSION KERANJANG
        // Jika semua syarat terpenuhi, simpan status klaim ('claimed_birthday_voucher') ke dalam Session
        // Session ini nantinya akan dibaca oleh sistem keranjang/checkout untuk memotong harga sebesar 20%
        session()->put('claimed_birthday_voucher', true);

        // Mengembalikan pengguna ke halaman semula dengan pesan sukses
        return back()->with('success', '🎉 Selamat Ulang Tahun! Kado voucher diskon 20% berhasil diklaim dan siap digunakan pada pesanan Anda.');
    }
}
