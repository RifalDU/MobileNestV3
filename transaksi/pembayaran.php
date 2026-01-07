<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once '../config.php';

// Check if user logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_SESSION['id_pengiriman'])) {
    header('Location: pengiriman.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id_pengiriman = $_SESSION['id_pengiriman'];

// Get pengiriman data
$pengiriman_sql = "SELECT * FROM pengiriman WHERE id_pengiriman = '$id_pengiriman' AND id_user = '$user_id'";
$pengiriman_result = mysqli_query($conn, $pengiriman_sql);
$pengiriman_data = mysqli_fetch_assoc($pengiriman_result);

if (!$pengiriman_data) {
    header('Location: pengiriman.php');
    exit;
}

// Get cart items & calculate totals
$cart_sql = "SELECT c.*, p.nama_produk, p.harga 
             FROM keranjang c 
             JOIN produk p ON c.id_produk = p.id_produk 
             WHERE c.id_user = '$user_id'";
$cart_result = mysqli_query($conn, $cart_sql);

$subtotal = 0;
$cart_items = [];
while ($row = mysqli_fetch_assoc($cart_result)) {
    $subtotal += $row['harga'] * $row['qty'];
    $cart_items[] = $row;
}

// Get voucher discount if exists
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

$ongkir = $pengiriman_data['ongkir'] ?? 0;
$total_bayar = $subtotal - $diskon + $ongkir;

// Payment methods
$payment_methods = [
    'bank_transfer' => [
        'name' => 'Transfer Bank',
        'icon' => '🏦',
        'desc' => 'Transfer ke rekening bank kami'
    ],
    'ewallet' => [
        'name' => 'E-Wallet',
        'icon' => '📱',
        'desc' => 'GCash, GoPay, OVO, Dana'
    ],
    'credit_card' => [
        'name' => 'Kartu Kredit',
        'icon' => '💳',
        'desc' => 'Visa, Mastercard, JCB'
    ],
    'cod' => [
        'name' => 'Bayar di Tempat (COD)',
        'icon' => '🚚',
        'desc' => 'Bayar saat barang tiba'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - MobileNest</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light: #f3f4f6;
            --dark: #1f2937;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-main {
            max-width: 1200px;
            margin: 0 auto;
        }

        .checkout-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }

        .step.active .step-number {
            background: var(--primary);
            color: white;
        }

        .step.completed .step-number {
            background: var(--success);
            color: white;
        }

        .step-title {
            font-size: 14px;
            color: #6b7280;
        }

        .step.active .step-title {
            color: var(--primary);
            font-weight: bold;
        }

        .row-main {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-section h3 {
            margin-bottom: 25px;
            color: var(--dark);
            font-size: 20px;
            font-weight: 600;
        }

        .payment-info-box {
            background: #f0f9ff;
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .payment-info-box h4 {
            margin-bottom: 10px;
            color: var(--primary);
            font-size: 14px;
            font-weight: 600;
        }

        .payment-info-box p {
            margin: 0;
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }

        .countdown-timer {
            background: #fef3c7;
            border-left: 4px solid var(--warning);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }

        .countdown-timer h4 {
            color: var(--warning);
            margin-bottom: 10px;
            font-size: 14px;
        }

        .countdown-timer .timer {
            font-size: 32px;
            font-weight: bold;
            color: var(--danger);
            font-family: 'Courier New', monospace;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .payment-method {
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .payment-method input[type="radio"] {
            display: none;
        }

        .payment-method.selected {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .payment-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .payment-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 14px;
        }

        .payment-desc {
            font-size: 11px;
            color: #6b7280;
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .file-upload-area.active {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .file-upload-area input[type="file"] {
            display: none;
        }

        .file-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .file-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .file-hint {
            color: #9ca3af;
            font-size: 12px;
        }

        .file-preview {
            margin-top: 15px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            display: none;
        }

        .file-preview.active {
            display: block;
        }

        .preview-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preview-icon {
            font-size: 24px;
        }

        .preview-details {
            flex: 1;
        }

        .preview-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .preview-size {
            color: #6b7280;
            font-size: 12px;
        }

        .preview-remove {
            background: var(--danger);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }

        .sidebar {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
        }

        .sidebar h4 {
            margin-bottom: 20px;
            color: var(--dark);
            font-weight: 600;
        }

        .shipping-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .shipping-info-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 13px;
            color: #6b7280;
        }

        .shipping-label {
            font-weight: 600;
        }

        .shipping-value {
            text-align: right;
            color: var(--dark);
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 14px;
            color: #6b7280;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: bold;
            color: var(--dark);
            padding: 15px 0;
            border-top: 2px solid #e5e7eb;
            margin-top: 15px;
        }

        .summary-row.total span:last-child {
            color: var(--primary);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover:not(:disabled) {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            width: 100%;
            padding: 12px;
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        @media (max-width: 768px) {
            .row-main {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <!-- Header -->
        <div class="checkout-header">
            <h1 style="color: var(--dark); margin-bottom: 20px;">MobileNest Checkout</h1>
            <div class="progress-steps">
                <div class="step completed">
                    <div class="step-number">✓</div>
                    <div class="step-title">Keranjang</div>
                </div>
                <div class="step completed">
                    <div class="step-number">✓</div>
                    <div class="step-title">Pengiriman</div>
                </div>
                <div class="step active">
                    <div class="step-number">3</div>
                    <div class="step-title">Pembayaran</div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-title">Selesai</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row-main">
            <!-- Form Section -->
            <div class="form-section">
                <h3>Konfirmasi Pembayaran</h3>

                <div class="error-message" id="errorMsg"></div>
                <div class="success-message" id="successMsg"></div>

                <!-- Countdown Timer -->
                <div class="countdown-timer">
                    <h4>⏰ Waktu Pembayaran Terbatas</h4>
                    <div class="timer" id="countdown">24:00:00</div>
                    <p style="margin-top: 10px; font-size: 12px; color: #92400e;">Selesaikan pembayaran dalam 24 jam</p>
                </div>

                <!-- Pengiriman Summary -->
                <div class="payment-info-box">
                    <h4>📍 Informasi Pengiriman</h4>
                    <p><strong>Penerima:</strong> <?php echo htmlspecialchars($pengiriman_data['nama_penerima']); ?></p>
                    <p><strong>Alamat:</strong> <?php echo htmlspecialchars($pengiriman_data['alamat_lengkap']); ?>, <?php echo htmlspecialchars($pengiriman_data['kota']); ?>, <?php echo htmlspecialchars($pengiriman_data['provinsi']); ?> <?php echo htmlspecialchars($pengiriman_data['kode_pos']); ?></p>
                    <p><strong>Metode:</strong> <?php echo ucfirst(str_replace('_', ' ', $pengiriman_data['metode_pengiriman'])); ?></p>
                </div>

                <form id="paymentForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Metode Pembayaran *</label>
                        <div class="payment-methods">
                            <?php foreach ($payment_methods as $key => $method): ?>
                                <div class="payment-method" data-method="<?php echo $key; ?>">
                                    <input type="radio" id="<?php echo $key; ?>" name="payment_method" value="<?php echo $key; ?>">
                                    <label for="<?php echo $key; ?>" style="cursor: pointer; margin: 0;">
                                        <div class="payment-icon"><?php echo $method['icon']; ?></div>
                                        <div class="payment-name"><?php echo $method['name']; ?></div>
                                        <div class="payment-desc"><?php echo $method['desc']; ?></div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nama_pengirim">Nama Pengirim *</label>
                        <input type="text" id="nama_pengirim" name="nama_pengirim" placeholder="Nama sesuai rekening/kartu" required>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_transfer">Tanggal Transfer *</label>
                        <input type="date" id="tanggal_transfer" name="tanggal_transfer" required>
                    </div>

                    <div class="form-group">
                        <label for="bukti_pembayaran">Bukti Pembayaran (Foto/Screenshot) *</label>
                        <div class="file-upload-area" id="uploadArea">
                            <input type="file" id="bukti_pembayaran" name="bukti_pembayaran" accept="image/jpeg,image/png,image/jpg" required>
                            <div class="file-icon">📸</div>
                            <div class="file-text">Klik atau drag file untuk upload</div>
                            <div class="file-hint">JPG atau PNG, maksimal 5MB</div>
                        </div>
                        <div class="file-preview" id="filePreview">
                            <div class="preview-info">
                                <div class="preview-icon">📄</div>
                                <div class="preview-details">
                                    <div class="preview-name" id="previewName"></div>
                                    <div class="preview-size" id="previewSize"></div>
                                </div>
                                <button type="button" class="preview-remove" id="removeFile">Hapus</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="catatan_pembayaran">Catatan (Opsional)</label>
                        <textarea id="catatan_pembayaran" name="catatan_pembayaran" placeholder="Catatan tambahan untuk pembayaran..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary" id="submitBtn">Konfirmasi & Buat Pesanan</button>
                    <a href="pengiriman.php" class="btn-secondary">Kembali ke Pengiriman</a>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <h4>Ringkasan Pesanan</h4>

                <!-- Shipping Info -->
                <div class="shipping-info">
                    <div class="shipping-info-item">
                        <span class="shipping-label">No. Pengiriman:</span>
                        <span class="shipping-value"><?php echo htmlspecialchars($pengiriman_data['no_pengiriman']); ?></span>
                    </div>
                    <div class="shipping-info-item">
                        <span class="shipping-label">Status:</span>
                        <span class="shipping-value"><?php echo htmlspecialchars($pengiriman_data['status_pengiriman']); ?></span>
                    </div>
                    <div class="shipping-info-item">
                        <span class="shipping-label">Metode:</span>
                        <span class="shipping-value"><?php echo ucfirst(str_replace('_', ' ', $pengiriman_data['metode_pengiriman'])); ?></span>
                    </div>
                </div>

                <!-- Cart Items -->
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <span><?php echo htmlspecialchars($item['nama_produk']); ?> x <?php echo $item['qty']; ?></span>
                        <span>Rp <?php echo number_format($item['harga'] * $item['qty'], 0, ',', '.'); ?></span>
                    </div>
                <?php endforeach; ?>

                <!-- Totals -->
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>

                <?php if ($diskon > 0): ?>
                    <div class="summary-row">
                        <span>Diskon</span>
                        <span style="color: var(--success);">-Rp <?php echo number_format($diskon, 0, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Ongkir</span>
                    <span>Rp <?php echo number_format($ongkir, 0, ',', '.'); ?></span>
                </div>

                <div class="summary-row total">
                    <span>Total Bayar</span>
                    <span id="totalAmount">Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const totalBayar = <?php echo $total_bayar; ?>;

        // Countdown timer
        function startCountdown() {
            let timeRemaining = 24 * 60 * 60; // 24 hours in seconds
            
            const timer = setInterval(() => {
                const hours = Math.floor(timeRemaining / 3600);
                const minutes = Math.floor((timeRemaining % 3600) / 60);
                const seconds = timeRemaining % 60;
                
                document.getElementById('countdown').textContent = 
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
                
                timeRemaining--;
                
                if (timeRemaining < 0) {
                    clearInterval(timer);
                    document.getElementById('countdown').textContent = '00:00:00';
                    document.getElementById('submitBtn').disabled = true;
                    showError('Waktu pembayaran telah habis');
                }
            }, 1000);
        }

        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', () => {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                method.classList.add('selected');
                method.querySelector('input[type="radio"]').checked = true;
            });
        });

        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('bukti_pembayaran');
        const filePreview = document.getElementById('filePreview');

        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('active');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('active');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('active');
            fileInput.files = e.dataTransfer.files;
            handleFileSelect();
        });

        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (!file) return;

            // Validate file type
            if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                showError('Format file harus JPG atau PNG');
                fileInput.value = '';
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showError('Ukuran file maksimal 5MB');
                fileInput.value = '';
                return;
            }

            // Show preview
            document.getElementById('previewName').textContent = file.name;
            document.getElementById('previewSize').textContent = (file.size / 1024).toFixed(2) + ' KB';
            filePreview.classList.add('active');
        }

        document.getElementById('removeFile').addEventListener('click', () => {
            fileInput.value = '';
            filePreview.classList.remove('active');
        });

        // Form submission
        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                showError('Pilih metode pembayaran');
                return;
            }

            if (!fileInput.files[0]) {
                showError('Upload bukti pembayaran');
                return;
            }

            const formData = new FormData();
            formData.append('payment_method', paymentMethod.value);
            formData.append('nama_pengirim', document.getElementById('nama_pengirim').value);
            formData.append('tanggal_transfer', document.getElementById('tanggal_transfer').value);
            formData.append('bukti_pembayaran', fileInput.files[0]);
            formData.append('catatan_pembayaran', document.getElementById('catatan_pembayaran').value);

            document.getElementById('submitBtn').disabled = true;

            try {
                const response = await fetch('../api/payment-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess('Pesanan berhasil dibuat! Silahkan tunggu verifikasi pembayaran.');
                    setTimeout(() => {
                        window.location.href = 'order-success.php?id=' + result.id_pesanan + '&no=' + result.no_pesanan;
                    }, 2000);
                } else {
                    showError(result.message);
                    document.getElementById('submitBtn').disabled = false;
                }
            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
                document.getElementById('submitBtn').disabled = false;
            }
        });

        function showError(message) {
            const errorDiv = document.getElementById('errorMsg');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            window.scrollTo(0, 0);
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMsg');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            window.scrollTo(0, 0);
        }

        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tanggal_transfer').value = today;
        document.getElementById('tanggal_transfer').min = today;

        // Start countdown on page load
        startCountdown();
    </script>
</body>
</html>
