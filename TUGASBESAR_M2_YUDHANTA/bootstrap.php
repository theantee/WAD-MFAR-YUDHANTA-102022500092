<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/service/database.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function rupiah($value): string {
    return 'Rp' . number_format((float)$value, 0, ',', '.');
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h($_SESSION['csrf_token']) . '">';
}
function csrf_valid(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
function verify_csrf(): void {
    if (!csrf_valid()) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}
// Panjang teks; memakai mbstring bila aktif, jika tidak jatuh ke strlen.
function str_len(string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}
function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}
function product_image(string $path): string {
    // Nama file gambar berasal dari database seed; hindari path di luar folder produk.
    $path = basename($path);
    return 'products/' . rawurlencode($path);
}

// ---------- Akun (dari KirimAja) ----------
function is_logged_in(): bool {
    return !empty($_SESSION['is_login']) && !empty($_SESSION['id_pengguna']);
}
function current_user_id(): int {
    return (int)($_SESSION['id_pengguna'] ?? 0);
}
// Halaman tujuan setelah login hanya boleh halaman internal yang dikenal.
function safe_next(?string $next): string {
    $allowed = ['checkout.php', 'pesanan.php', 'cart.php', 'index.php'];
    return in_array($next, $allowed, true) ? $next : 'index.php';
}
function require_login(string $returnTo): void {
    if (!is_logged_in()) {
        $_SESSION['flash'] = 'Silakan masuk atau daftar dulu untuk melanjutkan.';
        redirect('login.php?next=' . rawurlencode($returnTo));
    }
}
function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
