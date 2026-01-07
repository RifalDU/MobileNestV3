<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

// Log untuk debugging
$log_file = '../logs/shipping_debug.log';
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($log_file, $timestamp . ' ' . $message . "\n", FILE_APPEND);
}

log_message('=== NEW REQUEST ===');

// Check user login
if (!isset($_SESSION['user_id'])) {
    log_message('ERROR: User not logged in');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
log_message("User ID: $user_id");

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
log_message("Input data: " . json_encode($input));

// Validate required fields
$required_fields = ['nama_penerima', 'no_telepon', 'email', 'provinsi', 'kota', 'kecamatan', 'kode_pos', 'alamat_lengkap', 'metode_pengiriman'];

foreach ($required_fields as $field) {
    if (empty($input[$field] ?? '')) {
        log_message("ERROR: Missing field: $field");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Field '{$field}' harus diisi"
        ]);
        exit;
    }
}

// Validate email format
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    log_message("ERROR: Invalid email format: " . $input['email']);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Format email tidak valid'
    ]);
    exit;
}

// Validate phone number (10-13 digits)
if (!preg_match('/^[0-9]{10,13}$/', preg_replace('/[^0-9]/', '', $input['no_telepon']))) {
    log_message("ERROR: Invalid phone: " . $input['no_telepon']);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Nomor telepon harus 10-13 digit'
    ]);
    exit;
}

// Validate postal code (5-10 digits)
if (!preg_match('/^[0-9]{5,10}$/', $input['kode_pos'])) {
    log_message("ERROR: Invalid postal code: " . $input['kode_pos']);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Kode pos harus 5-10 digit'
    ]);
    exit;
}

// Shipping costs
$shipping_costs = [
    'regular' => 20000,
    'express' => 50000,
    'same_day' => 100000
];

if (!isset($shipping_costs[$input['metode_pengiriman']])) {
    log_message("ERROR: Invalid shipping method: " . $input['metode_pengiriman']);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Metode pengiriman tidak valid'
    ]);
    exit;
}

$ongkir = $shipping_costs[$input['metode_pengiriman']];
log_message("Ongkir calculated: $ongkir for method: " . $input['metode_pengiriman']);

// Escape strings
$nama_penerima = mysqli_real_escape_string($conn, $input['nama_penerima']);
$no_telepon = mysqli_real_escape_string($conn, $input['no_telepon']);
$email = mysqli_real_escape_string($conn, $input['email']);
$provinsi = mysqli_real_escape_string($conn, $input['provinsi']);
$kota = mysqli_real_escape_string($conn, $input['kota']);
$kecamatan = mysqli_real_escape_string($conn, $input['kecamatan']);
$kode_pos = mysqli_real_escape_string($conn, $input['kode_pos']);
$alamat_lengkap = mysqli_real_escape_string($conn, $input['alamat_lengkap']);
$metode_pengiriman = mysqli_real_escape_string($conn, $input['metode_pengiriman']);
$catatan = mysqli_real_escape_string($conn, $input['catatan'] ?? '');

// Generate unique shipping number
$no_pengiriman = 'PGR-' . strtoupper(uniqid());

// Get current datetime
$tgl_sekarang = date('Y-m-d H:i:s');

// Insert to database
$sql = "INSERT INTO pengiriman (
    id_user,
    no_pengiriman,
    nama_penerima,
    no_telepon,
    email,
    provinsi,
    kota,
    kecamatan,
    kode_pos,
    alamat_lengkap,
    metode_pengiriman,
    ongkir,
    catatan,
    status_pengiriman,
    tanggal_pengiriman,
    tanggal_konfirmasi
) VALUES (
    '$user_id',
    '$no_pengiriman',
    '$nama_penerima',
    '$no_telepon',
    '$email',
    '$provinsi',
    '$kota',
    '$kecamatan',
    '$kode_pos',
    '$alamat_lengkap',
    '$metode_pengiriman',
    '$ongkir',
    '$catatan',
    'Menunggu Verifikasi Pembayaran',
    '$tgl_sekarang',
    '$tgl_sekarang'
)";

log_message("SQL: $sql");

if (mysqli_query($conn, $sql)) {
    $id_pengiriman = mysqli_insert_id($conn);
    log_message("SUCCESS: Pengiriman created with ID: $id_pengiriman, No: $no_pengiriman");

    // Save to SESSION for next step
    $_SESSION['id_pengiriman'] = $id_pengiriman;
    $_SESSION['no_pengiriman'] = $no_pengiriman;
    $_SESSION['ongkir'] = $ongkir;
    $_SESSION['shipping_method'] = $metode_pengiriman;

    // Get cart info for session
    $cart_sql = "SELECT SUM(c.qty * p.harga) as subtotal FROM keranjang c JOIN produk p ON c.id_produk = p.id_produk WHERE c.id_user = '$user_id'";
    $cart_result = mysqli_query($conn, $cart_sql);
    $cart_data = mysqli_fetch_assoc($cart_result);
    
    $_SESSION['subtotal'] = $cart_data['subtotal'] ?? 0;
    $_SESSION['diskon'] = $_SESSION['diskon'] ?? 0;
    $_SESSION['total_bayar'] = $_SESSION['subtotal'] - $_SESSION['diskon'] + $ongkir;

    log_message("Session saved: id_pengiriman=$id_pengiriman, ongkir=$ongkir");

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Data pengiriman berhasil disimpan',
        'id_pengiriman' => $id_pengiriman,
        'no_pengiriman' => $no_pengiriman,
        'ongkir' => $ongkir
    ]);
} else {
    log_message("ERROR: Database insert failed: " . mysqli_error($conn));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan data pengiriman: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>