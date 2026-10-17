<?php
require_once 'bootstrap.php';
include 'service/database.php';

// Hapus barang dari keranjang
if (isset($_POST['remove_id'])) {
    $id = $_POST['remove_id'];

    unset($_SESSION['cart'][$id]);

    $_SESSION['flash'] = 'Barang dihapus dari keranjang.';
    redirect('cart.php');
}

// Ambil aksi dari form
$action = $_POST['action'] ?? '';

// Tambah produk ke keranjang
if ($action == 'add') {

    $id = $_POST['product_id'];
    $qty = $_POST['quantity'];

    // Jika qty kosong, jadikan 1
    if ($qty <= 0) {
        $qty = 1;
    }

    // Ambil stok produk
    $query = mysqli_query(
        $db,
        "SELECT stock FROM products WHERE id = $id"
    );

    $product = mysqli_fetch_assoc($query);

    if ($product && $product['stock'] > 0) {

        // Jumlah yang sudah ada di keranjang
        $current = $_SESSION['cart'][$id] ?? 0;

        // Tambahkan jumlah ke keranjang
        $_SESSION['cart'][$id] = $current + $qty;

        // Jangan melebihi stok
        if ($_SESSION['cart'][$id] > $product['stock']) {
            $_SESSION['cart'][$id] = $product['stock'];
        }

        $_SESSION['flash'] = 'Produk berhasil ditambahkan ke keranjang.';
    } else {
        $_SESSION['flash'] = 'Produk habis.';
    }

    redirect('cart.php');
}

// Update jumlah barang di keranjang
if ($action == 'update') {

    foreach ($_POST['qty'] as $id => $qty) {

        // Jika qty 0 atau kurang, hapus dari keranjang
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        // Ambil stok terbaru
        $query = mysqli_query(
            $db,
            "SELECT stock FROM products WHERE id = $id"
        );

        $product = mysqli_fetch_assoc($query);

        if ($product) {

            // Jika qty melebihi stok
            if ($qty > $product['stock']) {
                $qty = $product['stock'];
            }

            $_SESSION['cart'][$id] = $qty;
        }
    }

    $_SESSION['flash'] = 'Keranjang diperbarui.';
    redirect('cart.php');
}

redirect('index.php');