<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\DailyMenu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogController extends Controller
{
    // 1. MENAMPILKAN HALAMAN UTAMA KATALOG KUSTOMER
    public function index(Request $request)
    {
        // Mengambil daftar ID alergi yang dimiliki oleh pengguna yang sedang login saat ini
        $userAllergens = $request->user()->allergens->pluck('id')->toArray();

        // Mendapatkan waktu saat ini di zona waktu Jakarta
        $now = Carbon::now('Asia/Jakarta');
        // Menentukan rentang tanggal minggu yang sedang berjalan (Senin sampai Jumat)
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(4);

        // 2. Mengambil seluruh menu aktif. Kecocokan dietary ditandai pada data menu,
        // bukan disembunyikan, agar pelanggan tetap dapat melihat pilihan yang tersedia.
        $dailyMenus = DailyMenu::with('menu.allergens') // Ambil jadwal harian berserta menu dan data alergi dari menu tersebut
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]) // Hanya untuk minggu ini
            ->whereHas('menu', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        // 3. MEMFORMAT DATA MENU UNTUK DITAMPILKAN & PENGECEKAN BATAS WAKTU (CUT-OFF) PESANAN
        $menus = collect(); // Membuat collection kosong untuk menyimpan hasil format menu
        foreach ($dailyMenus as $dm) {
            $menuData = $dm->menu->toArray(); // Mengubah data model Menu menjadi array
            $date = Carbon::parse($dm->service_date->format('Y-m-d'), 'Asia/Jakarta'); // Mendapatkan tanggal menu tersebut disajikan
            
            // Menyisipkan beberapa info tambahan untuk frontend
            $menuData['daily_menu_id'] = $dm->id; 
            $menuData['service_date'] = $date->toDateString();
            $menuData['service_day'] = strtolower($date->format('l'));
            $conflictingAllergens = $dm->menu->allergens->whereIn('id', $userAllergens)->pluck('name')->values();
            $menuData['is_recommended'] = $conflictingAllergens->isEmpty();
            $menuData['dietary_warnings'] = $conflictingAllergens;
            
            // Menentukan batas waktu (Cut-off Time) pemesanan:
            // Pesanan hanya bisa dilakukan maksimal H-1 pada jam 17:00 sore.
            $cutOffTime = $date->copy()->subDay()->setTime(17, 0, 0);
            
            // Mengecek apakah pesanan masih bisa dilakukan (waktu saat ini belum melewati cut-off)
            // Jika sudah lewat jam 17:00 H-1, maka 'is_orderable' akan bernilai false
            $menuData['is_orderable'] = $now->lessThan($cutOffTime);
            
            $menus->push($menuData); // Memasukkan menu yang sudah diformat ke dalam collection
        }

        // 4. MENGHITUNG STATUS REWARD ULANG TAHUN (CRM FITUR ULANG TAHUN)
        $user = $request->user();
        $isBirthday = false; // Status apakah hari ini ulang tahun pengguna
        $hasUsedThisYear = false; // Status apakah kado sudah digunakan tahun ini
        $isClaimedInSession = session()->get('claimed_birthday_voucher', false); // Cek apakah kado sedang aktif di dalam sesi

        // Memastikan pengguna telah mengisi data tanggal lahir
        if ($user && $user->date_of_birth) {
            $dob = Carbon::parse($user->date_of_birth, 'Asia/Jakarta');
            
            // Mengecek apakah tanggal dan bulan lahirnya sama persis dengan tanggal hari ini
            if ($dob->month === $now->month && $dob->day === $now->day) {
                $isBirthday = true; // Tandai bahwa hari ini ulang tahunnya
                
                // Mengecek ke database apakah pengguna sudah mengklaim kadonya pada tahun ini
                $hasUsedThisYear = ($user->birthday_reward_claimed_year === $now->year);
            }
        }

        // Menyusun konfigurasi reward ulang tahun untuk dikirim ke frontend (Vue.js)
        $birthdayReward = [
            'is_birthday' => $isBirthday,
            'has_used_this_year' => $hasUsedThisYear,
            'is_claimed' => $isClaimedInSession,
            'discount_percentage' => 20, // Besaran diskon 20%
            'min_purchase' => 50000,
            'user_name' => $user ? $user->name : '',
        ];

        $userVouchers = $user ? \App\Models\Voucher::where('user_id', $user->id)
            ->where('is_used', false)
            ->where('valid_until', '>=', $now)
            ->orderBy('valid_until', 'asc')
            ->get() : [];

        // 5. Menghitung reward diskon otomatis berdasarkan segmen RFM pelanggan
        $rfmReward = null;
        if ($user) {
            $rfmService = app(\App\Services\RfmService::class);
            $rfmData = $rfmService->analyzeSingleUser($user, $now);
            if (!empty($rfmData['voucher_preset'])) {
                $rfmReward = array_merge($rfmData['voucher_preset'], [
                    'segment' => $rfmData['segment'],
                ]);
            }
        }

        // 6. MENGIRIM KESELURUHAN DATA KE HALAMAN KATALOG
        return Inertia::render('Customer/Catalog/Index', [
            'menus' => $menus,
            'birthdayReward' => $birthdayReward, // Data untuk menampilkan atau menyembunyikan pop-up ulang tahun
            'userVouchers' => $userVouchers,
            'rfmReward' => $rfmReward,
        ]);
    }
}
