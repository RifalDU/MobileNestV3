<?php
session_start();
require_once __DIR__ . '/../config.php';

// autentikasi
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header('Location: ../user/login.php');
    exit;
}

// fallback koneksi
if (!isset($conn) || !$conn instanceof mysqli) {
    $conn = new mysqli('localhost', 'root', '', 'mobilenest');
    if ($conn->connect_errno) {
        die('Database connection failed: ' . $conn->connect_error);
    }
}

// handle actions: add, edit, delete
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD produk baru
    if ($action === 'add') {
        $nama_produk = trim($_POST['nama_produk'] ?? '');
        $merek = trim($_POST['merek'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $spesifikasi = trim($_POST['spesifikasi'] ?? '');
        $harga = !empty($_POST['harga']) ? (float)$_POST['harga'] : 0;
        $stok = !empty($_POST['stok']) ? (int)$_POST['stok'] : 0;
        $gambar = trim($_POST['gambar'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $status_produk = trim($_POST['status_produk'] ?? 'Tersedia');

        if (empty($nama_produk) || $harga <= 0) {
            $message = 'Nama produk dan harga tidak boleh kosong.';
            $msg_type = 'danger';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO produk (nama_produk, merek, deskripsi, spesifikasi, harga, stok, gambar, kategori, status_produk, tanggal_ditambahkan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            if ($stmt) {
                $stmt->bind_param('ssssdisss', $nama_produk, $merek, $deskripsi, $spesifikasi, $harga, $stok, $gambar, $kategori, $status_produk);
                if ($stmt->execute()) {
                    $message = 'Produk berhasil ditambahkan.';
                    $msg_type = 'success';
                } else {
                    $message = 'Error: ' . $stmt->error;
                    $msg_type = 'danger';
                }
                $stmt->close();
            }
        }
    }

    // EDIT produk
    if ($action === 'edit') {
        $id_produk = (int)$_POST['id_produk'];
        $nama_produk = trim($_POST['nama_produk'] ?? '');
        $merek = trim($_POST['merek'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $spesifikasi = trim($_POST['spesifikasi'] ?? '');
        $harga = !empty($_POST['harga']) ? (float)$_POST['harga'] : 0;
        $stok = !empty($_POST['stok']) ? (int)$_POST['stok'] : 0;
        $gambar = trim($_POST['gambar'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $status_produk = trim($_POST['status_produk'] ?? 'Tersedia');

        if (empty($nama_produk) || $harga <= 0 || $id_produk <= 0) {
            $message = 'Data produk tidak valid.';
            $msg_type = 'danger';
        } else {
            $stmt = $conn->prepare(
                "UPDATE produk SET nama_produk=?, merek=?, deskripsi=?, spesifikasi=?, harga=?, stok=?, gambar=?, kategori=?, status_produk=? WHERE id_produk=?"
            );
            if ($stmt) {
                $stmt->bind_param('ssssdisssi', $nama_produk, $merek, $deskripsi, $spesifikasi, $harga, $stok, $gambar, $kategori, $status_produk, $id_produk);
                if ($stmt->execute()) {
                    $message = 'Produk berhasil diperbarui.';
                    $msg_type = 'success';
                } else {
                    $message = 'Error: ' . $stmt->error;
                    $msg_type = 'danger';
                }
                $stmt->close();
            }
        }
    }

    // DELETE produk
    if ($action === 'delete') {
        $id_produk = (int)$_POST['id_produk'];
        $stmt = $conn->prepare("DELETE FROM produk WHERE id_produk=?");
        if ($stmt) {
            $stmt->bind_param('i', $id_produk);
            if ($stmt->execute()) {
                $message = 'Produk berhasil dihapus.';
                $msg_type = 'success';
            } else {
                $message = 'Error: ' . $stmt->error;
                $msg_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// fetch produk untuk list
$limit = 1000;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// filter optional
$where = "1=1";
$params = [];
$types = '';

if (!empty($_GET['kategori'])) {
    $where .= " AND kategori = ?";
    $params[] = $_GET['kategori'];
    $types .= 's';
}

if (!empty($_GET['status'])) {
    $where .= " AND status_produk = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where .= " AND (nama_produk LIKE ? OR merek LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= 'ss';
}

// query list produk
$sql = "SELECT id_produk, nama_produk, merek, harga, stok, gambar, kategori, status_produk, tanggal_ditambahkan FROM produk WHERE $where ORDER BY tanggal_ditambahkan DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$result = null;

if ($stmt) {
    $all_types = $types . 'ii';
    $all_params = array_merge($params, [$limit, $offset]);
    
    $tmp = [$all_types];
    foreach ($all_params as $k => $v) {
        $tmp[] = &$all_params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $tmp);
    
    $stmt->execute();
    $result = $stmt->get_result();
}

// fetch unique categories & status
$categories = [];
$statuses = ['Tersedia', 'Tidak Tersedia'];

$cat_query = $conn->query("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
if ($cat_query) {
    while ($row = $cat_query->fetch_assoc()) {
        $categories[] = $row['kategori'];
    }
}

// modal edit form (fetch produk jika ada id di GET)
$edit_produk = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT id_produk, nama_produk, merek, deskripsi, spesifikasi, harga, stok, gambar, kategori, status_produk FROM produk WHERE id_produk=?");
    if ($stmt) {
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $edit_produk = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Produk - MobileNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">MobileNest Admin</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="kelola-produk.php">Kelola Produk</a></li>
        <li class="nav-item"><a class="nav-link" href="kelola-transaksi.php">Transaksi</a></li>
        <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="../user/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
  <h3>Kelola Produk</h3>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?= htmlspecialchars($msg_type) ?> alert-dismissible fade show">
      <?= htmlspecialchars($message) ?>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">+ Tambah Produk</button>

  <form class="row g-2 mb-3" method="get" action="kelola-produk.php">
    <div class="col-auto">
      <input type="search" name="search" class="form-control" placeholder="Cari nama/merek..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    </div>
    <div class="col-auto">
      <select name="kategori" class="form-select">
        <option value="">Semua Kategori</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($_GET['kategori']) && $_GET['kategori'] === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>" <?= (isset($_GET['status']) && $_GET['status'] === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="kelola-produk.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Gambar</th>
          <th>Nama Produk</th>
          <th>Merek</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result || $result->num_rows === 0): ?>
          <tr><td colspan="9" class="text-center">Tidak ada produk.</td></tr>
        <?php else: ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$row['id_produk'] ?></td>
              <td>
                <?php if (!empty($row['gambar'])): ?>
                  <img src="<?= htmlspecialchars($row['gambar']) ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>" style="max-width:80px;max-height:80px;">
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['nama_produk']) ?></td>
              <td><?= htmlspecialchars($row['merek']) ?></td>
              <td><?= htmlspecialchars($row['kategori']) ?></td>
              <td>Rp <?= number_format((float)$row['harga'],0,',','.') ?></td>
              <td><?= (int)$row['stok'] ?></td>
              <td>
                <span class="badge bg-<?= $row['status_produk'] === 'Tersedia' ? 'success' : 'secondary' ?>">
                  <?= htmlspecialchars($row['status_produk']) ?>
                </span>
              </td>
              <td class="text-nowrap">
                <a href="?edit=<?= (int)$row['id_produk'] ?>" class="btn btn-sm btn-warning">Edit</a>

                <form method="post" class="d-inline" onsubmit="return confirm('Hapus produk ini?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_produk" value="<?= (int)$row['id_produk'] ?>">
                  <button class="btn btn-sm btn-danger">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-muted">Menampilkan maksimal <?= $limit ?> produk.</p>
</main>

<!-- Modal Tambah Produk -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Produk Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Nama Produk *</label>
            <input type="text" name="nama_produk" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Merek</label>
            <input type="text" name="merek" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label">Spesifikasi</label>
            <textarea name="spesifikasi" class="form-control" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Harga *</label>
              <input type="number" name="harga" class="form-control" step="0.01" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Stok</label>
              <input type="number" name="stok" class="form-control" value="0">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label">Gambar URL</label>
            <input type="text" name="gambar" class="form-control" placeholder="https://...">
          </div>
          <div class="row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Kategori</label>
              <input type="text" name="kategori" class="form-control" placeholder="mis. Flagship">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Status</label>
              <select name="status_produk" class="form-select">
                <option value="Tersedia">Tersedia</option>
                <option value="Tidak Tersedia">Tidak Tersedia</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Produk -->
<?php if ($edit_produk): ?>
<div class="modal fade show" id="editModal" tabindex="-1" style="display: block;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id_produk" value="<?= (int)$edit_produk['id_produk'] ?>">
        <div class="modal-header">
          <h5 class="modal-title">Edit Produk</h5>
          <a href="kelola-produk.php" class="btn-close"></a>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Nama Produk *</label>
            <input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($edit_produk['nama_produk']) ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Merek</label>
            <input type="text" name="merek" class="form-control" value="<?= htmlspecialchars($edit_produk['merek']) ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="2"><?= htmlspecialchars($edit_produk['deskripsi']) ?></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label">Spesifikasi</label>
            <textarea name="spesifikasi" class="form-control" rows="2"><?= htmlspecialchars($edit_produk['spesifikasi']) ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Harga *</label>
              <input type="number" name="harga" class="form-control" step="0.01" value="<?= (float)$edit_produk['harga'] ?>" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Stok</label>
              <input type="number" name="stok" class="form-control" value="<?= (int)$edit_produk['stok'] ?>">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label">Gambar URL</label>
            <input type="text" name="gambar" class="form-control" value="<?= htmlspecialchars($edit_produk['gambar']) ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Kategori</label>
              <input type="text" name="kategori" class="form-control" value="<?= htmlspecialchars($edit_produk['kategori']) ?>">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Status</label>
              <select name="status_produk" class="form-select">
                <option value="Tersedia" <?= $edit_produk['status_produk'] === 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                <option value="Tidak Tersedia" <?= $edit_produk['status_produk'] === 'Tidak Tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a href="kelola-produk.php" class="btn btn-secondary">Batal</a>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>