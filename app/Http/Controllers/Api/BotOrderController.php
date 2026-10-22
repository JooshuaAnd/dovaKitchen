<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BotOrderController extends Controller
{
    /**
     * Helper untuk normalisasi nomor HP
     * Menghapus karakter non-digit dan menyelaraskan format 08xx vs 62xx
     */
    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        }
        return $cleaned;
    }

    /**
     * Mencari user berdasarkan nomor WhatsApp
     */
    private function findUserByPhone(string $phone): ?User
    {
        $normalized = $this->normalizePhone($phone);
        $rawSuffix = substr($normalized, 2); // misal 8123456789

        return User::with('allergens')
            ->where(function ($query) use ($normalized, $rawSuffix) {
                $query->where('phone', $normalized)
                    ->orWhere('phone', '0' . $rawSuffix)
                    ->orWhere('phone', '+62' . $rawSuffix)
                    ->orWhere('phone', 'LIKE', '%' . $rawSuffix);
            })
            ->first();
    }

    /**
     * 1. GET /api/bot/menus/weekly
     * Mengambil jadwal menu Senin - Jumat minggu ini beserta foto dan alergennya.
     */
    public function getWeeklyMenu(Request $request)
    {
        $dateParam = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        $startOfWeek = Carbon::parse($dateParam, 'Asia/Jakarta')->startOfWeek(); // Hari Senin
        $endOfWeek = $startOfWeek->copy()->addDays(4); // Hari Jumat

        $dailyMenus = DailyMenu::with(['menu.allergens'])
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        $dayNamesIndo = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
        ];

        $schedule = [];
        for ($i = 0; $i < 5; $i++) {
            $currentDate = $startOfWeek->copy()->addDays($i);
            $dayNumber = $currentDate->dayOfWeekIso; // 1 = Senin, 5 = Jumat
            $dateString = $currentDate->toDateString();

            $menusForDay = $dailyMenus->filter(function ($dm) use ($dateString) {
                return $dm->service_date->toDateString() === $dateString;
            })->map(function ($dm) {
                $menu = $dm->menu;
                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'description' => $menu->description,
                    'price' => (float) $menu->price,
                    'formatted_price' => 'Rp ' . number_format($menu->price, 0, ',', '.'),
                    'photo_url' => $menu->photo_path ? url('storage/' . $menu->photo_path) : null,
                    'allergens' => $menu->allergens->pluck('name')->values()->all(),
                ];
            })->values()->toArray();

            $schedule[] = [
                'day_name' => $dayNamesIndo[$dayNumber] ?? $currentDate->format('l'),
                'date' => $dateString,
                'menus' => $menusForDay,
            ];
        }

        return response()->json([
            'success' => true,
            'week_start' => $startOfWeek->toDateString(),
            'week_end' => $endOfWeek->toDateString(),
            'schedule' => $schedule,
        ]);
    }

    /**
     * 2. POST /api/bot/customer/check
     * Memeriksa data customer dan riwayat alerginya berdasarkan nomor WA.
     */
    public function checkCustomer(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $user = $this->findUserByPhone($request->phone);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp belum terdaftar di sistem Dova Kitchen.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'allergens' => $user->allergens->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'category' => $a->category,
                ]),
            ],
        ]);
    }

    /**
     * 3. POST /api/bot/orders/validate-allergens
     * Mengecek apakah item menu yang dipesan bertentangan dengan alergi customer.
     */
    public function validateAllergens(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.service_date' => 'required|date',
        ]);

        $user = $request->user_id 
            ? User::with('allergens')->find($request->user_id)
            : $this->findUserByPhone($request->phone ?? '');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $userAllergenIds = $user->allergens->pluck('id')->toArray();
        $conflicts = [];

        foreach ($request->items as $item) {
            $menu = Menu::with('allergens')->find($item['menu_id']);
            if (!$menu) continue;

            $intersect = $menu->allergens->whereIn('id', $userAllergenIds);
            if ($intersect->isNotEmpty()) {
                $conflicts[] = [
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'service_date' => $item['service_date'],
                    'conflicting_allergens' => $intersect->pluck('name')->values()->all(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'has_conflict' => !empty($conflicts),
            'conflicts' => $conflicts,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    /**
     * 4. POST /api/bot/orders
     * Membuat data pesanan resmi dari bot WhatsApp.
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.service_date' => 'required|date',
            'items.*.portion_quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user_id 
            ? User::find($request->user_id)
            : $this->findUserByPhone($request->phone ?? '');

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        return DB::transaction(function () use ($request, $user) {
            $subtotalAmount = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                $qty = (int) $item['portion_quantity'];
                $subtotal = $menu->price * $qty;

                $itemsData[] = [
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'service_date' => $item['service_date'],
                    'portion_quantity' => $qty,
                    'unit_price' => $menu->price,
                    'subtotal' => $subtotal,
                ];

                $subtotalAmount += $subtotal;
            }

            $order = Order::create([
                'user_id' => $user->id,
                'order_code' => 'BOT-' . strtoupper(Str::random(8)),
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => 0,
                'discount_type' => null,
                'total_amount' => $subtotalAmount,
                'payment_status' => 'pending_payment',
            ]);

            foreach ($itemsData as $row) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_id' => $row['menu_id'],
                    'service_date' => $row['service_date'],
                    'portion_quantity' => $row['portion_quantity'],
                    'unit_price' => $row['unit_price'],
                    'subtotal' => $row['subtotal'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat.',
                'order' => [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'customer_name' => $user->name,
                    'customer_phone' => $user->phone,
                    'subtotal_amount' => (float) $order->subtotal_amount,
                    'total_amount' => (float) $order->total_amount,
                    'formatted_total' => 'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                    'payment_status' => $order->payment_status,
                    'items' => $itemsData,
                ],
            ]);
        });
    }

    /**
     * 5. POST /api/bot/orders/{order}/payment-proof
     * Mengunggah foto bukti pembayaran yang dikirimkan customer di WhatsApp.
     */
    public function uploadPaymentProof(Request $request, Order $order)
    {
        $request->validate([
            'payment_proof' => 'required|image|max:4096', // Max 4MB
        ]);

        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        $order->update([
            'payment_proof_path' => $path,
            'payment_status' => 'awaiting_verification',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.',
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'payment_status' => $order->payment_status,
            'payment_proof_url' => url('storage/' . $path),
        ]);
    }
}
