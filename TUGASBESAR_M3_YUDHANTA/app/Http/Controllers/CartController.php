<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart)
    {
    }

    public function index(): View
    {
        $items = $this->cart->items();
        $total = (float) $items->sum(fn ($i) => $i->subtotal());

        return view('cart.index', ['items' => $items, 'total' => $total, 'jumlah' => $this->cart->count()]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer'],
            'return_to' => ['nullable', 'string'],
        ]);

        $message = $this->cart->add((int) $data['product_id'], (int) ($data['quantity'] ?? 1));

        // Kembali hanya ke halaman internal yang dikenal.
        $returnTo = $data['return_to'] ?? 'cart';
        $target = match (true) {
            $returnTo === 'home' => route('home'),
            (bool) preg_match('/^product:[1-9][0-9]*$/', $returnTo) => route('product.show', (int) substr($returnTo, 8)),
            default => route('cart.index'),
        };

        return redirect($target)->with('flash', $message);
    }

    /** Perbarui jumlah semua barang, atau hapus satu barang (tombol "Hapus" mengirim remove_id). */
    public function update(Request $request): RedirectResponse
    {
        if ($request->filled('remove_id')) {
            $this->cart->remove((int) $request->input('remove_id'));

            return redirect()->route('cart.index')->with('flash', 'Barang dihapus dari keranjang.');
        }

        $quantities = $request->input('qty', []);
        $this->cart->update(is_array($quantities) ? $quantities : []);

        return redirect()->route('cart.index')->with('flash', 'Keranjang diperbarui.');
    }
}
