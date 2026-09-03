<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __invoke(Request $request)
    {
        $now = Carbon::now('Asia/Jakarta');
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(4);

        $dailyMenus = DailyMenu::with('menu.allergens')
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->whereHas('menu', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        $menus = collect();
        foreach ($dailyMenus as $dm) {
            $menuData = $dm->menu->toArray();
            $date = Carbon::parse($dm->service_date, 'Asia/Jakarta');
            
            $menuData['service_date'] = $date->toDateString();
            $menuData['service_day'] = strtolower($date->format('l'));
            
            $menus->push($menuData);
        }

        return Inertia::render('Public/Landing', [
            'menus' => $menus,
        ]);
    }
}
