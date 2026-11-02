<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_customer_can_enter_from_the_general_portal_link(): void
    {
        $customer = User::create([
            'name' => 'Customer Lama',
            'phone' => '628123456789',
            'email' => 'lama@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post(route('customer-access.identify'), [
            'phone' => '0812-3456-789',
        ])->assertRedirect(route('catalog'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_new_customer_is_created_without_email_or_password_then_sent_to_dietary_setup(): void
    {
        $this->post(route('customer-access.identify'), [
            'phone' => '0813 1111 2222',
        ])->assertRedirect(route('customer-access.register.form'));

        $this->post(route('customer-access.register'), [
            'name' => 'Customer Baru',
        ])->assertRedirect(route('dietary-setup'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Customer Baru',
            'phone' => '6281311112222',
            'email' => null,
            'password' => null,
        ]);
    }
}
