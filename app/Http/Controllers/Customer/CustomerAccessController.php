<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerAccessController extends Controller
{
    public function start(Request $request)
    {
        return Inertia::render('Customer/Access/Start');
    }

    public function signedStart(Request $request, User $user)
    {
        abort_if($user->is_admin || !$user->phone, 404);

        $request->session()->put('customer_access_target_user_id', $user->id);
        $request->session()->forget('customer_access_phone');

        return redirect()->route('customer-access.start');
    }

    public function identify(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = User::normalizePhone($validated['phone']);

        if (!$phone || strlen($phone) < 10 || strlen($phone) > 15) {
            return back()->withErrors([
                'phone' => 'Masukkan nomor WhatsApp yang valid.',
            ]);
        }

        $targetUserId = $request->session()->get('customer_access_target_user_id');

        if ($targetUserId) {
            $user = User::whereKey($targetUserId)->where('is_admin', false)->first();

            if (!$user || User::normalizePhone($user->phone) !== $phone) {
                return back()->withErrors([
                    'phone' => 'Nomor WhatsApp tidak sesuai dengan link akses ini.',
                ]);
            }

            return $this->loginCustomer($request, $user, 'catalog');
        }

        if (User::where('phone', $phone)->where('is_admin', false)->exists()) {
            return back()->withErrors([
                'phone' => 'Nomor ini sudah terdaftar. Silakan gunakan link khusus yang dikirim admin.',
            ]);
        }

        $request->session()->put('customer_access_phone', $phone);

        return redirect()->route('customer-access.register');
    }

    public function registerForm(Request $request)
    {
        if (!$request->session()->has('customer_access_phone')) {
            return redirect()->route('customer-access.start');
        }

        return Inertia::render('Customer/Access/Register', [
            'phone' => $request->session()->get('customer_access_phone'),
        ]);
    }

    public function register(Request $request)
    {
        $phone = $request->session()->get('customer_access_phone');

        if (!$phone) {
            return redirect()->route('customer-access.start');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if (User::where('phone', $phone)->exists()) {
            $request->session()->forget('customer_access_phone');

            return redirect()->route('customer-access.start')->withErrors([
                'phone' => 'Nomor WhatsApp sudah terdaftar. Silakan gunakan link akses customer lama.',
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'email' => null,
            'password' => null,
        ]);

        return $this->loginCustomer($request, $user, 'dietary-setup');
    }

    private function loginCustomer(Request $request, User $user, string $route)
    {
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget([
            'customer_access_target_user_id',
            'customer_access_phone',
        ]);

        return redirect()->route($route);
    }
}
