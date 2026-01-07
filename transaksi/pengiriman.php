<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once '../config.php';

// Check if user logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user data for form pre-fill
$user_sql = "SELECT * FROM pengguna WHERE id_user = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);

// Get cart items & calculate subtotal
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

// Shipping costs
$shipping_costs = [
    'regular' => 20000,
    'express' => 50000,
    'same_day' => 100000
];

$selected_shipping = isset($_SESSION['shipping_method']) ? $_SESSION['shipping_method'] : 'regular';
$ongkir = $shipping_costs[$selected_shipping] ?? 20000;
$total = $subtotal - $diskon + $ongkir;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengiriman - MobileNest</title>
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

        .shipping-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .shipping-option {
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .shipping-option:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .shipping-option input[type="radio"] {
            display: none;
        }

        .shipping-option input[type="radio"]:checked + label {
            color: var(--primary);
        }

        .shipping-option.selected {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .shipping-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .shipping-price {
            font-size: 16px;
            font-weight: bold;
            color: var(--primary);
        }

        .shipping-time {
            font-size: 12px;
            color: #6b7280;
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

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.3);
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

            .shipping-options {
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
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-title">Pengiriman</div>
                </div>
                <div class="step">
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
                <h3>Data Pengiriman</h3>
                
                <div class="error-message" id="errorMsg"></div>
                <div class="success-message" id="successMsg"></div>

                <form id="shippingForm" method="POST">
                    <div class="form-group">
                        <label for="nama_penerima">Nama Penerima *</label>
                        <input type="text" id="nama_penerima" name="nama_penerima" 
                               value="<?php echo htmlspecialchars($user_data['nama_pengguna'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="no_telepon">Nomor Telepon *</label>
                        <input type="tel" id="no_telepon" name="no_telepon" 
                               value="<?php echo htmlspecialchars($user_data['no_telepon'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="provinsi">Provinsi *</label>
                        <input type="text" id="provinsi" name="provinsi" 
                               value="<?php echo htmlspecialchars($user_data['provinsi'] ?? 'Jawa Barat'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="kota">Kota *</label>
                        <input type="text" id="kota" name="kota" 
                               value="<?php echo htmlspecialchars($user_data['kota'] ?? 'Solok'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="kecamatan">Kecamatan *</label>
                        <input type="text" id="kecamatan" name="kecamatan" 
                               value="<?php echo htmlspecialchars($user_data['alamat'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="kode_pos">Kode Pos *</label>
                        <input type="text" id="kode_pos" name="kode_pos" placeholder="5-10 digits" required>
                    </div>

                    <div class="form-group">
                        <label for="alamat_lengkap">Alamat Lengkap *</label>
                        <textarea id="alamat_lengkap" name="alamat_lengkap" required 
                                  placeholder="Jl. Nama, No. XX, RT XX, RW XX"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Metode Pengiriman *</label>
                        <div class="shipping-options">
                            <div class="shipping-option selected" data-method="regular">
                                <input type="radio" id="regular" name="metode_pengiriman" value="regular" checked>
                                <label for="regular" style="cursor: pointer; margin: 0;">
                                    <div class="shipping-name">Regular</div>
                                    <div class="shipping-price">Rp 20.000</div>
                                    <div class="shipping-time">3-5 Hari</div>
                                </label>
                            </div>
                            <div class="shipping-option" data-method="express">
                                <input type="radio" id="express" name="metode_pengiriman" value="express">
                                <label for="express" style="cursor: pointer; margin: 0;">
                                    <div class="shipping-name">Express</div>
                                    <div class="shipping-price">Rp 50.000</div>
                                    <div class="shipping-time">1-2 Hari</div>
                                </label>
                            </div>
                            <div class="shipping-option" data-method="same_day">
                                <input type="radio" id="same_day" name="metode_pengiriman" value="same_day">
                                <label for="same_day" style="cursor: pointer; margin: 0;">
                                    <div class="shipping-name">Same Day</div>
                                    <div class="shipping-price">Rp 100.000</div>
                                    <div class="shipping-time">Hari Ini</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="catatan">Catatan (Opsional)</label>
                        <textarea id="catatan" name="catatan" placeholder="Catatan untuk kurir..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Lanjut ke Pembayaran</button>
                    <a href="keranjang.php" class="btn-secondary">Kembali ke Keranjang</a>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <h4>Ringkasan Belanja</h4>

                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <span><?php echo htmlspecialchars($item['nama_produk']); ?> x <?php echo $item['qty']; ?></span>
                        <span>Rp <?php echo number_format($item['harga'] * $item['qty'], 0, ',', '.'); ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal-amount">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>

                <?php if ($diskon > 0): ?>
                    <div class="summary-row">
                        <span>Diskon</span>
                        <span style="color: var(--success);">-Rp <?php echo number_format($diskon, 0, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Ongkir</span>
                    <span id="ongkir-amount">Rp <?php echo number_format($ongkir, 0, ',', '.'); ?></span>
                </div>

                <div class="summary-row total">
                    <span>Total</span>
                    <span id="total-amount">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const shippingCosts = {
            regular: 20000,
            express: 50000,
            same_day: 100000
        };

        const subtotal = <?php echo $subtotal; ?>;
        const diskon = <?php echo $diskon; ?>;

        // Shipping method change
        document.querySelectorAll('input[name="metode_pengiriman"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const method = e.target.value;
                
                // Update visual selection
                document.querySelectorAll('.shipping-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                e.target.closest('.shipping-option').classList.add('selected');

                // Update ongkir
                const newOngkir = shippingCosts[method];
                const newTotal = subtotal - diskon + newOngkir;
                
                document.getElementById('ongkir-amount').textContent = 
                    'Rp ' + newOngkir.toLocaleString('id-ID');
                document.getElementById('total-amount').textContent = 
                    'Rp ' + newTotal.toLocaleString('id-ID');
            });
        });

        // Form submission
        document.getElementById('shippingForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                nama_penerima: document.getElementById('nama_penerima').value,
                no_telepon: document.getElementById('no_telepon').value,
                email: document.getElementById('email').value,
                provinsi: document.getElementById('provinsi').value,
                kota: document.getElementById('kota').value,
                kecamatan: document.getElementById('kecamatan').value,
                kode_pos: document.getElementById('kode_pos').value,
                alamat_lengkap: document.getElementById('alamat_lengkap').value,
                metode_pengiriman: document.getElementById('metode_pengiriman').value,
                catatan: document.getElementById('catatan').value
            };

            try {
                const response = await fetch('../api/shipping-handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = 'pembayaran.php';
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
            }
        });

        function showError(message) {
            const errorDiv = document.getElementById('errorMsg');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    </script>
</body>
</html>
