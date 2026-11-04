<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class StoreOrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['items.product', 'items.variant'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Siswa/Orders/Index', [
            'orders' => $orders,
        ]);
    }
    
    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load(['items.product', 'items.variant']);
        $meta = json_decode($order->notes ?? '{}', true) ?: [];
        if (isset($meta['fee_amount'])) {
            $order->setAttribute('fee_amount', (float) $meta['fee_amount']);
        }

        return Inertia::render('Siswa/Orders/Show', [
            'order' => $order,
        ]);
    }
}
