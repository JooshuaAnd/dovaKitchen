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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.menu_id' => ['required', 'exists:menus,id'],
            'items.*.service_date' => ['required', 'date'],
            'items.*.portion_quantity' => ['required', 'integer', 'min:1'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $now = \Carbon\Carbon::now('Asia/Jakarta');
            $subtotalAmount = 0;
            $orderItemsData = [];
            
            // Calculate total and prepare order items
            foreach ($validated['items'] as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                $subtotal = $menu->price * $item['portion_quantity'];
                
                $orderItemsData[] = [
                    'menu_id' => $menu->id,
                    'service_date' => $item['service_date'],
                    'portion_quantity' => $item['portion_quantity'],
                    'unit_price' => $menu->price,
                    'subtotal' => $subtotal,
                ];
                
                $subtotalAmount += $subtotal;
            }

            // Check CRM Birthday Voucher eligibility
            $discountAmount = 0;
            $discountType = null;
            $isBirthday = false;

            if ($user->date_of_birth) {
                $dob = \Carbon\Carbon::parse($user->date_of_birth, 'Asia/Jakarta');
                $isBirthday = ($dob->month === $now->month && $dob->day === $now->day);
            }

            $hasClaimedInSession = session()->get('claimed_birthday_voucher', false);
            $hasNotUsedThisYear = ($user->birthday_reward_claimed_year !== $now->year);

            if ($isBirthday && $hasClaimedInSession && $hasNotUsedThisYear && $subtotalAmount >= 50000) {
                // 20% Birthday Discount
                $discountAmount = round($subtotalAmount * 0.20);
                $discountType = 'Kado Ulang Tahun (Diskon 20%)';

                // Record that user used reward for current year
                $user->update([
                    'birthday_reward_claimed_year' => $now->year
                ]);
                session()->forget('claimed_birthday_voucher');
            }

            $finalTotal = $subtotalAmount - $discountAmount;

            // Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'order_code' => 'ORD-' . strtoupper(Str::random(8)),
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'discount_type' => $discountType,
                'total_amount' => $finalTotal,
                'payment_status' => 'pending_payment',
            ]);

            // Create Order Items
            foreach ($orderItemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }

            return redirect()->route('orders.show', $order->id)->with('success', 'Order created successfully.');
        });
    }
}
