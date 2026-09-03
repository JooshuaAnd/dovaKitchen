<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\DailyMenu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $userAllergens = $request->user()->allergens->pluck('id')->toArray();

        $now = Carbon::now('Asia/Jakarta');
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(4);

        $dailyMenus = DailyMenu::with('menu.allergens')
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->whereHas('menu', function ($query) use ($userAllergens) {
                $query->where('is_active', true)
                    ->when(!empty($userAllergens), function ($q) use ($userAllergens) {
                        $q->whereDoesntHave('allergens', function ($q2) use ($userAllergens) {
                            $q2->whereIn('allergens.id', $userAllergens);
                        });
                    });
            })
            ->get();

        $menus = collect();
        foreach ($dailyMenus as $dm) {
            $menuData = $dm->menu->toArray();
            $date = Carbon::parse($dm->service_date, 'Asia/Jakarta');
            
            $menuData['daily_menu_id'] = $dm->id;
            $menuData['service_date'] = $date->toDateString();
            $menuData['service_day'] = strtolower($date->format('l'));
            
            $cutOffTime = $date->copy()->subDay()->setTime(17, 0, 0);
            $menuData['is_orderable'] = $now->lessThan($cutOffTime);
            
            $menus->push($menuData);
        }

        // 4. Compute CRM Birthday Cashback status
        $user = $request->user();
        $isBirthday = false;
        $hasUsedThisYear = false;
        $isClaimedInSession = session()->get('claimed_birthday_voucher', false);

        if ($user && $user->date_of_birth) {
            $dob = Carbon::parse($user->date_of_birth, 'Asia/Jakarta');
            if ($dob->month === $now->month && $dob->day === $now->day) {
                $isBirthday = true;
                $hasUsedThisYear = ($user->birthday_reward_claimed_year === $now->year);
            }
        }

        $birthdayReward = [
            'is_birthday' => $isBirthday,
            'has_used_this_year' => $hasUsedThisYear,
            'is_claimed' => $isClaimedInSession,
            'discount_percentage' => 20,
            'min_purchase' => 50000,
            'user_name' => $user ? $user->name : '',
        ];

        return Inertia::render('Customer/Catalog/Index', [
            'menus' => $menus,
            'birthdayReward' => $birthdayReward,
        ]);
    }
}
