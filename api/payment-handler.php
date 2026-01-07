<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

// Log untuk debugging
$log_file = '../logs/payment_debug.log';
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($log_file, $timestamp . ' ' . $message . "\n", FILE_APPEND);
}

log_message('=== NEW REQUEST ===');

// Validasi user login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['id_pengiriman'])) {
    log_message('ERROR: Missing session data');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Session tidak valid'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$id_pengiriman = $_SESSION['id_pengiriman'];

log_message("User ID: $user_id, Pengiriman ID: $id_pengiriman");

// Validasi upload file
if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
    log_message("ERROR: File upload error");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengunggah file bukti pembayaran'
    ]);
    exit;
}

$file = $_FILES['bukti_pembayaran'];
log_message("File info: " . json_encode([
    'name' => $file['name'],
    'size' => $file['size'],
    'type' => $file['type']
]));

// Validasi tipe file
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($file['type'], $allowed_types)) {
    log_message("ERROR: Invalid file type: " . $file['type']);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Format file harus JPG atau PNG'
    ]);
    exit;
}

// Validasi ukuran file (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    log_message("ERROR: File too large: " . $file['size']);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ukuran file maksimal 5MB'
    ]);
    exit;
}

// Get form data
$nama_pengirim = mysqli_real_escape_string($conn, trim($_POST['nama_pengirim'] ?? ''));
$tanggal_transfer = mysqli_real_escape_string($conn, trim($_POST['tanggal_transfer'] ?? ''));
$catatan_pembayaran = mysqli_real_escape_string($conn, trim($_POST['catatan_pembayaran'] ?? ''));
$payment_method = mysqli_real_escape_string($conn, trim($_POST['payment_method'] ?? ''));

// Validasi input
if (empty($nama_pengirim) || empty($tanggal_transfer) || empty($payment_method)) {
    log_message("ERROR: Missing required fields");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Semua field harus diisi'
    ]);
    exit;
}

// Create uploads directory
$upload_dir = '../uploads/pembayaran';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$file_name = 'pembayaran_' . $user_id . '_' . time() . '.' . $file_ext;
$file_path = $upload_dir . '/' . $file_name;

log_message("Saving file to: $file_path");

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    log_message("ERROR: Failed to move uploaded file");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan file bukti pembayaran'
    ]);
    exit;
}

log_message("File saved successfully");

// Mulai transaction
mysqli_query($conn, 'START TRANSACTION');

try {
    // Get cart items & calculate totals
    $cart_sql = "SELECT c.*, p.nama_produk, p.harga 
                 FROM keranjang c 
                 JOIN produk p ON c.id_produk = p.id_produk 
                 WHERE c.id_user = '$user_id'";
    $cart_result = mysqli_query($conn, $cart_sql);

    if (!$cart_result || mysqli_num_rows($cart_result) == 0) {
        throw new Exception('Keranjang kosong');
    }

    $subtotal = 0;
    $cart_items = [];
    while ($row = mysqli_fetch_assoc($cart_result)) {
        $subtotal += $row['harga'] * $row['qty'];
        $cart_items[] = $row;
    }

    // Check voucher
    $diskon = 0;
    if (isset($_SESSION['voucher_code'])) {
        $voucher_sql = "SELECT * FROM voucher 
                        WHERE kode_voucher = '{$_SESSION['voucher_code']}' 
                        AND status_voucher = 'Aktif'";
        $voucher_result = mysqli_query($conn, $voucher_sql);
        
        if (mysqli_num_rows($voucher_result) > 0) {
            $voucher = mysqli_fetch_assoc($voucher_result);
            $diskon = ($subtotal * $voucher['diskon']) / 100;
        }
    }

    $ongkir = $_SESSION['ongkir'] ?? 0;
    $total_bayar = $subtotal - $diskon + $ongkir;

    log_message("Totals: subtotal=$subtotal, diskon=$diskon, ongkir=$ongkir, total=$total_bayar");

    // Create order
    $no_pesanan = 'ORD-' . strtoupper(uniqid());
    $tgl_sekarang = date('Y-m-d H:i:s');

    $order_sql = "INSERT INTO pesanan (
        id_user, id_pengiriman, no_pesanan, subtotal, diskon, ongkir,
        total_bayar, status_pesanan, metode_pembayaran, bukti_pembayaran,
        tanggal_pesanan
    ) VALUES (
        '$user_id', '$id_pengiriman', '$no_pesanan', '$subtotal', '$diskon', '$ongkir',
        '$total_bayar', 'Menunggu Verifikasi', '$payment_method', '$file_name',
        '$tgl_sekarang'
    )";

    log_message("Insert order query: $order_sql");

    if (!mysqli_query($conn, $order_sql)) {
        throw new Exception('Gagal membuat pesanan: ' . mysqli_error($conn));
    }

    $id_pesanan = mysqli_insert_id($conn);
    log_message("Order ID created: $id_pesanan");

    // Insert order details
    foreach ($cart_items as $item) {
        $id_produk = $item['id_produk'];
        $nama_produk = mysqli_real_escape_string($conn, $item['nama_produk']);
        $harga = $item['harga'];
        $qty = $item['qty'];
        $item_subtotal = $harga * $qty;

        $detail_sql = "INSERT INTO detail_pesanan (
            id_pesanan, id_produk, nama_produk, harga, qty, subtotal
        ) VALUES (
            '$id_pesanan', '$id_produk', '$nama_produk', '$harga', '$qty', '$item_subtotal'
        )";

        if (!mysqli_query($conn, $detail_sql)) {
            throw new Exception('Gagal menyimpan detail pesanan: ' . mysqli_error($conn));
        }

        log_message("Detail pesanan created for product $id_produk");
    }

    // Clear cart
    $delete_cart_sql = "DELETE FROM keranjang WHERE id_user = '$user_id'";
    if (!mysqli_query($conn, $delete_cart_sql)) {
        throw new Exception('Gagal menghapus keranjang: ' . mysqli_error($conn));
    }

    log_message("Cart cleared for user $user_id");

    // Update shipping status
    $update_shipping_sql = "UPDATE pengiriman 
                           SET status_pengiriman = 'Menunggu Verifikasi Pembayaran',
                               tanggal_konfirmasi = '$tgl_sekarang'
                           WHERE id_pengiriman = '$id_pengiriman'";
    
    if (!mysqli_query($conn, $update_shipping_sql)) {
        throw new Exception('Gagal update status pengiriman: ' . mysqli_error($conn));
    }

    log_message("Shipping status updated");

    // Commit transaction
    mysqli_query($conn, 'COMMIT');

    log_message("SUCCESS: Order created with ID $id_pesanan, No $no_pesanan");

    // Clear session
    unset($_SESSION['id_pengiriman']);
    unset($_SESSION['no_pengiriman']);
    unset($_SESSION['subtotal']);
    unset($_SESSION['diskon']);
    unset($_SESSION['ongkir']);
    unset($_SESSION['total_bayar']);
    unset($_SESSION['shipping_method']);
    unset($_SESSION['voucher_code']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dibuat. Silahkan tunggu verifikasi pembayaran',
        'id_pesanan' => $id_pesanan,
        'no_pesanan' => $no_pesanan
    ]);

} catch (Exception $e) {
    // Rollback transaction
    mysqli_query($conn, 'ROLLBACK');
    
    // Delete uploaded file
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    log_message("ERROR: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>