<?php
session_start();
require_once '../config/config.php';

// Check if user logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$query = "SELECT nama_pengguna, no_telepon, email FROM pengguna WHERE id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get cart items & calculate subtotal
$query_cart = "SELECT k.*, p.nama_produk, p.harga FROM keranjang k 
              JOIN produk p ON k.id_produk = p.id_produk 
              WHERE k.id_user = ?";
$stmt_cart = $conn->prepare($query_cart);
$stmt_cart->bind_param('i', $user_id);
$stmt_cart->execute();
$cart_result = $stmt_cart->get_result();
$cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['harga'] * $item['qty'];
}

// Default shipping cost
$shipping_cost = 20000; // Regular
$shipping_method = 'regular';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengiriman - MobileNest</title>
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
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        /* Shipping Methods */
        .shipping-methods {
            margin: 20px 0;
        }

        .shipping-methods h3 {
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .method-name {
            font-weight: 500;
        }

        .method-cost {
            color: #007bff;
            font-weight: 600;
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

        .cart-summary {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 13px;
        }

        .summary-row.total {
            font-weight: 600;
            border-top: 1px solid #eee;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 16px;
            color: #222;
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
        }

        .success {
            color: #28a745;
            font-size: 13px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Data Pengiriman</h1>

        <div class="main-content">
            <!-- Form Section -->
            <form id="shippingForm" class="form-section">
                <div class="form-group">
                    <label>Nama Penerima *</label>
                    <input type="text" name="nama_penerima" value="<?php echo htmlspecialchars($user['nama_pengguna'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Nomor Telepon *</label>
                    <input type="tel" name="no_telepon" value="<?php echo htmlspecialchars($user['no_telepon'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <textarea name="alamat_lengkap" required></textarea>
                </div>

                <div class="form-group">
                    <label>Kota/Kabupaten *</label>
                    <input type="text" name="kota" required>
                </div>

                <div class="form-group">
                    <label>Kode Pos *</label>
                    <input type="text" name="kode_pos" placeholder="Cth: 40141" required>
                </div>

                <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="catatan" style="min-height: 40px;"></textarea>
                </div>

                <!-- Shipping Methods -->
                <div class="shipping-methods">
                    <h3>Metode Pengiriman</h3>
                    
                    <label class="method-item">
                        <input type="radio" name="metode_pengiriman" value="regular" checked data-cost="20000">
                        <div class="method-label">
                            <span class="method-name">🚚 Regular (5-7 hari)</span>
                            <span class="method-cost">Rp 20.000</span>
                        </div>
                    </label>

                    <label class="method-item">
                        <input type="radio" name="metode_pengiriman" value="express" data-cost="50000">
                        <div class="method-label">
                            <span class="method-name">⚡ Express (2-3 hari)</span>
                            <span class="method-cost">Rp 50.000</span>
                        </div>
                    </label>

                    <label class="method-item">
                        <input type="radio" name="metode_pengiriman" value="same_day" data-cost="100000">
                        <div class="method-label">
                            <span class="method-name">🏃 Same Day</span>
                            <span class="method-cost">Rp 100.000</span>
                        </div>
                    </label>
                </div>

                <div class="buttons">
                    <button type="button" class="btn-secondary" onclick="history.back()">← Kembali</button>
                    <button type="submit" class="btn-primary">Lanjut ke Pembayaran →</button>
                </div>

                <div id="message"></div>
            </form>

            <!-- Sidebar -->
            <div class="sidebar">
                <h3>📋 Ringkasan Pesanan</h3>

                <div class="cart-summary">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <span><?php echo htmlspecialchars($item['nama_produk']); ?> x<?php echo $item['qty']; ?></span>
                            <span>Rp <?php echo number_format($item['harga'] * $item['qty']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>Rp <?php echo number_format($subtotal); ?></span>
                </div>

                <div class="summary-row">
                    <span>Ongkir:</span>
                    <span id="ongkir-display">Rp 20.000</span>
                </div>

                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="total-display">Rp <?php echo number_format($subtotal + 20000); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const subtotal = <?php echo $subtotal; ?>;
        const form = document.getElementById('shippingForm');
        const messageDiv = document.getElementById('message');
        const ongkirDisplay = document.getElementById('ongkir-display');
        const totalDisplay = document.getElementById('total-display');
        const shippingRadios = document.querySelectorAll('input[name="metode_pengiriman"]');

        // Update total when shipping method changes
        shippingRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const cost = parseInt(this.dataset.cost);
                const total = subtotal + cost;
                ongkirDisplay.textContent = 'Rp ' + cost.toLocaleString('id-ID');
                totalDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
            });
        });

        // Handle form submit
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(form);

            try {
                const response = await fetch('../api/shipping-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = 'pembayaran.php';
                } else {
                    messageDiv.className = 'error';
                    messageDiv.textContent = result.message || 'Terjadi kesalahan';
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.className = 'error';
                messageDiv.textContent = 'Error: ' + error.message;
            }
        });
    </script>
</body>
</html>
