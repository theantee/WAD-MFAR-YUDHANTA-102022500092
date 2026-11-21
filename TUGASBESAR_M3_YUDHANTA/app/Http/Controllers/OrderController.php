<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Riwayat pesanan + status tiap pengiriman (satu pesanan bisa berisi beberapa pengiriman, satu per gudang). */
    public function index(Request $request): View
    {
        $orders = Order::where('id_pengguna', $request->user()->id)
            ->with(['items', 'pengiriman.gudang', 'pengiriman.kurir', 'pengiriman.alamat.zona'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('orders.index', compact('orders'));
    }

    /** Halaman sukses; datanya dikirim lewat flash session dari proses checkout. */
    public function success(): View
    {
        return view('orders.success', ['order' => session('last_order')]);
    }
}
