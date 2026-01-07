<?php
header('Content-Type: application/json');
session_start();
require_once '../config/config.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '../logs/payment_debug.log');

try {
    // Check session
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['id_pengiriman'])) {
        throw new Exception('Session invalid');
    }

    $user_id = $_SESSION['user_id'];
    $id_pengiriman = $_SESSION['id_pengiriman'];

    // Validate input
    $metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');
    $nama_pengirim = trim($_POST['nama_pengirim'] ?? '');
    $tanggal_transfer = trim($_POST['tanggal_transfer'] ?? '');

    if (empty($metode_pembayaran) || empty($nama_pengirim) || empty($tanggal_transfer)) {
        throw new Exception('Semua field harus diisi');
    }

    // Validate file upload
    if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload gagal');
    }

    $file = $_FILES['bukti_pembayaran'];

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File terlalu besar (max 5MB)');
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Format file hanya JPG atau PNG');
    }

    // Create uploads directory if not exists
    $upload_dir = '../uploads/pembayaran';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate filename
    $file_ext = $mime_type === 'image/jpeg' ? 'jpg' : 'png';
    $filename = 'pembayaran_' . $user_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . '/' . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Gagal menyimpan file');
    }

    // Get cart items for order details
    $query_cart = "SELECT k.id_produk, p.nama_produk, p.harga, k.qty 
                   FROM keranjang k 
                   JOIN produk p ON k.id_produk = p.id_produk 
                   WHERE k.id_user = ?";
    $stmt_cart = $conn->prepare($query_cart);
    $stmt_cart->bind_param('i', $user_id);
    $stmt_cart->execute();
    $cart_result = $stmt_cart->get_result();
    $cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);

    if (empty($cart_items)) {
        throw new Exception('Keranjang kosong');
    }

    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['harga'] * $item['qty'];
    }
    $ongkir = $_SESSION['ongkir'] ?? 0;
    $total = $subtotal + $ongkir;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Generate order number
        $no_pesanan = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);

        // Create pesanan
        $query_order = "INSERT INTO pesanan (
                            id_user,
                            id_pengiriman,
                            no_pesanan,
                            subtotal,
                            ongkir,
                            total_bayar,
                            status_pesanan,
                            metode_pembayaran,
                            bukti_pembayaran,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt_order = $conn->prepare($query_order);
        if (!$stmt_order) {
            throw new Exception('Prepare error: ' . $conn->error);
        }

        $status_pesanan = 'Menunggu Verifikasi';
        $stmt_order->bind_param(
            'iisiiiss',
            $user_id,
            $id_pengiriman,
            $no_pesanan,
            $subtotal,
            $ongkir,
            $total,
            $status_pesanan,
            $metode_pembayaran,
            $filename
        );

        if (!$stmt_order->execute()) {
            throw new Exception('Create pesanan error: ' . $stmt_order->error);
        }

        $id_pesanan = $conn->insert_id;

        // Create detail pesanan
        $query_detail = "INSERT INTO detail_pesanan (
                            id_pesanan,
                            id_produk,
                            nama_produk,
                            harga,
                            qty,
                            subtotal,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt_detail = $conn->prepare($query_detail);
        if (!$stmt_detail) {
            throw new Exception('Prepare detail error: ' . $conn->error);
        }

        foreach ($cart_items as $item) {
            $item_subtotal = $item['harga'] * $item['qty'];
            $stmt_detail->bind_param(
                'iisiii',
                $id_pesanan,
                $item['id_produk'],
                $item['nama_produk'],
                $item['harga'],
                $item['qty'],
                $item_subtotal
            );

            if (!$stmt_detail->execute()) {
                throw new Exception('Create detail error: ' . $stmt_detail->error);
            }
        }

        // Clear cart
        $query_clear = "DELETE FROM keranjang WHERE id_user = ?";
        $stmt_clear = $conn->prepare($query_clear);
        $stmt_clear->bind_param('i', $user_id);
        if (!$stmt_clear->execute()) {
            throw new Exception('Clear cart error: ' . $stmt_clear->error);
        }

        // Update pengiriman status
        $query_update = "UPDATE pengiriman SET tanggal_konfirmasi = NOW() WHERE id_pengiriman = ?";
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bind_param('i', $id_pengiriman);
        if (!$stmt_update->execute()) {
            throw new Exception('Update pengiriman error: ' . $stmt_update->error);
        }

        // Commit transaction
        $conn->commit();

        // Clear session
        unset($_SESSION['id_pengiriman']);
        unset($_SESSION['ongkir']);
        unset($_SESSION['subtotal']);

        error_log('[SUCCESS] Order created: id=' . $id_pesanan . ', no=' . $no_pesanan);

        echo json_encode([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat',
            'id_pesanan' => $id_pesanan,
            'no_pesanan' => $no_pesanan
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('[ERROR] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
