<?php
session_start();
require_once '../config.php';
require_once '../includes/brand-logos.php';

// Validasi ID Produk
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: list-produk.php');
    exit;
}

$id_produk = mysqli_real_escape_string($conn, $_GET['id']);
$sql = "SELECT * FROM produk WHERE id_produk = '$id_produk' AND status_produk = 'Tersedia'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Produk tidak ditemukan!');window.location='list-produk.php';</script>";
    exit;
}

$product = mysqli_fetch_assoc($result);
$page_title = $product['nama_produk'];
$brand_logo = get_brand_logo_data($product['merek']);

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <?php
                    $img_path = "../uploads/" . $product['gambar'];
                    if (!empty($product['gambar']) && file_exists("../uploads/" . $product['gambar'])) {
                        echo '<img src="'.$img_path.'" class="img-fluid rounded" alt="'.htmlspecialchars($product['nama_produk']).'">';
                    } else {
                        echo '<div class="card-body text-center bg-light d-flex align-items-center justify-content-center" style="min-height: 400px;">
                                <i class="bi bi-phone" style="font-size: 5rem; color: #ccc;"></i>
                              </div>';
                    }
                ?>
            </div>
        </div>
        
        <div class="col-md-8">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="list-produk.php">Produk</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['nama_produk']); ?></li>
                </ol>
            </nav>
            
            <h1 class="mb-3"><?php echo htmlspecialchars($product['nama_produk']); ?></h1>
            
            <!-- Brand dengan Logo -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <?php if ($brand_logo): ?>
                    <div style="width: 40px; height: 40px;">
                        <img src="<?php echo htmlspecialchars($brand_logo['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($brand_logo['alt']); ?>" 
                             style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                <?php endif; ?>
                <div>
                    <p class="text-muted mb-0">Merek:</p>
                    <p class="mb-0"><strong><?php echo htmlspecialchars($product['merek']); ?></strong></p>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-8">
                    <h3 class="text-primary fw-bold mb-3">
                        Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?>
                    </h3>
                    
                    <div class="mb-3">
                        <span class="badge bg-success">Stok: <?php echo $product['stok']; ?> unit</span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="input-group" style="width: 130px;">
                            <span class="input-group-text">Qty</span>
                            <input type="number" class="form-control text-center" id="quantity" min="1" max="<?php echo $product['stok']; ?>" value="1">
                        </div>
                        <button class="btn btn-primary btn-lg" type="button" onclick="handleAddToCart()">
                            <i class="bi bi-cart-plus"></i> Tambah Keranjang
                        </button>
                    </div>
                </div>
            </div>
            
            <hr>
            <h4>Deskripsi</h4>
            <p><?php echo nl2br(htmlspecialchars($product['deskripsi'])); ?></p>
        </div>
    </div>
</div>

<script>
async function handleAddToCart() {
    const qty = parseInt(document.getElementById('quantity').value);
    const id = <?php echo $product['id_produk']; ?>;
    
    // Panggil fungsi dari api-handler.js
    // Kita gunakan showNotification dari cart.js
    try {
        const result = await addToCart(id, qty);
        if (result.success) {
            showNotification('success', 'Berhasil ditambahkan ke keranjang!');
            updateCartCount(); // Update badge di navbar
        } else {
            showNotification('error', 'Gagal: ' + result.message);
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan sistem');
    }
}
</script>

<?php include '../includes/footer.php'; ?>