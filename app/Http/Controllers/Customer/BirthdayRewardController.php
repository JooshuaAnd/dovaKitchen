<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BirthdayRewardController extends Controller
{
    public function claim(Request $request)
    {
        $user = $request->user();
        $today = Carbon::now('Asia/Jakarta');

        if (!$user->date_of_birth) {
            return back()->with('error', 'Silakan lengkapi tanggal lahir di profil Anda terlebih dahulu.');
        }

        $dob = Carbon::parse($user->date_of_birth, 'Asia/Jakarta');

        // Verify if today is the user's birthday
        if ($dob->month !== $today->month || $dob->day !== $today->day) {
            return back()->with('error', 'Kado ulang tahun hanya dapat diklaim tepat pada hari ulang tahun Anda.');
        }

        // Verify if already claimed/used this year
        if ($user->birthday_reward_claimed_year === $today->year) {
            return back()->with('error', 'Anda sudah menggunakan kado voucher ulang tahun untuk tahun ini.');
        }

        // Activate claimed status in session for the cart/checkout
        session()->put('claimed_birthday_voucher', true);

        return back()->with('success', '🎉 Selamat Ulang Tahun! Kado voucher diskon 20% berhasil diklaim dan siap digunakan pada pesanan Anda.');
    }
}
