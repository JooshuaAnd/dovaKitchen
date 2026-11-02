<?php

namespace Tests\Feature;

use App\Models\Allergen;
use App\Models\DailyMenu;
use App\Models\Menu;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogDietaryWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_keeps_conflicting_menu_and_marks_it_not_recommended(): void
    {
        Carbon::setTestNow('2026-10-26 12:00:00');

        $user = User::create([
            'name' => 'Pelanggan Dietary',
            'email' => 'dietary@example.com',
            'password' => bcrypt('password'),
        ]);
        $allergen = Allergen::create(['name' => 'Kacang', 'category' => 'Kacang-kacangan']);
        $user->allergens()->attach($allergen);

        $safeMenu = Menu::create(['name' => 'Ayam Panggang', 'price' => 30000, 'is_active' => true]);
        $warningMenu = Menu::create(['name' => 'Sate Kacang', 'price' => 30000, 'is_active' => true]);
        $warningMenu->allergens()->attach($allergen);

        DailyMenu::create(['menu_id' => $safeMenu->id, 'service_date' => '2026-10-28']);
        DailyMenu::create(['menu_id' => $warningMenu->id, 'service_date' => '2026-10-28']);

        $this->actingAs($user)->get(route('catalog'))->assertInertia(fn (Assert $page) => $page
            ->component('Customer/Catalog/Index')
            ->has('menus', 2)
            ->where('menus.0.is_recommended', true)
            ->where('menus.1.is_recommended', false)
            ->where('menus.1.dietary_warnings.0', 'Kacang')
        );

        Carbon::setTestNow();
    }

    public function test_checkout_requires_confirmation_for_a_dietary_warning(): void
    {
        $user = User::create([
            'name' => 'Pelanggan Dietary',
            'email' => 'checkout-dietary@example.com',
            'password' => bcrypt('password'),
        ]);
        $allergen = Allergen::create(['name' => 'Susu', 'category' => 'Produk susu']);
        $user->allergens()->attach($allergen);
        $menu = Menu::create(['name' => 'Pasta Keju', 'price' => 30000, 'is_active' => true]);
        $menu->allergens()->attach($allergen);

        $payload = [
            'items' => [[
                'menu_id' => $menu->id,
                'service_date' => now()->addDays(2)->toDateString(),
                'portion_quantity' => 1,
            ]],
        ];

        $this->actingAs($user)->post(route('checkout.store'), $payload)
            ->assertSessionHasErrors('dietary_warnings_confirmed');

        $this->actingAs($user)->post(route('checkout.store'), $payload + ['dietary_warnings_confirmed' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }
}
