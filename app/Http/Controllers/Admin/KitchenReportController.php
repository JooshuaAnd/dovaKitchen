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

        // 3. MENGHITUNG TOTAL PORSI YANG DIPESAN OLEH PELANGGAN (RINGKASAN)
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

        // 4. MENGAMBIL RINCIAN DETAIL PESANAN PELANGGAN PER HARI
        $orderItems = OrderItem::with([
                'order' => function ($q) {
                    $q->select('id', 'user_id', 'order_code', 'payment_status', 'created_at')
                      ->with(['user' => function ($qu) {
                          $qu->select('id', 'name', 'phone', 'email')
                             ->with('allergens:id,name');
                      }]);
                },
                'menu:id,name,photo_path,price'
            ])
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        // 5. MENYUSUN DATA UNTUK DITAMPILKAN DI TAMPILAN (VIEW)
        $weekDates = [];
        // Looping sebanyak 5 kali untuk hari Senin (0) sampai Jumat (4)
        for ($i = 0; $i < 5; $i++) {
            // Menentukan tanggal spesifik untuk hari ke-$i
            $date = $startOfWeek->copy()->addDays($i);
            $dateString = $date->toDateString();
            
            // Memfilter menu yang dijadwalkan tepat pada tanggal ini
            $menusForDay = $dailyMenus->filter(function($dm) use ($dateString) {
                $dmDate = $dm->service_date instanceof Carbon 
                    ? $dm->service_date->toDateString() 
                    : substr((string)$dm->service_date, 0, 10);
                return $dmDate === $dateString;
            })->map(function($dm) use ($report, $dateString) {
                // Meng-copy data menu agar tidak merubah referensi aslinya
                $menu = clone $dm->menu;
                
                // Mencari apakah ada data total pesanan untuk menu ini pada tanggal ini di hasil query agregasi ($report)
                $orderAgg = $report->first(function($r) use ($menu, $dateString) {
                    $rDate = $r->service_date instanceof Carbon 
                        ? $r->service_date->toDateString() 
                        : substr((string)$r->service_date, 0, 10);
                    return $rDate === $dateString && $r->menu_id === $menu->id;
                });
                
                // Menyematkan nilai total_portions ke objek menu (jika tidak ada pesanan set jadi 0)
                $menu->total_portions = $orderAgg ? (int) $orderAgg->total_portions : 0;
                return $menu;
            })->values()->toArray(); // Re-index array

            // Total porsi keseluruhan untuk hari ini
            $totalDayPortions = array_sum(array_column($menusForDay, 'total_portions'));

            // Memfilter pesanan yang dijadwalkan pada hari ini
            $dayItems = $orderItems->filter(function ($item) use ($dateString) {
                $itemDate = $item->service_date instanceof Carbon 
                    ? $item->service_date->toDateString() 
                    : substr((string)$item->service_date, 0, 10);
                return $itemDate === $dateString;
            });

            // Mengelompokkan item pesanan per pesanan (order_id)
            $customerOrders = $dayItems->groupBy('order_id')->map(function ($items) {
                $first = $items->first();
                $order = $first->order;
                $user = $order ? $order->user : null;

                $itemsList = $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'menu_id' => $item->menu_id,
                        'menu_name' => $item->menu ? $item->menu->name : 'Menu',
                        'menu_photo' => $item->menu ? $item->menu->photo_path : null,
                        'portion_quantity' => (int) $item->portion_quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })->values()->toArray();

                $totalCustomerPortions = $items->sum('portion_quantity');

                return [
                    'order_id' => $order ? $order->id : null,
                    'order_code' => $order ? $order->order_code : '-',
                    'created_at' => $order && $order->created_at ? $order->created_at->format('d M Y H:i') : null,
                    'customer' => [
                        'id' => $user ? $user->id : null,
                        'name' => $user ? $user->name : 'Pelanggan',
                        'phone' => $user ? $user->phone : null,
                        'email' => $user ? $user->email : null,
                        'allergens' => $user && $user->allergens ? $user->allergens->pluck('name')->toArray() : [],
                    ],
                    'items' => $itemsList,
                    'total_portions' => (int) $totalCustomerPortions,
                ];
            })->values()->toArray();

            // Memasukkan data hari, menu-menunya, dan rincian pemesan ke dalam array utama $weekDates
            $weekDates[] = [
                'date' => $dateString,
                'day_name' => $date->format('l'), // Mengambil nama hari, misal: "Monday"
                'menus' => $menusForDay,
                'total_portions' => $totalDayPortions,
                'total_customers' => count($customerOrders),
                'customer_orders' => $customerOrders,
            ];
        }

        // 6. MENGIRIM DATA KE FRONTEND (VUE.JS) VIA INERTIA
        return Inertia::render('Admin/KitchenReport/Index', [
            'weekDates' => $weekDates,
            'currentWeekStart' => $startOfWeek->toDateString(),
        ]);
    }
}
