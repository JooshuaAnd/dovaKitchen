<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderHistoryController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->with(['orderItems.menu'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Customer/History/Index', [
            'orders' => $orders
        ]);
    }
}
