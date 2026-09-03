<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\DailyMenu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KitchenReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. MENDAPATKAN TANGGAL AWAL DAN AKHIR MINGGU INI
        // Mengambil parameter 'date' dari URL, jika tidak ada gunakan tanggal hari ini
        $dateParam = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        // Menentukan hari Senin dari tanggal tersebut sebagai hari pertama dalam minggu
        $startOfWeek = Carbon::parse($dateParam, 'Asia/Jakarta')->startOfWeek();
        // Menentukan hari Jumat dengan cara menambah 4 hari dari hari Senin
        $endOfWeek = $startOfWeek->copy()->addDays(4);

        // 2. MENGAMBIL JADWAL MENU MINGGUAN DARI DATABASE
        // Mengambil data jadwal harian beserta relasi menu-nya dari hari Senin hingga Jumat
        $dailyMenus = DailyMenu::with('menu')
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        // 3. MENGHITUNG TOTAL PORSI YANG DIPESAN OLEH PELANGGAN
        // Melakukan query agregasi (pengelompokan dan penjumlahan) pada tabel order_items
        $report = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            // Hanya ambil pesanan yang status pembayarannya sudah 'paid' (lunas)
            ->where('orders.payment_status', 'paid')
            // Batasi hanya untuk pesanan di minggu yang sedang aktif (Senin - Jumat)
            ->whereBetween('order_items.service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->select(
                'order_items.service_date',
                'order_items.menu_id',
                // Menjumlahkan kolom portion_quantity menjadi alias total_portions
                DB::raw('SUM(order_items.portion_quantity) as total_portions')
            )
            // Mengelompokkan hasil berdasarkan tanggal layanan dan ID menu
            ->groupBy('order_items.service_date', 'order_items.menu_id')
            ->get();

        // 4. MENYUSUN DATA UNTUK DITAMPILKAN DI TAMPILAN (VIEW)
        $weekDates = [];
        // Looping sebanyak 5 kali untuk hari Senin (0) sampai Jumat (4)
        for ($i = 0; $i < 5; $i++) {
            // Menentukan tanggal spesifik untuk hari ke-$i
            $date = $startOfWeek->copy()->addDays($i);
            $dateString = $date->toDateString();
            
            // Memfilter menu yang dijadwalkan tepat pada tanggal ini
            $menusForDay = $dailyMenus->filter(function($dm) use ($dateString) {
                return $dm->service_date->toDateString() === $dateString;
            })->map(function($dm) use ($report, $dateString) {
                // Meng-copy data menu agar tidak merubah referensi aslinya
                $menu = clone $dm->menu;
                
                // Mencari apakah ada data total pesanan untuk menu ini pada tanggal ini di hasil query agregasi ($report)
                $orderAgg = $report->first(function($r) use ($menu, $dateString) {
                    return $r->service_date === $dateString && $r->menu_id === $menu->id;
                });
                
                // Menyematkan nilai total_portions ke objek menu (jika tidak ada pesanan set jadi 0)
                $menu->total_portions = $orderAgg ? (int) $orderAgg->total_portions : 0;
                return $menu;
            })->values()->toArray(); // Re-index array

            // Memasukkan data hari dan menu-menunya ke dalam array utama $weekDates
            $weekDates[] = [
                'date' => $dateString,
                'day_name' => $date->format('l'), // Mengambil nama hari, misal: "Monday"
                'menus' => $menusForDay,
            ];
        }

        // 5. MENGIRIM DATA KE FRONTEND (VUE.JS) VIA INERTIA
        return Inertia::render('Admin/KitchenReport/Index', [
            'weekDates' => $weekDates,
            'currentWeekStart' => $startOfWeek->toDateString(),
        ]);
    }
}
