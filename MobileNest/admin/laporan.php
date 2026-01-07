<?php
// ...existing code...
session_start();
require_once __DIR__ . '/config.php';
// atau dari subfolder admin:
require_once __DIR__ . '/../config.php';

// simple auth check (sesuaikan nama session admin jika berbeda)
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// koneksi mysqli dari koneksi.php diharapkan tersedia sebagai $conn
if (!isset($conn) || !$conn instanceof mysqli) {
    // fallback sederhana jika koneksi tidak didefinisikan
    $conn = new mysqli('localhost', 'root', '', 'mobilenest');
    if ($conn->connect_errno) {
        die('Database connection failed: ' . $conn->connect_error);
    }
}

// sanitasi dan default tanggal (30 hari terakhir)
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date   = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : date('Y-m-d');

// basic validation: ensure format YYYY-MM-DD
$sd_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date);
$ed_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date);
if (!$sd_ok) $start_date = date('Y-m-d', strtotime('-30 days'));
if (!$ed_ok) $end_date = date('Y-m-d');

$start_datetime = $start_date . ' 00:00:00';
$end_datetime   = $end_date . ' 23:59:59';

// prepare statements untuk ringkasan
$countStmt = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE tanggal_transaksi BETWEEN ? AND ?");
$sumStmt   = $conn->prepare("SELECT IFNULL(SUM(total_harga),0) FROM transaksi WHERE tanggal_transaksi BETWEEN ? AND ?");
$listStmt  = $conn->prepare("SELECT id_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, no_resi, tanggal_transaksi, tanggal_diperbarui FROM transaksi WHERE tanggal_transaksi BETWEEN ? AND ? ORDER BY tanggal_transaksi DESC");

// eksekusi ringkasan
$totalTransactions = 0;
$totalRevenue = 0;

if ($countStmt && $sumStmt) {
    $countStmt->bind_param('ss', $start_datetime, $end_datetime);
    $countStmt->execute();
    $countStmt->bind_result($totalTransactions);
    $countStmt->fetch();
    $countStmt->close();

    $sumStmt->bind_param('ss', $start_datetime, $end_datetime);
    $sumStmt->execute();
    $sumStmt->bind_result($totalRevenue);
    $sumStmt->fetch();
    $sumStmt->close();
}

// handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!$listStmt) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Query preparation failed';
        exit;
    }
    $listStmt->bind_param('ss', $start_datetime, $end_datetime);
    $listStmt->execute();
    $res = $listStmt->get_result();

    $filename = 'laporan_transaksi_' . $start_date . '_to_' . $end_date . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');

    // header CSV
    fputcsv($out, ['ID Transaksi','ID User','Tanggal Transaksi','Tanggal Diperbarui','Total Harga','Status Pesanan','Metode Pembayaran','No Resi','Alamat Pengiriman']);

    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [
            $row['id_transaksi'],
            $row['id_user'],
            $row['tanggal_transaksi'],
            $row['tanggal_diperbarui'],
            $row['total_harga'],
            $row['status_pesanan'],
            $row['metode_pembayaran'],
            $row['no_resi'],
            $row['alamat_pengiriman'],
        ]);
    }
    fclose($out);
    exit;
}

