<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class StoreOrderController extends Controller
{
    /**
     * Tampilkan daftar pesanan untuk Admin
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $orders = Order::with(['user', 'siswa', 'items.product', 'items.variant'])
            ->when($search, function ($query, $search) {
                $query->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('siswa', function ($q) use ($search) {
                          $q->where('nama_siswa', 'like', "%{$search}%");
                      });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Tandai pesanan sebagai COMPLETED (sudah diambil/selesai)
     */
    public function complete(Order $order)
    {
        if ($order->status !== 'PAID') {
            return redirect()->back()->with('error', 'Hanya pesanan berstatus LUNAS (PAID) yang dapat diselesaikan.');
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => 'COMPLETED',
            ]);
            
            // Di sini bisa ditambahkan logika lain, misalnya mengirim email notifikasi
            // bahwa barang sudah diambil, atau update inventaris lanjutan jika diperlukan.
        });

        return redirect()->back()->with('success', 'Status pesanan berhasil diperbarui menjadi SELESAI (COMPLETED).');
    }
}
