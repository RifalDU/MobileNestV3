<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['id_pengiriman'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$id_pengiriman = $_SESSION['id_pengiriman'];

// Get pengiriman data
$query = "SELECT * FROM pengiriman WHERE id_pengiriman = ? AND id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $id_pengiriman, $user_id);
$stmt->execute();
$pengiriman = $stmt->get_result()->fetch_assoc();

if (!$pengiriman) {
    header('Location: pengiriman.php');
    exit();
}

// Get cart items
$query_cart = "SELECT k.*, p.nama_produk, p.harga FROM keranjang k 
              JOIN produk p ON k.id_produk = p.id_produk 
              WHERE k.id_user = ?";
$stmt_cart = $conn->prepare($query_cart);
$stmt_cart->bind_param('i', $user_id);
$stmt_cart->execute();
$cart_items = $stmt_cart->get_result()->fetch_all(MYSQLI_ASSOC);

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['harga'] * $item['qty'];
}

$ongkir = $pengiriman['ongkir'];
$total = $subtotal + $ongkir;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - MobileNest</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            margin-bottom: 30px;
            font-size: 24px;
            color: #222;
        }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        /* Payment Methods */
        .payment-methods {
            margin: 20px 0;
        }

        .payment-methods h3 {
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .method-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .method-item:hover {
            border-color: #007bff;
            background: #f8f9ff;
        }

        .method-item input[type="radio"] {
            margin-right: 10px;
            cursor: pointer;
        }

        .method-label {
            flex: 1;
            cursor: pointer;
        }

        /* File Upload */
        .upload-area {
            margin: 15px 0;
        }

        .upload-box {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8f9fa;
        }

        .upload-box:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .upload-box.has-file {
            border-color: #28a745;
            background: #f1f8f4;
        }

        .upload-box p {
            margin: 10px 0;
            font-size: 13px;
            color: #666;
        }

        #file-preview {
            margin-top: 10px;
            font-size: 13px;
            color: #28a745;
            font-weight: 500;
        }

        #fileInput {
            display: none;
        }

        /* Sidebar */
        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .sidebar h3 {
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .sidebar-section {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .sidebar-section:last-child {
            border-bottom: none;
        }

        .sidebar-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 13px;
        }

        .sidebar-row.total {
            font-weight: 600;
            font-size: 16px;
            color: #222;
            margin-top: 10px;
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .error {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }

        .success {
            color: #155724;
            font-size: 13px;
            margin-top: 5px;
            padding: 10px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }

        .loading {
            display: none;
            font-size: 13px;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💳 Konfirmasi Pembayaran</h1>

        <div class="main-content">
            <!-- Form Section -->
            <form id="paymentForm" class="form-section" enctype="multipart/form-data">
                <!-- Shipping Info -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; margin-bottom: 10px; font-weight: 600;">📦 Info Pengiriman</h3>
                    <div style="font-size: 13px; line-height: 1.6;">
                        <p><strong>Ke:</strong> <?php echo htmlspecialchars($pengiriman['nama_penerima']); ?></p>
                        <p><strong>Alamat:</strong> <?php echo htmlspecialchars($pengiriman['alamat_lengkap']); ?></p>
                        <p><strong>Metode:</strong> <?php echo ucfirst($pengiriman['metode_pengiriman']); ?></p>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="payment-methods">
                    <h3>Metode Pembayaran</h3>
                    
                    <label class="method-item">
                        <input type="radio" name="metode_pembayaran" value="bank_transfer" checked>
                        <div class="method-label">🏦 Transfer Bank</div>
                    </label>

                    <label class="method-item">
                        <input type="radio" name="metode_pembayaran" value="ewallet">
                        <div class="method-label">📱 E-Wallet (OVO, GoPay, Dana)</div>
                    </label>

                    <label class="method-item">
                        <input type="radio" name="metode_pembayaran" value="credit_card">
                        <div class="method-label">💳 Kartu Kredit</div>
                    </label>

                    <label class="method-item">
                        <input type="radio" name="metode_pembayaran" value="cod">
                        <div class="method-label">🚚 Bayar di Tempat (COD)</div>
                    </label>
                </div>

                <!-- Upload Bukti -->
                <div class="form-group">
                    <label>Upload Bukti Pembayaran *</label>
                    <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                        <p>📸 Klik atau drag file ke sini</p>
                        <p style="font-size: 12px; color: #999;">JPG atau PNG, maksimal 5MB</p>
                        <div id="file-preview"></div>
                    </div>
                    <input type="file" id="fileInput" name="bukti_pembayaran" accept=".jpg,.jpeg,.png">
                </div>

                <!-- Nama Pengirim -->
                <div class="form-group">
                    <label>Nama Pengirim (sesuai bukti) *</label>
                    <input type="text" name="nama_pengirim" required>
                </div>

                <!-- Tanggal Transfer -->
                <div class="form-group">
                    <label>Tanggal Transfer *</label>
                    <input type="date" name="tanggal_transfer" required>
                </div>

                <div class="buttons">
                    <button type="button" class="btn-secondary" onclick="history.back()">← Kembali</button>
                    <button type="submit" class="btn-primary">Konfirmasi Pesanan →</button>
                </div>

                <div class="loading" id="loading">⏳ Processing...</div>
                <div id="message"></div>
            </form>

            <!-- Sidebar -->
            <div class="sidebar">
                <h3>📋 Ringkasan Pesanan</h3>

                <div class="sidebar-section">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="sidebar-row">
                            <span><?php echo htmlspecialchars($item['nama_produk']); ?> x<?php echo $item['qty']; ?></span>
                            <span>Rp <?php echo number_format($item['harga'] * $item['qty']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-row">
                        <span>Subtotal</span>
                        <span>Rp <?php echo number_format($subtotal); ?></span>
                    </div>
                    <div class="sidebar-row">
                        <span>Ongkir</span>
                        <span>Rp <?php echo number_format($ongkir); ?></span>
                    </div>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-row total">
                        <span>Total Bayar</span>
                        <span>Rp <?php echo number_format($total); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const uploadBox = document.querySelector('.upload-box');
        const filePreview = document.getElementById('file-preview');
        const form = document.getElementById('paymentForm');
        const messageDiv = document.getElementById('message');
        const loadingDiv = document.getElementById('loading');

        // File input events
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const size = (file.size / 1024 / 1024).toFixed(2);
                
                if (file.size > 5 * 1024 * 1024) {
                    filePreview.textContent = '❌ File terlalu besar (max 5MB)';
                    fileInput.value = '';
                    uploadBox.classList.remove('has-file');
                    return;
                }

                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    filePreview.textContent = '❌ Format hanya JPG atau PNG';
                    fileInput.value = '';
                    uploadBox.classList.remove('has-file');
                    return;
                }

                filePreview.textContent = `✅ ${file.name} (${size}MB)`;
                uploadBox.classList.add('has-file');
            }
        });

        // Drag & drop
        uploadBox.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadBox.style.borderColor = '#007bff';
        });

        uploadBox.addEventListener('dragleave', () => {
            uploadBox.style.borderColor = '#ddd';
        });

        uploadBox.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadBox.style.borderColor = '#ddd';
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        });

        // Form submit
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!fileInput.files.length) {
                messageDiv.className = 'error';
                messageDiv.textContent = 'Harap upload bukti pembayaran';
                return;
            }

            loadingDiv.style.display = 'block';
            messageDiv.textContent = '';

            const formData = new FormData(form);

            try {
                const response = await fetch('../api/payment-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    messageDiv.className = 'success';
                    messageDiv.textContent = '✅ Pesanan berhasil dibuat! Redirecting...';
                    setTimeout(() => {
                        window.location.href = 'order-success.php?id=' + result.id_pesanan;
                    }, 2000);
                } else {
                    messageDiv.className = 'error';
                    messageDiv.textContent = result.message || 'Terjadi kesalahan';
                    loadingDiv.style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.className = 'error';
                messageDiv.textContent = 'Error: ' + error.message;
                loadingDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>
