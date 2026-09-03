<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

use Inertia\Inertia;

class OrderVerificationController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')->orderBy('created_at', 'desc')->get();
        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:paid,rejected',
        ]);

        $order->update([
            'payment_status' => $validated['status'],
        ]);

        return back()->with('success', 'Order status updated to ' . $validated['status']);
    }
}
