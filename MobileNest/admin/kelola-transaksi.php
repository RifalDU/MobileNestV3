<?php
session_start();
require_once __DIR__ . '/config.php';
// atau dari subfolder admin:
require_once __DIR__ . '/../config.php';

// autentikasi
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// fallback koneksi jika koneksi.php belum menyediakan $conn
if (!isset($conn) || !$conn instanceof mysqli) {
    $conn = new mysqli('localhost', 'root', '', 'mobilenest');
    if ($conn->connect_errno) {
        die('Database connection failed: ' . $conn->connect_error);
    }
}

// handle actions: update status, update resi, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // minimal sanitize / validate
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status' && !empty($_POST['id_transaksi']) && isset($_POST['status_pesanan'])) {
        $id = (int)$_POST['id_transaksi'];
        $status = trim($_POST['status_pesanan']);
        $stmt = $conn->prepare("UPDATE transaksi SET status_pesanan = ?, tanggal_diperbarui = NOW() WHERE id_transaksi = ?");
        if ($stmt) {
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: kelola-transaksi.php');
        exit;
    }

    if ($action === 'update_resi' && !empty($_POST['id_transaksi'])) {
        $id = (int)$_POST['id_transaksi'];
        $no_resi = trim($_POST['no_resi']);
        $stmt = $conn->prepare("UPDATE transaksi SET no_resi = ?, tanggal_diperbarui = NOW() WHERE id_transaksi = ?");
        if ($stmt) {
            $stmt->bind_param('si', $no_resi, $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: kelola-transaksi.php');
        exit;
    }

    if ($action === 'delete' && !empty($_POST['id_transaksi'])) {
        $id = (int)$_POST['id_transaksi'];
        // jika ada tabel terkait (mis. transaksi_items), hapus dulu di sana. contoh:
        // $stmt = $conn->prepare("DELETE FROM transaksi_items WHERE id_transaksi = ?");
        // $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();

        $stmt = $conn->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: kelola-transaksi.php');
        exit;
    }
}

// paging / filter sederhana
$limit = 1000;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// optional filter by status or date range
$where = "1=1";
$params = [];
$types = '';

// filter status
if (!empty($_GET['status'])) {
    $where .= " AND status_pesanan = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// filter date range
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    $where .= " AND tanggal_transaksi BETWEEN ? AND ?";
    $params[] = $start . ' 00:00:00';
    $params[] = $end . ' 23:59:59';
    $types .= 'ss';
}

// prepare query (tidak termasuk LIMIT/OFFSET dalam prepared dengan bind_param untuk MySQLi)
$sql = "SELECT id_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, no_resi, tanggal_transaksi, tanggal_diperbarui FROM transaksi WHERE $where ORDER BY tanggal_transaksi DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    // bind dynamic params then limit/offset
    if ($types !== '') {
        // build array for call_user_func_array
        $bind_names[] = $types . 'ii';
        foreach ($params as $k => $v) {
            $bind_names[] = &$params[$k];
        }
        $bind_names[] = &$limit;
        $bind_names[] = &$offset;
        // Note: PHP < 5.6 workaround using call_user_func_array
        $ref = new ReflectionClass('mysqli_stmt');
        $method = $ref->getMethod('bind_param');
        // simpler: use variable argument list via ... operator if available
        // fallback: manual binding below
        // We'll bind manually depending on count:
        // Build types for all params including ii
        $all_types = $types . 'ii';
        $all_params = array_merge($params, [$limit, $offset]);
        // Proper binding using dynamic call:
        $tmp = [];
        $tmp[] = $all_types;
        foreach ($all_params as $k => $v) {
            $tmp[] = &$all_params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $tmp);
    } else {
        // only limit/offset
        $stmt->bind_param('ii', $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = null;
}

// helper statuses (sesuaikan bila ada status lain)
$statuses = ['Menunggu Pembayaran','Diproses','Dikirim','Selesai','Dibatalkan'];

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Transaksi - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">MobileNest Admin</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="kelola-produk.php">Kelola Produk</a></li>
        <li class="nav-item"><a class="nav-link active" href="kelola-transaksi.php">Transaksi</a></li>
        <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
  <h3>Kelola Transaksi</h3>

  <form class="row g-2 mb-3" method="get" action="kelola-transaksi.php">
    <div class="col-auto">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">Semua</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>" <?= (isset($_GET['status']) && $_GET['status'] === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label">Dari</label>
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start) ?>">
    </div>
    <div class="col-auto">
      <label class="form-label">Sampai</label>
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end) ?>">
    </div>
    <div class="col-auto align-self-end">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="kelola-transaksi.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>ID User</th>
          <th>Tanggal</th>
          <th>Total Harga</th>
          <th>Status</th>
          <th>Metode</th>
          <th>No Resi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result || $result->num_rows === 0): ?>
          <tr><td colspan="8" class="text-center">Tidak ada transaksi.</td></tr>
        <?php else: ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id_transaksi']) ?></td>
              <td><?= htmlspecialchars($row['id_user']) ?></td>
              <td><?= htmlspecialchars($row['tanggal_transaksi']) ?></td>
              <td>Rp <?= number_format((float)$row['total_harga'],0,',','.') ?></td>
              <td>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <select name="status_pesanan" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($statuses as $s): ?>
                      <option value="<?= htmlspecialchars($s) ?>" <?= $s === $row['status_pesanan'] ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
              <td>
                <form method="post" class="d-flex">
                  <input type="hidden" name="action" value="update_resi">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <input name="no_resi" class="form-control form-control-sm me-1" value="<?= htmlspecialchars($row['no_resi']) ?>" placeholder="kosong jika belum">
                  <button class="btn btn-sm btn-outline-primary">Simpan</button>
                </form>
              </td>
              <td class="text-nowrap">
                <a href="detail-transaksi.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn btn-sm btn-info">Detail</a>

                <form method="post" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <button class="btn btn-sm btn-danger">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted">Menampilkan maksimal <?= $limit ?> transaksi. Gunakan filter atau halaman laporan untuk ekspor.</p>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```// filepath: c:\xampp\htdocs\MobileNest\admin\kelola-transaksi.php
