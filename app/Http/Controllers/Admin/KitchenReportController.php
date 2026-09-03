<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenReportController extends Controller
{
    public function index(Request $request)
    {
        // Aggregates total portion quantity grouped by service_date and menu_id.
        $report = OrderItem::join('menus', 'order_items.menu_id', '=', 'menus.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->select(
                'order_items.service_date',
                'menus.id as menu_id',
                'menus.name as menu_name',
                DB::raw('SUM(order_items.portion_quantity) as total_portions')
            )
            ->groupBy('order_items.service_date', 'menus.id', 'menus.name')
            ->orderBy('order_items.service_date')
            ->get();

        return \Inertia\Inertia::render('Admin/KitchenReport/Index', [
            'report' => $report
        ]);
    }
}
