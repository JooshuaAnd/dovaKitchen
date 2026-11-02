<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\VoucherTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserControllerRfmTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_management_and_receive_rfm_data(): void
    {
        $admin = User::create([
            'name' => 'Admin Dova',
            'email' => 'admin@dovakitchen.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $customer1 = User::create([
            'name' => 'Kustomer Satu',
            'email' => 'kustomer1@gmail.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $customer2 = User::create([
            'name' => 'Kustomer Dua',
            'email' => 'kustomer2@gmail.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        // Create a paid order for customer 1
        Order::create([
            'user_id' => $customer1->id,
            'order_code' => 'ORD-001',
            'subtotal_amount' => 150000,
            'discount_amount' => 0,
            'total_amount' => 150000,
            'payment_status' => 'paid',
        ]);

        // Create a pending order for customer 2 (unpaid)
        Order::create([
            'user_id' => $customer2->id,
            'order_code' => 'ORD-002',
            'subtotal_amount' => 200000,
            'discount_amount' => 0,
            'total_amount' => 200000,
            'payment_status' => 'pending_payment',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->has('users', 2)
            ->has('summary')
            ->where('summary.total_customers', 2)
            ->where('summary.total_transactions', 1)
            ->where('summary.total_revenue', 150000)
            ->has('users.0.rfm', fn (Assert $rfm) => $rfm
                ->hasAll([
                    'recency_last_order_at',
                    'recency_days',
                    'recency_label',
                    'frequency_paid_orders',
                    'monetary_total_spent',
                    'monetary_avg_order',
                    'score_r',
                    'score_f',
                    'score_m',
                    'rfm_code',
                    'rfm_total_score',
                    'segment',
                    'segment_description',
                    'crm_recommendation',
                    'scoring_method',
                    'top_ingredients',
                    'top_categories',
                    'top_menus',
                ])
                ->etc()
            )
        );
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::create([
            'name' => 'Admin Dova',
            'email' => 'admin2@dovakitchen.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $customer = User::create([
            'name' => 'Hapus Saya',
            'email' => 'hapus@gmail.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $customer->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_admin_can_issue_and_delete_crm_voucher(): void
    {
        $admin = User::create([
            'name' => 'Admin Dova',
            'email' => 'admin_v@dovakitchen.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $customer = User::create([
            'name' => 'Customer VIP',
            'email' => 'vip@gmail.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        // Issue voucher
        $postData = [
            'code' => 'CHAMP-TEST',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_purchase' => 50000,
            'valid_until' => now()->addDays(7)->format('Y-m-d'),
            'rfm_segment' => 'Champions',
            'title' => 'Reward Loyalitas',
            'notes' => 'Voucher apresiasi pelanggan aktif',
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.vouchers.store', $customer->id), $postData);
        $response->assertRedirect();

        $this->assertDatabaseHas('vouchers', [
            'user_id' => $customer->id,
            'code' => 'CHAMP-TEST',
            'discount_value' => 20,
            'is_used' => false,
        ]);

        $voucher = \App\Models\Voucher::where('code', 'CHAMP-TEST')->first();

        // Delete voucher
        $delResponse = $this->actingAs($admin)->delete(route('admin.vouchers.destroy', $voucher->id));
        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
    }

    public function test_admin_can_assign_only_a_matching_rfm_voucher_template(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'template-admin@example.com', 'password' => bcrypt('password'), 'is_admin' => true]);
        $customer = User::create(['name' => 'Customer', 'email' => 'template-customer@example.com', 'password' => bcrypt('password')]);
        $template = VoucherTemplate::create([
            'title' => 'Voucher Sambutan', 'code_prefix' => 'WELCOME', 'rfm_segment' => 'Belum Bertransaksi',
            'discount_type' => 'percentage', 'discount_value' => 15, 'min_purchase' => 0,
            'valid_until' => now()->addWeek(),
        ]);

        $this->actingAs($admin)->post(route('admin.users.vouchers.assign', [$customer, $template]))->assertRedirect();

        $this->assertDatabaseHas('vouchers', [
            'user_id' => $customer->id,
            'title' => 'Voucher Sambutan',
            'rfm_segment' => 'Belum Bertransaksi',
        ]);

        $otherTemplate = VoucherTemplate::create([
            'title' => 'Voucher Champion', 'code_prefix' => 'CHAMP', 'rfm_segment' => 'Champions',
            'discount_type' => 'percentage', 'discount_value' => 20, 'min_purchase' => 0,
            'valid_until' => now()->addWeek(),
        ]);

        $this->actingAs($admin)->post(route('admin.users.vouchers.assign', [$customer, $otherTemplate]))->assertStatus(422);
        $this->assertDatabaseMissing('vouchers', ['user_id' => $customer->id, 'title' => 'Voucher Champion']);
    }

    public function test_customer_can_redeem_voucher_at_checkout(): void
    {
        $customer = User::create([
            'name' => 'Customer Belanja',
            'email' => 'belanja@gmail.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $menu = \App\Models\Menu::create([
            'name' => 'Ayam Penyet Special',
            'price' => 50000,
            'category' => 'Main Course',
            'description' => 'Enak',
            'is_active' => true,
        ]);

        $voucher = \App\Models\Voucher::create([
            'user_id' => $customer->id,
            'code' => 'DISC20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_purchase' => 50000,
            'valid_until' => now()->addDays(7),
            'is_used' => false,
        ]);

        $checkoutData = [
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'service_date' => now()->addDays(2)->format('Y-m-d'),
                    'portion_quantity' => 2, // 2 x 50.000 = 100.000
                ]
            ],
        ];

        $response = $this->actingAs($customer)->post(route('checkout.store'), $checkoutData);
        $response->assertRedirect();

        $order = Order::where('user_id', $customer->id)->firstOrFail();
        $this->actingAs($customer)->put(route('orders.voucher.apply', $order), ['voucher_code' => 'DISC20'])
            ->assertRedirect();

        // Order total: 100.000 - 20% (20.000) = 80.000
        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'subtotal_amount' => 100000,
            'discount_amount' => 20000,
            'total_amount' => 80000,
        ]);

        // Voucher should be marked used
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'is_used' => true,
        ]);
    }

    public function test_checkout_keeps_normal_total_without_a_voucher(): void
    {
        $customer = User::create([
            'name' => 'Kustomer Baru',
            'email' => 'kustomer_baru@gmail.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $menu = \App\Models\Menu::create([
            'name' => 'Paket Catering Perdana',
            'price' => 50000,
            'category' => 'Main Course',
            'description' => 'Paket Perdana',
            'is_active' => true,
        ]);

        $checkoutData = [
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'service_date' => now()->addDays(2)->format('Y-m-d'),
                    'portion_quantity' => 1, // 1 x 50.000 = 50.000
                ]
            ],
            // No voucher code provided!
        ];

        $response = $this->actingAs($customer)->post(route('checkout.store'), $checkoutData);
        $response->assertRedirect();

        // Tanpa voucher, total pada order tetap harga normal.
        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'subtotal_amount' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'discount_type' => null,
        ]);
    }
}