// fetch list for display (limit)
$displayLimit = 1000;
$listStmt = $conn->prepare("SELECT id_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, no_resi, tanggal_transaksi, tanggal_diperbarui FROM transaksi WHERE tanggal_transaksi BETWEEN ? AND ? ORDER BY tanggal_transaksi DESC LIMIT ?");
if ($listStmt) {
    $listStmt->bind_param('ssi', $start_datetime, $end_datetime, $displayLimit);
    $listStmt->execute();
    $result = $listStmt->get_result();
} else {
    $result = null;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Laporan Transaksi - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">MobileNest Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="kelola-produk.php">Kelola Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="kelola-transaksi.php">Transaksi</a></li>
        <li class="nav-item"><a class="nav-link active" href="laporan.php">Laporan</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
  <h3>Laporan Transaksi</h3>

  <form class="row g-2 align-items-end mb-3" method="get" action="laporan.php">
    <div class="col-auto">
      <label class="form-label">Dari</label>
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
    </div>
    <div class="col-auto">
      <label class="form-label">Sampai</label>
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="laporan.php" class="btn btn-outline-secondary">Reset</a>
    </div>
    <div class="col-auto ms-auto text-end">
      <a href="laporan.php?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-success">Export CSV</a>
    </div>
  </form>

  <div class="row mb-3">
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h6>Total Transaksi</h6>
          <p class="fs-4 mb-0"><?= (int)$totalTransactions ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h6>Total Pendapatan</h6>
          <p class="fs-4 mb-0">Rp <?= number_format((float)$totalRevenue,0,',','.') ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>ID User</th>
          <th>Tanggal Transaksi</th>
          <th>Tanggal Diperbarui</th>
          <th>Total Harga</th>
          <th>Status</th>
          <th>Metode</th>
          <th>No Resi</th>
          <th>Alamat Pengiriman</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result || $result->num_rows === 0): ?>
          <tr><td colspan="9" class="text-center">Tidak ada transaksi pada rentang waktu ini.</td></tr>
        <?php else: ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id_transaksi']) ?></td>
              <td><?= htmlspecialchars($row['id_user']) ?></td>
              <td><?= htmlspecialchars($row['tanggal_transaksi']) ?></td>
              <td><?= htmlspecialchars($row['tanggal_diperbarui']) ?></td>
              <td>Rp <?= number_format((float)$row['total_harga'],0,',','.') ?></td>
              <td><?= htmlspecialchars($row['status_pesanan']) ?></td>
              <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
              <td><?= htmlspecialchars($row['no_resi'] ?? '-') ?></td>
              <td style="max-width:300px;white-space:normal;"><?= htmlspecialchars($row['alamat_pengiriman']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted">Catatan: daftar dibatasi <?= $displayLimit ?> baris. Gunakan Export CSV untuk mengambil data lengkap.</p>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```// filepath: c:\xampp\htdocs\MobileNest\admin\laporan.php
// ...existing code...
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// simple auth check (sesuaikan nama session admin jika berbeda)
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// koneksi mysqli dari koneksi.php diharapkan tersedia sebagai $conn
if (!isset($conn) || !$conn instanceof mysqli) {
    // fallback sederhana jika koneksi tidak didefinisikan
    $conn = new mysqli('localhost', 'root', '', 'mobilenest');
    if ($conn->connect_errno) {
        die('Database connection failed: ' . $conn->connect_error);
    }
}

// sanitasi dan default tanggal (30 hari terakhir)
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date   = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : date('Y-m-d');

// basic validation: ensure format YYYY-MM-DD
$sd_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date);
$ed_ok = preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date);
if (!$sd_ok) $start_date = date('Y-m-d', strtotime('-30 days'));
if (!$ed_ok) $end_date = date('Y-m-d');

$start_datetime = $start_date . ' 00:00:00';
$end_datetime   = $end_date . ' 23:59:59';

// prepare statements untuk ringkasan
$countStmt = $conn->prepare("SELECT COUNT(*) FROM transaksi WHERE tanggal_transaksi BETWEEN ? AND ?");
$sumStmt   = $conn->prepare("SELECT IFNULL(SUM(total_harga),0) FROM transaksi WHERE tanggal_transaksi BETWEEN ? AND ?");
$listStmt  = $conn->prepare("SELECT id_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, no_resi, tanggal_transaksi, tanggal_diperbarui FROM transaksi WHERE tanggal_transaksi BETWEEN ? AND ? ORDER BY tanggal_transaksi DESC");

// eksekusi ringkasan
$totalTransactions = 0;
$totalRevenue = 0;

