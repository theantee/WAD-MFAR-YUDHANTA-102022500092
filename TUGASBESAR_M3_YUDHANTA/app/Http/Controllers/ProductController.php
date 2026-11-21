<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /** Katalog + pencarian (?q=) + filter kategori (?category=). */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $like = '%'.addcslashes($search, '%_\\').'%';

        $products = Product::active()
            ->with('gudang')
            ->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'like', $like)->orWhere('description', 'like', $like)
            ))
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->orderBy('id')
            ->get();

        return view('home', compact('products', 'search', 'category'));
    }

    public function show(int $id): View
    {
        $product = Product::active()->with('gudang')->findOrFail($id);

        return view('product.show', compact('product'));
    }
}
