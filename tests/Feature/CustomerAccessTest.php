<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_customer_can_enter_from_a_signed_whatsapp_link(): void
    {
        $customer = User::create([
            'name' => 'Customer Lama',
            'phone' => '628123456789',
            'email' => 'lama@example.com',
            'password' => bcrypt('password'),
        ]);

        $url = URL::temporarySignedRoute(
            'customer-access.signed',
            now()->addMinutes(5),
            ['user' => $customer->id],
            false
        );

        $this->get($url)->assertRedirect(route('customer-access.start'));

        $this->post(route('customer-access.identify'), [
            'phone' => '0812-3456-789',
        ])->assertRedirect(route('catalog'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_signed_link_cannot_be_used_with_another_phone(): void
    {
        $customer = User::create([
            'name' => 'Customer Lama',
            'phone' => '628123456789',
            'email' => 'lama@example.com',
            'password' => bcrypt('password'),
        ]);

        $url = URL::temporarySignedRoute(
            'customer-access.signed',
            now()->addMinutes(5),
            ['user' => $customer->id],
            false
        );

        $this->get($url);

        $this->from(route('customer-access.start'))
            ->post(route('customer-access.identify'), ['phone' => '0812-0000-0000'])
            ->assertRedirect(route('customer-access.start'))
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
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
