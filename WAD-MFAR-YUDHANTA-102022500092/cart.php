<?php
require_once __DIR__ . '/bootstrap.php';
include('service/database.php');
$pageTitle = 'Keranjang';
$items = [];
$total = 0;
foreach ($_SESSION['cart'] as $id => $quantity) {
    $stmt = mysqli_prepare($db, 'SELECT id, name, price, stock, image_path, emoji FROM products WHERE id = ? AND active = 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$product) { unset($_SESSION['cart'][$id]); continue; }
    $product['quantity'] = (int)$quantity;
    $product['subtotal'] = (float)$product['price'] * (int)$quantity;
    $total += $product['subtotal'];
    $items[] = $product;
}
include('layout/header.php');
?>
<div class="page-wrap">
    <div class="page-heading"><p class="section-eyebrow">PESANANMU</p><h1>Keranjang belanja</h1><p>Periksa barang dan jumlah sebelum checkout.</p></div>
    <?php if (!empty($_SESSION['flash'])): ?><div class="notice"><?= h($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?>
    <?php if (!$items): ?>
        <div class="empty-state"><div class="empty-icon">🛒</div><h2>Keranjang masih kosong</h2><p>Tambahkan barang dari katalog untuk mulai berbelanja.</p><a class="button button-primary" href="index.php">Lihat produk</a></div>
    <?php else: ?>
    <div class="cart-layout"><section class="cart-panel"><form action="cart_action.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="update">
        <?php foreach ($items as $item): ?><div class="cart-item"><img class="cart-product-image" src="<?= h(product_image($item['image_path'])) ?>" alt=""><div class="cart-product-details"><h3><?= h($item['name']) ?></h3><p><?= rupiah($item['price']) ?> / barang</p><span class="muted">Stok: <?= (int)$item['stock'] ?></span></div><label class="quantity-label">Jumlah<input type="number" name="qty[<?= (int)$item['id'] ?>]" min="0" max="<?= max(1, (int)$item['stock']) ?>" value="<?= (int)$item['quantity'] ?>"></label><div class="cart-subtotal"><strong><?= rupiah($item['subtotal']) ?></strong><button class="text-button" type="submit" name="remove_id" value="<?= (int)$item['id'] ?>">Hapus</button></div></div><?php endforeach; ?>
        <button class="button button-secondary" type="submit">Perbarui jumlah</button>
    </form></section><aside class="summary-card"><h2>Ringkasan</h2><div class="summary-line"><span>Jumlah barang</span><strong><?= (int)array_sum($_SESSION['cart']) ?></strong></div><div class="summary-line"><span>Subtotal</span><strong><?= rupiah($total) ?></strong></div><p class="summary-note">Ongkir dihitung saat checkout, setelah kamu menandai lokasi tujuan di peta.</p><div class="summary-total"><span>Total</span><strong><?= rupiah($total) ?></strong></div><a href="checkout.php" class="button button-primary button-full">Lanjut checkout</a><a href="index.php" class="back-link">← Lanjut belanja</a></aside></div>
    <?php endif; ?>
</div>
<?php include('layout/footer.html'); ?>