<?php
session_start();
require_once __DIR__ . '/../koneksi.php';

// autentikasi
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// fallback koneksi jika koneksi.php belum menyediakan $conn
if (!isset($conn) || !$conn instanceof mysqli) {
    $conn = new mysqli('localhost', 'root', '', 'mobilenest');
    if ($conn->connect_errno) {
        die('Database connection failed: ' . $conn->connect_error);
    }
}

// handle actions: update status, update resi, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // minimal sanitize / validate
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status' && !empty($_POST['id_transaksi']) && isset($_POST['status_pesanan'])) {
        $id = (int)$_POST['id_transaksi'];
        $status = trim($_POST['status_pesanan']);
        $stmt = $conn->prepare("UPDATE transaksi SET status_pesanan = ?, tanggal_diperbarui = NOW() WHERE id_transaksi = ?");
        if ($stmt) {
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: kelola-transaksi.php');
        exit;
    }

    if ($action === 'update_resi' && !empty($_POST['id_transaksi'])) {
        $id = (int)$_POST['id_transaksi'];
        $no_resi = trim($_POST['no_resi']);
        $stmt = $conn->prepare("UPDATE transaksi SET no_resi = ?, tanggal_diperbarui = NOW() WHERE id_transaksi = ?");
        if ($stmt) {
            $stmt->bind_param('si', $no_resi, $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: kelola-transaksi.php');
        exit;
    }

    if ($action === 'delete' && !empty($_POST['id_transaksi'])) {
        $id = (int)$_POST['id_transaksi'];
        // jika ada tabel terkait (mis. transaksi_items), hapus dulu di sana. contoh:
        // $stmt = $conn->prepare("DELETE FROM transaksi_items WHERE id_transaksi = ?");
        // $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();

        $stmt = $conn->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: kelola-transaksi.php');
        exit;
    }
}

// paging / filter sederhana
$limit = 1000;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// optional filter by status or date range
$where = "1=1";
$params = [];
$types = '';

// filter status
if (!empty($_GET['status'])) {
    $where .= " AND status_pesanan = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// filter date range
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    $where .= " AND tanggal_transaksi BETWEEN ? AND ?";
    $params[] = $start . ' 00:00:00';
    $params[] = $end . ' 23:59:59';
    $types .= 'ss';
}

// prepare query (tidak termasuk LIMIT/OFFSET dalam prepared dengan bind_param untuk MySQLi)
$sql = "SELECT id_transaksi, id_user, total_harga, status_pesanan, metode_pembayaran, alamat_pengiriman, no_resi, tanggal_transaksi, tanggal_diperbarui FROM transaksi WHERE $where ORDER BY tanggal_transaksi DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    // bind dynamic params then limit/offset
    if ($types !== '') {
        // build array for call_user_func_array
        $bind_names[] = $types . 'ii';
        foreach ($params as $k => $v) {
            $bind_names[] = &$params[$k];
        }
        $bind_names[] = &$limit;
        $bind_names[] = &$offset;
        // Note: PHP < 5.6 workaround using call_user_func_array
        $ref = new ReflectionClass('mysqli_stmt');
        $method = $ref->getMethod('bind_param');
        // simpler: use variable argument list via ... operator if available
        // fallback: manual binding below
        // We'll bind manually depending on count:
        // Build types for all params including ii
        $all_types = $types . 'ii';
        $all_params = array_merge($params, [$limit, $offset]);
        // Proper binding using dynamic call:
        $tmp = [];
        $tmp[] = $all_types;
        foreach ($all_params as $k => $v) {
            $tmp[] = &$all_params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $tmp);
    } else {
        // only limit/offset
        $stmt->bind_param('ii', $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = null;
}

// helper statuses (sesuaikan bila ada status lain)
$statuses = ['Menunggu Pembayaran','Diproses','Dikirim','Selesai','Dibatalkan'];

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Transaksi - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">MobileNest Admin</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="kelola-produk.php">Kelola Produk</a></li>
        <li class="nav-item"><a class="nav-link active" href="kelola-transaksi.php">Transaksi</a></li>
        <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="../logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
  <h3>Kelola Transaksi</h3>

  <form class="row g-2 mb-3" method="get" action="kelola-transaksi.php">
    <div class="col-auto">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">Semua</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>" <?= (isset($_GET['status']) && $_GET['status'] === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label">Dari</label>
      <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start) ?>">
    </div>
    <div class="col-auto">
      <label class="form-label">Sampai</label>
      <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end) ?>">
    </div>
    <div class="col-auto align-self-end">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="kelola-transaksi.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>ID User</th>
          <th>Tanggal</th>
          <th>Total Harga</th>
          <th>Status</th>
          <th>Metode</th>
          <th>No Resi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result || $result->num_rows === 0): ?>
          <tr><td colspan="8" class="text-center">Tidak ada transaksi.</td></tr>
        <?php else: ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id_transaksi']) ?></td>
              <td><?= htmlspecialchars($row['id_user']) ?></td>
              <td><?= htmlspecialchars($row['tanggal_transaksi']) ?></td>
              <td>Rp <?= number_format((float)$row['total_harga'],0,',','.') ?></td>
              <td>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <select name="status_pesanan" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($statuses as $s): ?>
                      <option value="<?= htmlspecialchars($s) ?>" <?= $s === $row['status_pesanan'] ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
              <td>
                <form method="post" class="d-flex">
                  <input type="hidden" name="action" value="update_resi">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <input name="no_resi" class="form-control form-control-sm me-1" value="<?= htmlspecialchars($row['no_resi']) ?>" placeholder="kosong jika belum">
                  <button class="btn btn-sm btn-outline-primary">Simpan</button>
                </form>
              </td>
              <td class="text-nowrap">
                <a href="detail-transaksi.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn btn-sm btn-info">Detail</a>

                <form method="post" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_transaksi" value="<?= (int)$row['id_transaksi'] ?>">
                  <button class="btn btn-sm btn-danger">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted">Menampilkan maksimal <?= $limit ?> transaksi. Gunakan filter atau halaman laporan untuk ekspor.</p>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>