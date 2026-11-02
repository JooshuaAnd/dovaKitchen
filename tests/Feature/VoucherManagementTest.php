<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VoucherTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VoucherManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_voucher_template_for_an_rfm_segment(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'voucher-admin@example.com', 'password' => bcrypt('password'), 'is_admin' => true]);
        $customer = User::create(['name' => 'New Customer', 'email' => 'voucher-customer@example.com', 'password' => bcrypt('password')]);

        $this->actingAs($admin)->get(route('admin.vouchers.index'))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Vouchers/Index')
            ->has('segments')
        );

        $this->actingAs($admin)->post(route('admin.vouchers.store'), [
            'title' => 'Promo Sambutan',
            'rfm_segment' => 'Belum Bertransaksi',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'min_purchase' => 30000,
            'valid_until' => now()->addWeek()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('voucher_templates', [
            'title' => 'Promo Sambutan',
            'rfm_segment' => 'Belum Bertransaksi',
            'code_prefix' => 'PERDANA',
            'discount_value' => 15,
        ]);
    }

    public function test_voucher_categories_are_available_without_customer_data(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'category-admin@example.com', 'password' => bcrypt('password'), 'is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.vouchers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('segments', [
                    'Belum Bertransaksi',
                    'Champions',
                    'Loyal Repeaters',
                    'Big Spenders (Baru)',
                    'New / Recent Customers',
                    'At Risk VIP',
                    'At Risk Routine',
                    'Previously Big Spenders',
                    'Hibernating / Pasif',
                ])
            );
    }

    public function test_voucher_list_can_be_filtered_by_rfm_category(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'filter-admin@example.com', 'password' => bcrypt('password'), 'is_admin' => true]);
        VoucherTemplate::create([
            'title' => 'Voucher Champion', 'code_prefix' => 'CHAMP', 'rfm_segment' => 'Champions',
            'discount_type' => 'percentage', 'discount_value' => 20, 'min_purchase' => 50000,
            'valid_until' => now()->addWeek(),
        ]);
        VoucherTemplate::create([
            'title' => 'Voucher Pasif', 'code_prefix' => 'PASIF', 'rfm_segment' => 'Hibernating / Pasif',
            'discount_type' => 'percentage', 'discount_value' => 15, 'min_purchase' => 30000,
            'valid_until' => now()->addWeek(),
        ]);

        $this->actingAs($admin)->get(route('admin.vouchers.index', ['rfm_segment' => 'Champions']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedSegment', 'Champions')
                ->has('templates', 1)
                ->where('templates.0.rfm_segment', 'Champions')
            );
    }
}
