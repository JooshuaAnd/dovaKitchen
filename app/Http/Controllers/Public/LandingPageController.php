<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    // 1. MENAMPILKAN HALAMAN UTAMA (LANDING PAGE) UNTUK PUBLIK
    // Fungsi __invoke() ini adalah "Single Action Controller" (Pengontrol Aksi Tunggal)
    // yang berarti controller ini hanya punya satu fungsi utama saat dipanggil oleh route.
    public function __invoke(Request $request)
    {
        // Mendapatkan waktu saat ini menggunakan zona waktu Jakarta
        $now = Carbon::now('Asia/Jakarta');
        
        // Menentukan rentang tanggal untuk minggu yang sedang berjalan saat ini
        // (Dimulai dari hari Senin sampai hari Jumat)
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(4);

        // 2. MENGAMBIL JADWAL MENU MINGGU INI DARI DATABASE
        $dailyMenus = DailyMenu::with('menu.allergens') // Ambil jadwal harian berserta detail menu dan relasi alerginya
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]) // Hanya ambil jadwal untuk minggu ini
            ->whereHas('menu', function ($query) {
                // Pastikan menu yang terambil hanya menu yang statusnya aktif ('is_active' = true)
                $query->where('is_active', true);
            })
            ->get();

        // 3. MEMFORMAT DATA MENU UNTUK DITAMPILKAN DI HALAMAN DEPAN
        $menus = collect(); // Membuat koleksi kosong untuk menampung data
        
        // Melakukan perulangan pada setiap jadwal harian yang ditemukan
        foreach ($dailyMenus as $dm) {
            $menuData = $dm->menu->toArray(); // Mengubah model Menu tersebut menjadi array
            $date = Carbon::parse($dm->service_date->format('Y-m-d'), 'Asia/Jakarta'); // Ambil tanggal penyajiannya
            
            // Menyisipkan informasi tanggal penyajian dan nama hari ke dalam array
            // agar bisa ditampilkan dengan rapi di Landing Page
            $menuData['service_date'] = $date->toDateString();
            $menuData['service_day'] = strtolower($date->format('l'));
            
            // Memasukkan array menu yang sudah rapi ini ke dalam koleksi $menus
            $menus->push($menuData);
        }

        // 4. MENGIRIM DATA KE TAMPILAN FRONTEND (VUE.JS)
        // Menampilkan komponen Vue 'Public/Landing' dan mengirimkan daftar menu ke sana
        return Inertia::render('Public/Landing', [
            'menus' => $menus,
        ]);
    }
}
