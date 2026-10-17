<?php
    session_start();
    require_once __DIR__ . '/bootstrap.php';
    include("service/database.php");
    $pageTitle = 'Beranda';
    $searchInput = $_GET['q'] ?? '';
    $categoryInput = $_GET['category'] ?? '';
    $search = is_string($searchInput) ? trim($searchInput) : '';
    $category = is_string($categoryInput) ? trim($categoryInput) : '';

    $sql = "SELECT id, name, description, price, stock, category, condition_label,
                size_label, likes, image_path, emoji
            FROM products
            WHERE active = 1
            AND (? = '' OR name LIKE CONCAT('%', ?, '%') OR description LIKE CONCAT('%', ?, '%'))
            AND (? = '' OR category = ?)
            ORDER BY id ASC";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'sssss', $search, $search, $search, $category, $category);
    mysqli_stmt_execute($stmt);
    $products = mysqli_stmt_get_result($stmt);
    
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ShopKu</title>

    
</head>

<body>

    <!-- HEADER -->
    <?php include "layout/header.php" ?>

    <!--Hero Section-->
<section class="hero">


    <div class="hero-content">

        <h1>
            Mau jual baju bekas atau barang bekas?
        </h1>

        <button class="hero-button">
            Mulai jualan sekarang
        </button>

        <a href="#" class="hero-link">
            Mau tahu gimana caranya? Cek di sini
        </a>

    </div>

</section>

