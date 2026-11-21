<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/** Keranjang belanja yang disimpan di sesi: [product_id => jumlah]. */
class CartService
{
    private const KEY = 'cart';
    private const MAX_PER_ITEM = 99;

    /** @return array<int,int> */
    public function raw(): array
    {
        $cart = session(self::KEY, []);

        return is_array($cart) ? $cart : [];
    }

    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /** Tambah barang. Mengembalikan pesan untuk ditampilkan ke pembeli. */
    public function add(int $productId, int $quantity): string
    {
        $quantity = $quantity > 0 ? min($quantity, self::MAX_PER_ITEM) : 1;
        $product = Product::active()->find($productId);

        if (! $product || $product->stock <= 0) {
            return 'Produk sedang habis.';
        }

        $cart = $this->raw();
        $cart[$product->id] = min(($cart[$product->id] ?? 0) + $quantity, $product->stock, self::MAX_PER_ITEM);
        session([self::KEY => $cart]);

        return 'Produk berhasil ditambahkan ke keranjang.';
    }

    /** Ubah jumlah banyak barang sekaligus: [product_id => jumlah]. Jumlah <= 0 menghapus barang. */
    public function update(array $quantities): void
    {
        $cart = $this->raw();
        $products = Product::active()->whereIn('id', array_keys($cart))->get()->keyBy('id');

        foreach ($quantities as $id => $qty) {
            $id = (int) $id;
            if (! isset($cart[$id])) {
                continue;
            }
            $qty = filter_var($qty, FILTER_VALIDATE_INT);
            $product = $products->get($id);

            if ($qty === false || $qty <= 0 || ! $product || $product->stock <= 0) {
                unset($cart[$id]);
            } else {
                $cart[$id] = min($qty, $product->stock, self::MAX_PER_ITEM);
            }
        }

        session([self::KEY => $cart]);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        session([self::KEY => $cart]);
    }

    /**
     * Isi keranjang beserta gudang asal tiap barang. Barang yang sudah tidak aktif dibuang dari keranjang.
     * $lock = true memakai SELECT ... FOR UPDATE (harus dipanggil di dalam transaksi checkout).
     *
     * @return Collection<int,CartItem>
     */
    public function items(bool $lock = false): Collection
    {
        $cart = $this->raw();
        if (! $cart) {
            return collect();
        }

        $query = Product::active()->with('gudang.pulau')->whereIn('id', array_keys($cart))->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }
        $products = $query->get();

        if (! $lock) {
            $valid = $products->pluck('id')->all();
            $pruned = array_intersect_key($cart, array_flip($valid));
            if (count($pruned) !== count($cart)) {
                session([self::KEY => $pruned]);
            }
        }

        return $products->map(fn (Product $p) => new CartItem($p, (int) $cart[$p->id]))->values();
    }

    /**
     * Kelompokkan item per gudang asal: [id_gudang => ['gudang' => Gudang, 'items' => Collection, 'subtotal' => float]].
     *
     * @param  Collection<int,CartItem>  $items
     * @return Collection<int,array{gudang:\App\Models\Gudang,items:Collection,subtotal:float}>
     */
    public function groupByGudang(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (CartItem $i) => $i->product->id_gudang)
            ->map(fn (Collection $group) => [
                'gudang' => $group->first()->product->gudang,
                'items' => $group,
                'subtotal' => (float) $group->sum(fn (CartItem $i) => $i->subtotal()),
            ])
            ->sortKeys();
    }
}
