<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly ShippingService $shipping,
    ) {
    }

    public function show(): View|RedirectResponse
    {
        $grup = $this->cart->groupByGudang($this->cart->items());
        if ($grup->isEmpty()) {
            return redirect()->route('cart.index');
        }

        return view('checkout.show', [
            'grup' => $grup,
            'subtotal' => (float) $grup->sum('subtotal'),
            'zonaPeta' => $this->shipping->zonas(),
        ]);
    }

    /**
     * JSON: titik tujuan di peta + isi keranjang (dibaca server dari sesi) ->
     * zona tujuan dan, untuk tiap gudang asal, pilihan kurir beserta rincian tarif.
     */
    public function quote(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Klik peta untuk memilih titik tujuan.'], 422);
        }

        $zona = $this->shipping->findZona((float) $request->input('latitude'), (float) $request->input('longitude'));
        if (! $zona) {
            return response()->json(['success' => false, 'message' => 'Titik berada di luar area layanan. Pilih titik di dalam area berwarna pada peta.'], 422);
        }

        $grup = $this->cart->groupByGudang($this->cart->items());
        if ($grup->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Keranjang kosong.'], 422);
        }

        $hasil = [];
        foreach ($grup as $idGudang => $g) {
            $opsi = $this->shipping->options((int) $idGudang, $zona['id_zona']);
            if ($opsi->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Belum ada tarif dari '.$g['gudang']->nama_gudang.' ke '.$zona['nama_zona'].'.'], 422);
            }
            $hasil[] = [
                'id_gudang' => (int) $idGudang,
                'nama_gudang' => $g['gudang']->nama_gudang,
                'nama_pulau' => $g['gudang']->pulau->nama_pulau,
                'antar_pulau' => $g['gudang']->id_pulau !== $zona['id_pulau'],   // beda kelompok pulau -> ada biaya antar pulau
                'opsi' => $opsi->map(fn ($t) => $this->shipping->optionToArray($t))->values(),
            ];
        }

        return response()->json(['success' => true, 'data' => ['zona' => $zona, 'grup' => $hasil]]);
    }

    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate(
            [
                'customer_name' => ['required', 'string', 'max:120'],
                'phone' => ['required', 'string', 'max:30'],
                'alamat' => ['required', 'string', 'max:255'],
                'detail_alamat' => ['nullable', 'string', 'max:1000'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'kurir' => ['nullable', 'array'],
            ],
            [
                'customer_name.required' => 'Nama, telepon, dan alamat wajib diisi.',
                'phone.required' => 'Nama, telepon, dan alamat wajib diisi.',
                'alamat.required' => 'Nama, telepon, dan alamat wajib diisi.',
                '*.max' => 'Ada isian yang terlalu panjang.',
                'latitude.required' => 'Klik peta untuk menandai titik tujuan pengiriman.',
                'longitude.required' => 'Klik peta untuk menandai titik tujuan pengiriman.',
                'latitude.numeric' => 'Klik peta untuk menandai titik tujuan pengiriman.',
                'longitude.numeric' => 'Klik peta untuk menandai titik tujuan pengiriman.',
            ],
        );

        try {
            $summary = $checkout->place($request->user(), $data, $data['kurir'] ?? []);
        } catch (CheckoutException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('orders.success')->with('last_order', $summary);
    }
}
