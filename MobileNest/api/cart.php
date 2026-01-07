<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) session_start();

// Cek User ID (Login atau Guest)
function getCurrentUserId() {
    global $conn;
    
    // 1. Jika sudah login, pakai ID asli
    $user_info = get_user_info();
    if ($user_info && isset($user_info['id'])) {
        return $user_info['id'];
    }

    // 2. Jika Guest dan sudah punya session guest_id
    if (isset($_SESSION['guest_id'])) {
        return $_SESSION['guest_id'];
    }

    // 3. Jika Guest baru, buatkan user sementara di DB
    // Kita isi semua field wajib dengan dummy data agar query tidak gagal
    $username = 'guest_' . time() . '_' . rand(100,999);
    $email = $username . '@temp.local';
    $password = password_hash('guest123', PASSWORD_DEFAULT); // Dummy password
    $nama = 'Guest Shopper';
    
    $sql = "INSERT INTO users (username, password, nama_lengkap, email, status_akun, tanggal_daftar) 
            VALUES (?, ?, ?, ?, 'Aktif', NOW())";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $username, $password, $nama, $email);
    
    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        $_SESSION['guest_id'] = $new_id; // Simpan ID baru di session
        return $new_id;
    }
    
    return null; // Gagal buat user
}

$user_id = getCurrentUserId();

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Gagal menginisialisasi user session']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- LOGIC ADD TO CART ---
if ($action === 'add') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_produk = $input['id_produk'] ?? 0;
    $qty = $input['quantity'] ?? 1;

    // Cek stok dulu
    $cek_stok = mysqli_query($conn, "SELECT stok FROM produk WHERE id_produk = '$id_produk'");
    $prod = mysqli_fetch_assoc($cek_stok);
    if ($qty > $prod['stok']) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
        exit;
    }

    // Cek item di keranjang
    $cek = mysqli_query($conn, "SELECT id_keranjang, jumlah FROM keranjang WHERE id_user = '$user_id' AND id_produk = '$id_produk'");
    
    if (mysqli_num_rows($cek) > 0) {
        $row = mysqli_fetch_assoc($cek);
        $new_qty = $row['jumlah'] + $qty;
        mysqli_query($conn, "UPDATE keranjang SET jumlah = '$new_qty' WHERE id_keranjang = '{$row['id_keranjang']}'");
    } else {
        mysqli_query($conn, "INSERT INTO keranjang (id_user, id_produk, jumlah, tanggal_ditambahkan) VALUES ('$user_id', '$id_produk', '$qty', NOW())");
    }

    echo json_encode(['success' => true, 'message' => 'Produk masuk keranjang']);
    exit;
}

// --- LOGIC GET CART ---
if ($action === 'get') {
    $query = "SELECT k.*, p.nama_produk, p.harga, p.gambar 
              FROM keranjang k 
              JOIN produk p ON k.id_produk = p.id_produk 
              WHERE k.id_user = '$user_id'";
    $res = mysqli_query($conn, $query);
    $items = [];
    while($row = mysqli_fetch_assoc($res)) {
        $items[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $items, 'count' => count($items)]);
    exit;
}

// --- LOGIC COUNT ---
if ($action === 'count') {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM keranjang WHERE id_user = '$user_id'");
    $row = mysqli_fetch_assoc($res);
    echo json_encode(['success' => true, 'count' => $row['cnt']]);
    exit;
}

// --- LOGIC REMOVE ---
if ($action === 'remove') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_produk = $input['id_produk'] ?? 0;
    mysqli_query($conn, "DELETE FROM keranjang WHERE id_user = '$user_id' AND id_produk = '$id_produk'");
    echo json_encode(['success' => true]);
    exit;
}
?>