<!-- PRODUCT SECTION -->
<section class="product-section" id="produk">
    <div class="product-container">
        <div class="product-header">
            <div><p class="section-eyebrow">PILIHAN SHOPKU</p><h2><?= $search !== '' ? 'Hasil pencarian' : ($category !== '' ? 'Kategori: ' . h($category) : 'Temukan barang favoritmu') ?></h2></div>
            <?php if ($search !== '' || $category !== ''): ?><a class="see-all" href="index.php">Hapus filter ×</a><?php else: ?><span class="product-count">Barang pilihan dengan harga menarik</span><?php endif; ?>
        </div>
        <?php if (!empty($_SESSION['flash'])): ?><div class="notice"><?= h($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?>
        <div class="product-grid">
        <?php if (mysqli_num_rows($products) === 0): ?>
            <div class="no-results"><span>⌕</span><h3>Barang tidak ditemukan</h3><p>Coba kata kunci atau kategori lain.</p><a href="index.php" class="button button-primary">Lihat semua barang</a></div>
        <?php else: ?>
            <?php while ($product = mysqli_fetch_assoc($products)): ?>
            <article class="product-card">
                <div class="product-image">
                    <a class="product-link" href="product.php?id=<?= (int)$product['id'] ?>" aria-label="Lihat <?= h($product['name']) ?>">
                        <img src="<?= h(product_image($product['image_path'])) ?>" alt="<?= h($product['name']) ?>" loading="lazy">
                    </a>
                    <span class="condition-badge"><?= h($product['condition_label']) ?></span>
                    <button class="like-button" type="button" aria-pressed="false" data-id="<?= (int)$product['id'] ?>" data-count="<?= (int)$product['likes'] ?>" aria-label="Sukai <?= h($product['name']) ?>">
                        <span class="heart-icon">♡</span><span class="like-count"><?= (int)$product['likes'] ?></span>
                    </button>
                </div>
                <div class="product-info">
                    <a class="product-name-link" href="product.php?id=<?= (int)$product['id'] ?>"><h3><?= h($product['name']) ?></h3></a>
                    <p class="product-condition-text"><?= h($product['category']) ?><?= $product['size_label'] !== '' && $product['size_label'] !== '-' ? ' · Ukuran ' . h($product['size_label']) : '' ?></p>
                    <div class="product-bottom">
                        <strong class="product-price"><?= rupiah($product['price']) ?></strong>
                        <?php if ((int)$product['stock'] > 0): ?>
                        <form action="cart_action.php" method="post" >
                            <input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><input type="hidden" name="quantity" value="1"><input type="hidden" name="return_to" value="index.php">
                            <button type="submit" aria-label="Tambahkan ke keranjang">+ 🛒</button>
                        </form>
                        <?php else: ?><span class="sold-out">Habis</span><?php endif; ?>
                    </div>
                    <p class="stock-note"><?= (int)$product['stock'] > 0 ? 'Stok ' . (int)$product['stock'] : 'Stok habis' ?></p>
                </div>
            </article>  
            <?php endwhile; ?>
        <?php endif; ?>
        </div>
        
    </div>
</section>
<!-- PRODUCT GRID -->
<div class="product-grid">

    
</div>
<!-- SEE MORE BUTTON -->
<div class="see-more-container">
    <button class="see-more-button">
        Lihat lebih banyak
    </button>
</div>

        <!-- CATEGORIES AND BRANDS -->
        <div class="discovery-section">


            <!-- MOST POPULAR CATEGORIES -->
            <div class="discovery-box">

                <h2>
                    Most popular categories
                </h2>

                <div class="tag-container">

                    <a href="#" class="category-tag">
                        Wanita
                    </a>

                    <a href="#" class="category-tag">
                        Pria
                    </a>

                    <a href="#" class="category-tag">
                        Sepatu
                    </a>

                    <a href="#" class="category-tag">
                        Tas
                    </a>

                    <a href="#" class="category-tag">
                        Tops
                    </a>

                    <a href="#" class="category-tag">
                        Jackets
                    </a>

                    <a href="#" class="category-tag">
                        Dresses
                    </a>

                    <a href="#" class="category-tag">
                        Elektronik
                    </a>

                    <a href="#" class="category-tag">
                        Beranda
                    </a>

                    <a href="#" class="category-tag">
                        Olahraga
                    </a>

                </div>

            </div>


            <!-- BEST SELLING BRANDS -->
            <div class="discovery-box">

                <h2>
                    Best selling brands
                </h2>

                <div class="tag-container">

                    <a href="#" class="brand-tag">
                        Nike
                    </a>

                    <a href="#" class="brand-tag">
                        Adidas
                    </a>

                    <a href="#" class="brand-tag">
                        Uniqlo
                    </a>

                    <a href="#" class="brand-tag">
                        H&M
                    </a>

                    <a href="#" class="brand-tag">
                        Zara
                    </a>

                    <a href="#" class="brand-tag">
                        Levi's
                    </a>

                    <a href="#" class="brand-tag">
                        Converse
                    </a>

                    <a href="#" class="brand-tag">
                        Vans
                    </a>

                    <a href="#" class="brand-tag">
                        Puma
                    </a>

                    <a href="#" class="brand-tag">
                        New Balance
                    </a>

                </div>

            </div>


        </div>

    </div>

</section>
<script>
const likeButtons = document.querySelectorAll(".like-button");

likeButtons.forEach(function(button) {

    button.addEventListener("click", function(event) {

        // Jangan sampai klik heart membuka halaman produk
        event.preventDefault();
        event.stopPropagation();

        // Ambil jumlah like sekarang
        const countElement = button.querySelector(".like-count");

        // Ambil angka
        let currentCount = parseInt(countElement.textContent);

        // Cek apakah produk sudah di-like
        if (button.classList.contains("liked")) {

            // UNLIKE
            currentCount--;

            // Tampilkan jumlah baru
            countElement.textContent = currentCount;

            // Hapus status liked
            button.classList.remove("liked");

            // Kembalikan icon menjadi outline
            button.querySelector(".heart-icon").textContent = "♡";

        } else {

            // LIKE
            currentCount++;

            // Tampilkan jumlah baru
            countElement.textContent = currentCount;

            // Tambahkan status liked
            button.classList.add("liked");

            // Ubah icon menjadi hati penuh
            button.querySelector(".heart-icon").textContent = "♥";
        }

    });

});
</script>
</body>

</html>