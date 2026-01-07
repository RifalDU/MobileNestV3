<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// Debug logging function
function debug_log($message) {
    $log_file = __DIR__ . '/../logs/cart_debug.log';
    $dir = dirname($log_file);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) session_start();

debug_log('=== NEW REQUEST ===' . json_encode($_GET));

// Cek User ID (Login atau Guest)
function getCurrentUserId() {
    global $conn;
    
    debug_log('Getting current user ID...');
    debug_log('Session data: ' . json_encode($_SESSION));
    
    // 1. Jika sudah login, pakai ID asli
    $user_info = get_user_info();
    debug_log('get_user_info() returned: ' . json_encode($user_info));
    
    if ($user_info && isset($user_info['id'])) {
        debug_log('Using logged-in user ID: ' . $user_info['id']);
        return $user_info['id'];
    }

    // 2. Jika Guest dan sudah punya session guest_id
    if (isset($_SESSION['guest_id'])) {
        debug_log('Using existing guest ID: ' . $_SESSION['guest_id']);
        return $_SESSION['guest_id'];
    }

    // 3. Jika Guest baru, buatkan user sementara di DB
    debug_log('Creating new guest user...');
    $username = 'guest_' . time() . '_' . rand(100,999);
    $email = $username . '@temp.local';
    $password = password_hash('guest123', PASSWORD_DEFAULT);
    $nama = 'Guest Shopper';
    
    $sql = "INSERT INTO users (username, password, nama_lengkap, email, status_akun, tanggal_daftar) 
            VALUES (?, ?, ?, ?, 'Aktif', NOW())";
            
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        debug_log('Prepare failed: ' . mysqli_error($conn));
        return null;
    }
    
    mysqli_stmt_bind_param($stmt, 'ssss', $username, $password, $nama, $email);
    
    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        $_SESSION['guest_id'] = $new_id;
        debug_log('New guest user created with ID: ' . $new_id);
        mysqli_stmt_close($stmt);
        return $new_id;
    }
    
    debug_log('Failed to create guest user: ' . mysqli_error($conn));
    mysqli_stmt_close($stmt);
    return null;
}

$user_id = getCurrentUserId();
debug_log('Final user_id: ' . $user_id);

