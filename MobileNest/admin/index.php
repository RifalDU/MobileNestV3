<?php
require_once '../config.php';

// Check if user is logged in and is admin
// Untuk sekarang, skip admin check (anda bisa implement nanti)
if (!is_logged_in()) {
    header('Location: ../user/login.php');
    exit;
}

$page_title = "Admin Dashboard";
$css_path = "../assets/css/style.css";
$logo_path = "../assets/images/logo.jpg";
$home_url = "../index.php";

include '../includes/header.php';

// Get stats
$total_produk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produk"))['total'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi"))['total'];
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_harga) as total FROM transaksi"))['total'] ?? 0;
?>

    <!-- ADMIN NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-speedometer2"></i> Admin MobileNest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="../user/profil.php"><i class="bi bi-person"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../user/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ADMIN LAYOUT -->
    <div class="container-fluid mt-4 mb-5">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-3 mb-4">
                <div class="card border-0 shadow-sm" style="position: sticky; top: 100px;">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-3"><i class="bi bi-list"></i> Menu Admin</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="index.php" class="btn btn-primary w-100 text-start">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="kelola-produk.php" class="btn btn-outline-primary w-100 text-start">
                                    <i class="bi bi-box-seam"></i> Kelola Produk
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="kelola-users.php" class="btn btn-outline-primary w-100 text-start">
                                    <i class="bi bi-people"></i> Kelola User
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="kelola-transaksi.php" class="btn btn-outline-primary w-100 text-start">
                                    <i class="bi bi-receipt"></i> Kelola Transaksi
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-md-9">
                <h1 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard Admin</h1>

                <!-- STATS CARDS -->
                <div class="row mb-5">
                    <!-- Total Produk -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-box-seam text-primary" style="font-size: 2.5rem;"></i>
                                <h3 class="fw-bold mt-2 text-primary"><?php echo $total_produk; ?></h3>
                                <p class="text-muted mb-0">Total Produk</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total User -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-people text-success" style="font-size: 2.5rem;"></i>
                                <h3 class="fw-bold mt-2 text-success"><?php echo $total_users; ?></h3>
                                <p class="text-muted mb-0">Total User</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Transaksi -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-receipt text-warning" style="font-size: 2.5rem;"></i>
                                <h3 class="fw-bold mt-2 text-warning"><?php echo $total_transaksi; ?></h3>
                                <p class="text-muted mb-0">Total Transaksi</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Pendapatan -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="bi bi-cash-coin text-danger" style="font-size: 2.5rem;"></i>
                                <h3 class="fw-bold mt-2 text-danger">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h3>
                                <p class="text-muted mb-0">Total Pendapatan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-header bg-light fw-bold">
                        <i class="bi bi-lightning"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2 d-sm-flex">
                            <a href="kelola-produk.php?action=tambah" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Tambah Produk Baru
                            </a>
                            <a href="kelola-produk.php" class="btn btn-outline-primary">
                                <i class="bi bi-list"></i> Lihat Semua Produk
                            </a>
                            <a href="../index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-house"></i> Ke Halaman Utama
                            </a>
                        </div>
                    </div>
                </div>

                <!-- RECENT PRODUCTS -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light fw-bold">
                        <i class="bi bi-clock-history"></i> Produk Terbaru
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th>Merek</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM produk ORDER BY tanggal_ditambahkan DESC LIMIT 5";
                                    $result = mysqli_query($conn, $sql);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['nama_produk']); ?></td>
                                            <td><?php echo htmlspecialchars($row['merek']); ?></td>
                                            <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                            <td><?php echo $row['stok']; ?> unit</td>
                                            <td><span class="badge bg-success"><?php echo htmlspecialchars($row['status_produk']); ?></span></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
