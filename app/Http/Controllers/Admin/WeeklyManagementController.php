<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\DailyMenu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WeeklyManagementController extends Controller
{
    // 1. MENAMPILKAN HALAMAN MANAJEMEN MINGGUAN (JADWAL MENU)
    public function index(Request $request)
    {
        // Mendapatkan tanggal dari parameter URL 'date'. Jika kosong, gunakan tanggal hari ini.
        $dateParam = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        
        // Menentukan hari Senin (awal minggu) dari tanggal yang didapat
        $startOfWeek = Carbon::parse($dateParam, 'Asia/Jakarta')->startOfWeek(); 
        // Menentukan hari Jumat (akhir minggu) dengan menambahkan 4 hari ke hari Senin
        $endOfWeek = $startOfWeek->copy()->addDays(4); 

        // Mengambil data jadwal harian beserta relasi menu-nya dari hari Senin hingga Jumat
        $dailyMenus = DailyMenu::with('menu')
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        // Mengambil semua menu yang statusnya aktif ('is_active' = true) untuk ditampilkan sebagai pilihan jadwal
        $availableMenus = Menu::where('is_active', true)->orderBy('name')->get();

        $weekDates = [];
        // Looping 5 kali (untuk hari Senin sampai Jumat)
        for ($i = 0; $i < 5; $i++) {
            // Menghitung tanggal yang spesifik untuk hari ke-$i
            $date = $startOfWeek->copy()->addDays($i);
            
            // Menyaring (filter) jadwal menu harian khusus untuk tanggal iterasi saat ini
            $menusForDay = $dailyMenus->filter(function($dm) use ($date) {
                return $dm->service_date->toDateString() === $date->toDateString();
            })->map(function($dm) {
                // Mengambil objek menu dari jadwal tersebut
                return $dm->menu;
            })->values()->toArray(); // Mengurutkan ulang indeks array

            // Memasukkan tanggal, nama hari, dan daftar menu ke dalam array utama
            $weekDates[] = [
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'), // Mengambil nama hari bahasa Inggris (e.g., "Monday")
                'menus' => $menusForDay,
            ];
        }

        // Mengirimkan data jadwal mingguan, pilihan menu aktif, dan tanggal awal minggu ke frontend (Vue.js)
        return Inertia::render('Admin/WeeklyManagement/Index', [
            'weekDates' => $weekDates,
            'availableMenus' => $availableMenus,
            'currentWeekStart' => $startOfWeek->toDateString(),
        ]);
    }

    // 2. MEMPERBARUI JADWAL MENU UNTUK SUATU HARI
    public function update(Request $request)
    {
        // Validasi data masukan dari admin
        $validated = $request->validate([
            'date' => 'required|date', // Tanggal wajib ada dan berformat tanggal
            'menu_ids' => 'array', // ID menu yang dipilih harus berupa array (bisa lebih dari satu)
            'menu_ids.*' => 'exists:menus,id', // Setiap ID menu harus benar-benar ada di tabel menus
        ]);

        $date = $validated['date'];
        $menuIds = $validated['menu_ids'] ?? []; // Jika tidak ada menu yang dipilih, gunakan array kosong

        // Menghapus semua jadwal menu yang sudah ada pada tanggal tersebut (reset jadwal hari itu)
        DailyMenu::where('service_date', $date)->delete();

        // Menyiapkan array kosong untuk menampung data jadwal baru yang akan disimpan
        $inserts = [];
        $now = Carbon::now();
        // Looping melalui setiap ID menu yang dipilih admin
        foreach ($menuIds as $menuId) {
            // Memasukkan struktur data ke dalam array $inserts
            $inserts[] = [
                'menu_id' => $menuId,
                'service_date' => $date,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Jika array $inserts tidak kosong (ada menu yang dijadwalkan)
        if (!empty($inserts)) {
            // Melakukan proses insert banyak data sekaligus (bulk insert) ke tabel daily_menus
            DailyMenu::insert($inserts);
        }

        // Mengembalikan pengguna ke halaman sebelumnya dengan pesan sukses
        return redirect()->back()->with('success', 'Schedule updated successfully.');
    }
}