if (!$user_id) {
    debug_log('ERROR: Failed to get user_id');
    echo json_encode(['success' => false, 'message' => 'Gagal menginisialisasi user session']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
debug_log('Action: ' . $action);

// --- LOGIC ADD TO CART ---
if ($action === 'add') {
    $input = json_decode(file_get_contents('php://input'), true);
    debug_log('Raw input: ' . file_get_contents('php://input'));
    
    if (!$input) {
        debug_log('ERROR: Invalid JSON input');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $id_produk = (int)($input['id_produk'] ?? 0);
    $qty = (int)($input['quantity'] ?? 1);
    
    debug_log('id_produk: ' . $id_produk . ', qty: ' . $qty . ', user_id: ' . $user_id);

    if ($id_produk <= 0 || $qty <= 0) {
        debug_log('ERROR: Invalid product ID or quantity');
        echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
        exit;
    }

    // Cek stok dengan prepared statement
    $stmt_cek = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id_produk = ?");
    if (!$stmt_cek) {
        debug_log('ERROR: Prepare stock check failed: ' . mysqli_error($conn));
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt_cek, 'i', $id_produk);
    mysqli_stmt_execute($stmt_cek);
    $result_cek = mysqli_stmt_get_result($stmt_cek);
    $prod = mysqli_fetch_assoc($result_cek);
    mysqli_stmt_close($stmt_cek);
    
    debug_log('Product check result: ' . json_encode($prod));

    if (!$prod) {
        debug_log('ERROR: Product not found');
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
        exit;
    }

    if ($qty > (int)$prod['stok']) {
        debug_log('ERROR: Insufficient stock');
        echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $prod['stok']]);
        exit;
    }

    // Cek item di keranjang
    $stmt_check = mysqli_prepare($conn, "SELECT id_keranjang, jumlah FROM keranjang WHERE id_user = ? AND id_produk = ?");
    if (!$stmt_check) {
        debug_log('ERROR: Prepare check cart failed: ' . mysqli_error($conn));
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt_check, 'ii', $user_id, $id_produk);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    $item_exists = mysqli_num_rows($result_check) > 0;
    debug_log('Item exists in cart: ' . ($item_exists ? 'yes' : 'no'));
    
    if ($item_exists) {
        // Item sudah ada, update quantity
        $row = mysqli_fetch_assoc($result_check);
        $new_qty = (int)$row['jumlah'] + $qty;
        
        debug_log('Updating existing item: old_qty=' . $row['jumlah'] . ', new_qty=' . $new_qty);
        
        // Cek apakah total quantity melebihi stok
        if ($new_qty > (int)$prod['stok']) {
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Total quantity exceeds stock');
            echo json_encode(['success' => false, 'message' => 'Total quantity melebihi stok. Stok tersedia: ' . $prod['stok']]);
            exit;
        }
        
        $stmt_update = mysqli_prepare($conn, "UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ?");
        if (!$stmt_update) {
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Prepare update failed: ' . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt_update, 'ii', $new_qty, $row['id_keranjang']);
        $exec = mysqli_stmt_execute($stmt_update);
        
        debug_log('Update executed: ' . ($exec ? 'success' : 'failed') . ', error: ' . mysqli_error($conn));
        
        mysqli_stmt_close($stmt_update);
        
        if ($exec) {
            mysqli_stmt_close($stmt_check);
            debug_log('SUCCESS: Item quantity updated');
            echo json_encode(['success' => true, 'message' => 'Quantity produk di keranjang diperbarui']);
            exit;
        } else {
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Update failed: ' . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Gagal update keranjang: ' . mysqli_error($conn)]);
            exit;
        }
    } else {
        // Item baru, insert
        debug_log('Inserting new item to cart: id_user=' . $user_id . ', id_produk=' . $id_produk . ', qty=' . $qty);
        
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO keranjang (id_user, id_produk, jumlah, tanggal_ditambahkan) VALUES (?, ?, ?, NOW())");
        if (!$stmt_insert) {
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Prepare insert failed: ' . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt_insert, 'iii', $user_id, $id_produk, $qty);
        $exec = mysqli_stmt_execute($stmt_insert);
        
        debug_log('Insert executed: ' . ($exec ? 'success' : 'failed') . ', error: ' . mysqli_error($conn));
        
        $last_id = mysqli_insert_id($conn);
        debug_log('Last insert ID: ' . $last_id);
        
        mysqli_stmt_close($stmt_insert);
        
        if ($exec) {
            mysqli_stmt_close($stmt_check);
            debug_log('SUCCESS: New item added to cart');
            echo json_encode(['success' => true, 'message' => 'Produk ditambahkan ke keranjang']);
            exit;
        } else {
            mysqli_stmt_close($stmt_check);
            debug_log('ERROR: Insert failed: ' . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan ke keranjang: ' . mysqli_error($conn)]);
            exit;
        }
    }
    
    mysqli_stmt_close($stmt_check);
}

// --- LOGIC GET CART ---
if ($action === 'get') {
    debug_log('Getting cart items for user_id: ' . $user_id);
    
    $query = "SELECT 
                k.id_keranjang,
                k.id_user,
                k.id_produk,
                k.jumlah as quantity,
                k.tanggal_ditambahkan,
                p.nama_produk,
                p.harga,
                p.gambar,
                p.stok
              FROM keranjang k 
              JOIN produk p ON k.id_produk = p.id_produk 
              WHERE k.id_user = ?
              ORDER BY k.tanggal_ditambahkan DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        debug_log('ERROR: Prepare get cart failed: ' . mysqli_error($conn));
        echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while($row = mysqli_fetch_assoc($res)) {
        $row['id_produk'] = (int)$row['id_produk'];
        $row['quantity'] = (int)$row['quantity'];
        $row['harga'] = (float)$row['harga'];
        $row['stok'] = (int)$row['stok'];
        $items[] = $row;
    }
    
    debug_log('Cart items count: ' . count($items));
    
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'items' => $items, 'count' => count($items)]);
    exit;
}

// --- LOGIC COUNT ---
if ($action === 'count') {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM keranjang WHERE id_user = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query error']);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true, 'count' => (int)$row['cnt']]);
    exit;
}

// --- LOGIC REMOVE ---
if ($action === 'remove') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_produk = (int)($input['id_produk'] ?? 0);
    
    if ($id_produk <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $stmt = mysqli_prepare($conn, "DELETE FROM keranjang WHERE id_user = ? AND id_produk = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $id_produk);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => $result]);
    exit;
}

// --- LOGIC UPDATE QUANTITY ---
if ($action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_produk = (int)($input['id_produk'] ?? 0);
    $new_qty = (int)($input['quantity'] ?? 0);
    
    if ($id_produk <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    if ($new_qty <= 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM keranjang WHERE id_user = ? AND id_produk = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $id_produk);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $stmt_cek = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id_produk = ?");
        if (!$stmt_cek) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt_cek, 'i', $id_produk);
        mysqli_stmt_execute($stmt_cek);
        $res_cek = mysqli_stmt_get_result($stmt_cek);
        $prod = mysqli_fetch_assoc($res_cek);
        mysqli_stmt_close($stmt_cek);
        
        if (!$prod || $new_qty > (int)$prod['stok']) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
            exit;
        }
        
        $stmt = mysqli_prepare($conn, "UPDATE keranjang SET jumlah = ? WHERE id_user = ? AND id_produk = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, 'iii', $new_qty, $user_id, $id_produk);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action not found']);
?>