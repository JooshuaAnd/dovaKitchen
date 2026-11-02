<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VoucherTemplate;
use App\Services\RfmService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class UserController extends Controller
{
    protected RfmService $rfmService;

    public function __construct(RfmService $rfmService)
    {
        $this->rfmService = $rfmService;
    }

    // 1. MENAMPILKAN DAFTAR SEMUA PENGGUNA (KUSTOMER) BESERTA ANALISIS RFM & PREFERENSI BAHAN BAKU
    public function index()
    {
        // Mengambil seluruh user non-admin (kustomer) beserta relasi pantangan dan riwayat pesanannya
        $users = User::where('is_admin', false)
            ->with([
                'allergens',
                'vouchers' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'orders' => function ($query) {
                    $query->with(['orderItems.menu.allergens'])
                          ->orderBy('created_at', 'desc');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $analysisDate = Carbon::now();

        // Pendelegasian perhitungan RFM, scoring, segmentasi, dan agregasi KPI ke RfmService
        $analysisResult = $this->rfmService->analyzeUsers($users, $analysisDate);

        $analysisResult['users'] = $analysisResult['users']->map(function (array $user) {
            if (!empty($user['id']) && !empty($user['phone'])) {
                $user['customer_access_url'] = URL::temporarySignedRoute(
                    'customer-access.signed',
                    now()->addDays(7),
                    ['user' => $user['id']],
                    false
                );
            }

            return $user;
        });

        // Mengirimkan sekumpulan data user tersebut ke halaman frontend (Vue.js) menggunakan Inertia
        return Inertia::render('Admin/Users/Index', [
            'users' => $analysisResult['users'],
            'summary' => $analysisResult['summary'],
            'voucherTemplates' => VoucherTemplate::orderBy('title')->get(),
        ]);
    }

    // 2. MENGHAPUS DATA PENGGUNA (KUSTOMER)
    public function destroy(User $user)
    {
        // Mengeksekusi perintah hapus record user dari database
        $user->delete();

        // Mengembalikan pengguna (admin) ke halaman sebelumnya dengan membawa pesan sukses
        return redirect()->back()->with('success', 'User has been deleted successfully.');
    }
}
