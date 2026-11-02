<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOrderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_sees_orders_with_uploaded_payment_proof(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin-orders@example.com', 'password' => bcrypt('password'), 'is_admin' => true]);
        $customer = User::create(['name' => 'Customer', 'email' => 'customer-orders@example.com', 'password' => bcrypt('password')]);

        Order::create(['user_id' => $customer->id, 'order_code' => 'ORD-PENDING', 'subtotal_amount' => 10000, 'discount_amount' => 0, 'total_amount' => 10000, 'payment_status' => 'pending_payment']);
        Order::create(['user_id' => $customer->id, 'order_code' => 'ORD-PROOF', 'subtotal_amount' => 20000, 'discount_amount' => 0, 'total_amount' => 20000, 'payment_status' => 'awaiting_verification']);

        $this->actingAs($admin)->get(route('admin.orders.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index')
            ->has('orders', 1)
            ->where('orders.0.order_code', 'ORD-PROOF')
        );
    }
}
