<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\DailyMenu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WeeklyManagementController extends Controller
{
    public function index(Request $request)
    {
        $dateParam = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        $startOfWeek = Carbon::parse($dateParam, 'Asia/Jakarta')->startOfWeek(); // Monday
        $endOfWeek = $startOfWeek->copy()->addDays(4); // Friday

        // Fetch daily menus for the week
        $dailyMenus = DailyMenu::with('menu')
            ->whereBetween('service_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        // Fetch all active menus to be assigned
        $availableMenus = Menu::where('is_active', true)->orderBy('name')->get();

        $weekDates = [];
        for ($i = 0; $i < 5; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            
            // Pluck the related menu models
            $menusForDay = $dailyMenus->filter(function($dm) use ($date) {
                return $dm->service_date->toDateString() === $date->toDateString();
            })->map(function($dm) {
                return $dm->menu;
            })->values()->toArray();

            $weekDates[] = [
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'), // e.g. "Monday"
                'menus' => $menusForDay,
            ];
        }

        return Inertia::render('Admin/WeeklyManagement/Index', [
            'weekDates' => $weekDates,
            'availableMenus' => $availableMenus,
            'currentWeekStart' => $startOfWeek->toDateString(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'menu_ids' => 'array',
            'menu_ids.*' => 'exists:menus,id',
        ]);

        $date = $validated['date'];
        $menuIds = $validated['menu_ids'] ?? [];

        // Delete existing daily menus for this date
        DailyMenu::where('service_date', $date)->delete();

        // Insert new ones
        $inserts = [];
        $now = Carbon::now();
        foreach ($menuIds as $menuId) {
            $inserts[] = [
                'menu_id' => $menuId,
                'service_date' => $date,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($inserts)) {
            DailyMenu::insert($inserts);
        }

        return redirect()->back()->with('success', 'Schedule updated successfully.');
    }
}
