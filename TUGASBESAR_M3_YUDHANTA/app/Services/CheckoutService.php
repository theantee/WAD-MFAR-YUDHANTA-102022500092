<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\AlamatPengiriman;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pengiriman;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly ShippingService $shipping,
    ) {
    }

    /**
     * Membuat pesanan dari isi keranjang dalam SATU transaksi: stok, pesanan, alamat, dan semua pengiriman
     * berhasil bersama atau dibatalkan bersama. Zona, gudang, dan tarif dihitung ulang di server.
     *
     * @param  array{customer_name:string,phone:string,alamat:string,detail_alamat:?string,latitude:float|string,longitude:float|string}  $data
     * @param  array<int|string,int|string>  $kurirInput  [id_gudang => id_kurir]
     * @return array<string,mixed> ringkasan pesanan untuk halaman sukses
     *
     * @throws CheckoutException
     */
    public function place(User $user, array $data, array $kurirInput): array
    {
        $zona = $this->shipping->findZona((float) $data['latitude'], (float) $data['longitude'])
            ?? throw new CheckoutException('Titik tujuan di luar area layanan.');

        $summary = DB::transaction(function () use ($user, $data, $kurirInput, $zona) {
            $items = $this->cart->items(lock: true);

            if ($items->isEmpty() || $items->count() !== count($this->cart->raw())) {
                throw new CheckoutException('Ada barang yang sudah tidak tersedia. Kembali ke keranjang dan periksa lagi.');
            }
            foreach ($items as $item) {
                if ($item->quantity < 1 || $item->quantity > $item->product->stock) {
                    throw new CheckoutException('Stok "'.$item->product->name.'" tidak mencukupi. Kembali ke keranjang dan periksa jumlahnya.');
                }
            }

            $subtotal = (float) $items->sum(fn (CartItem $i) => $i->subtotal());

            // Ongkir per gudang asal.
            $ongkir = 0.0;
            $pengiriman = [];
            foreach ($this->cart->groupByGudang($items) as $idGudang => $group) {
                $idKurir = (int) ($kurirInput[$idGudang] ?? 0);
                $tarif = $idKurir > 0 ? $this->shipping->quote((int) $idGudang, $zona['id_zona'], $idKurir) : null;
                if (! $tarif) {
                    throw new CheckoutException('Pilih kurir yang tersedia untuk pengiriman dari '.$group['gudang']->nama_gudang.'.');
                }
                $ongkir += $tarif->harga;
                $pengiriman[(int) $idGudang] = ['gudang' => $group['gudang'], 'tarif' => $tarif];
            }
            $total = $subtotal + $ongkir;
            $detail = trim((string) ($data['detail_alamat'] ?? ''));

            // 1) alamat tujuan (titik peta + zona tujuan)
            $alamat = AlamatPengiriman::create([
                'id_pengguna' => $user->id,
                'id_zona' => $zona['id_zona'],
                'alamat' => $data['alamat'],
                'detail_alamat' => $detail,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);

            // 2) pesanan (subtotal + jumlah ongkir = total)
            $order = Order::create([
                'order_number' => 'SKU-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'id_pengguna' => $user->id,
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'address' => $detail !== '' ? $data['alamat'].' ('.$detail.')' : $data['alamat'],
                'subtotal' => $subtotal,
                'ongkir' => $ongkir,
                'total' => $total,
                'status' => 'Menunggu pembayaran',
            ]);

            // 3) rincian barang (dengan gudang asal) + pengurangan stok
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product->id,
                    'id_gudang' => $item->product->id_gudang,
                    'product_name' => $item->product->name,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal(),
                ]);

                $updated = Product::whereKey($item->product->id)
                    ->where('stock', '>=', $item->quantity)
                    ->decrement('stock', $item->quantity);
                if ($updated !== 1) {
                    throw new CheckoutException('Stok berubah. Silakan ulangi checkout.');
                }
            }

            // 4) satu pengiriman per gudang asal
            $rincian = [];
            foreach ($pengiriman as $idGudang => $p) {
                /** @var \App\Models\TarifOngkir $t */
                $t = $p['tarif'];
                Pengiriman::create([
                    'id_pesanan' => $order->id,
                    'id_alamat' => $alamat->id_alamat,
                    'id_pengguna' => $user->id,
                    'id_gudang' => $idGudang,
                    'id_kurir' => $t->id_kurir,
                    'tarif_ongkir' => $t->harga,
                    'biaya_antar_pulau' => $t->biaya_antar_pulau,
                    'status' => 'Menunggu',
                ]);
                $rincian[] = [
                    'nama_gudang' => $p['gudang']->nama_gudang,
                    'nama_pulau' => $p['gudang']->pulau->nama_pulau,
                    'nama_kurir' => $t->nama_kurir,
                    'estimasi' => $t->estimasi,
                    'jarak_km' => $t->jarak_km,
                    'biaya_dasar' => $t->biaya_dasar,
                    'biaya_jarak' => $t->biaya_jarak,
                    'biaya_antar_pulau' => $t->biaya_antar_pulau,
                    'harga' => $t->harga,
                ];
            }

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'subtotal' => $subtotal,
                'ongkir' => $ongkir,
                'total' => $total,
                'nama_zona' => $zona['nama_zona'],
                'provinsi' => $zona['provinsi'],
                'nama_pulau' => $zona['nama_pulau'],
                'pengiriman' => $rincian,
            ];
        });

        $this->cart->clear();   // hanya setelah transaksi berhasil

        return $summary;
    }
}