if ($countStmt && $sumStmt) {
    $countStmt->bind_param('ss', $start_datetime, $end_datetime);
    $countStmt->execute();
    $countStmt->bind_result($totalTransactions);
    $countStmt->fetch();
    $countStmt->close();

    $sumStmt->bind_param('ss', $start_datetime, $end_datetime);
    $sumStmt->execute();
    $sumStmt->bind_result($totalRevenue);
    $sumStmt->fetch();
    $sumStmt->close();
}

// handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!$listStmt) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Query preparation failed';
        exit;
    }
    $listStmt->bind_param('ss', $start_datetime, $end_datetime);
    $listStmt->execute();
    $res = $listStmt->get_result();

    $filename = 'laporan_transaksi_' . $start_date . '_to_' . $end_date . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');

    // header CSV
    fputcsv($out, ['ID Transaksi','ID User','Tanggal Transaksi','Tanggal Diperbarui','Total Harga','Status Pesanan','Metode Pembayaran','No Resi','Alamat Pengiriman']);

    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [
            $row['id_transaksi'],
            $row['id_user'],
            $row['tanggal_transaksi'],
            $row['tanggal_diperbarui'],
            $row['total_harga'],
            $row['status_pesanan'],
            $row['metode_pembayaran'],
            $row['no_resi'],
            $row['alamat_pengiriman'],
        ]);
    }
    fclose($out);
    exit;
}

// fetch list for display (limit)
$displayLimit = 1000;
$listStmt = $conn->prepare("SELECT id_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, no_resi, tanggal_transaksi, tanggal_diperbarui FROM transaksi WHERE tanggal_transaksi BETWEEN ? AND ? ORDER BY tanggal_transaksi DESC LIMIT ?");
if ($listStmt) {
    $listStmt->bind_param('ssi', $start_datetime, $end_datetime, $displayLimit);
    $listStmt->execute();
    $result = $listStmt->get_result();
} else {
    $result = null;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Laporan Transaksi - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">MobileNest Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="kelola-produk.php">Kelola Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="kelola-transaksi.php">Transaksi</a></li>
        <li class="nav-item"><a class="nav-link active" href="laporan.php">Laporan</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
  <h3>Laporan Transaksi</h3>

  <form class="row g-2 align-items-end mb-3" method="get" action="laporan.php">
    <div class="col-auto">
      <label class="form-label">Dari</label>
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
    </div>
    <div class="col-auto">
      <label class="form-label">Sampai</label>
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="laporan.php" class="btn btn-outline-secondary">Reset</a>
    </div>
    <div class="col-auto ms-auto text-end">
      <a href="laporan.php?export=csv&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-success">Export CSV</a>
    </div>
  </form>

  <div class="row mb-3">
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h6>Total Transaksi</h6>
          <p class="fs-4 mb-0"><?= (int)$totalTransactions ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h6>Total Pendapatan</h6>
          <p class="fs-4 mb-0">Rp <?= number_format((float)$totalRevenue,0,',','.') ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>ID User</th>
          <th>Tanggal Transaksi</th>
          <th>Tanggal Diperbarui</th>
          <th>Total Harga</th>
          <th>Status</th>
          <th>Metode</th>
          <th>No Resi</th>
          <th>Alamat Pengiriman</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result || $result->num_rows === 0): ?>
          <tr><td colspan="9" class="text-center">Tidak ada transaksi pada rentang waktu ini.</td></tr>
        <?php else: ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id_transaksi']) ?></td>
              <td><?= htmlspecialchars($row['id_user']) ?></td>
              <td><?= htmlspecialchars($row['tanggal_transaksi']) ?></td>
              <td><?= htmlspecialchars($row['tanggal_diperbarui']) ?></td>
              <td>Rp <?= number_format((float)$row['total_harga'],0,',','.') ?></td>
              <td><?= htmlspecialchars($row['status_pesanan']) ?></td>
              <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
              <td><?= htmlspecialchars($row['no_resi'] ?? '-') ?></td>
              <td style="max-width:300px;white-space:normal;"><?= htmlspecialchars($row['alamat_pengiriman']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted">Catatan: daftar dibatasi <?= $displayLimit ?> baris. Gunakan Export CSV untuk mengambil data lengkap.</p>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>