<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="ecommerce.css">
    <?= $extraHead ?? '' ?>
</head>
<body>
    <header>

        <!-- TOP HEADER -->
        <div class="top-header">

            <!-- LOGO -->
            <a class="logo" href="index.php" aria-label="ShopKu beranda">ShopKu</a>

            <!-- SEARCH -->
            <div class="search-container">

                <button class="catalog-button">
                    Katalog
                    <span>▼</span>
                </button>

                <div class="search-box">
                    <span class="search-icon">⌕</span>

                    <input
                        type="text"
                        placeholder="Cari Barang"
                    >
                </div>

            </div>

            <!-- RIGHT MENU -->
            <div class="header-actions">
                <
                <div class="auth-buttons">
                    <?php if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true): ?>
                    <a class="auth-link" href="pesanan.php">Pesanan</a>
                    <span class="divider">|</span>
                    <span class="auth-user" title="Akun yang sedang masuk">Halo, <?= ($_SESSION['username']) ?></span>
                    <span class="divider">|</span>
                    <form class="logout-form" action="logout.php" method="post"><button type="submit" class="login-button">Keluar</button></form>
                <?php else: ?>
                    <a class="auth-link" href="register.php">Daftar</a>
                    <span class="divider">|</span>
                    <a class="auth-link" href="login.php">Masuk</a>
                <?php endif; ?>
                </div>

                <button class="sell-button">
                    Jual Barang-mu sekarang!
                </button>

                <button class="help-button">
                    ?
                </button>
                <a class="cart-top-link" href="cart.php" aria-label="Keranjang belanja">🛒 <span class="cart-count"><?= array_sum($_SESSION['cart'] ?? []) ?></span></a>

                <button class="language-button">
                    ID
                    <span>▼</span>
                </button>

            </div>

        </div>


        <!-- CATEGORY NAVIGATION -->
        <nav class="category-nav">

            <a href="#">Wanita</a>

            <a href="#">Pria</a>

            <a href="#">Anak-anak</a>

            <a href="#">Home</a>

            <a href="#">Elektronik</a>

            <a href="#">Buku, Musik, Blu-Ray Film</a>

            <a href="#">Hobi & Barang Koleksi</a>

            <a href="#">Olahraga</a>

        </nav>

    </header>

    
</body>
</html>