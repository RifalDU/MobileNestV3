# MobileNest Database Schema Documentation

**Database Name:** `mobilenest_db`  
**Last Updated:** December 30, 2025  
**Version:** 1.0

---

## 📊 Database Overview

Database `mobilenest_db` adalah database untuk platform e-commerce MobileNest yang mengelola data produk, pengguna, transaksi, promosi, dan review.

### 📈 Tabel Utama (9 Tables)
1. **admin** - Data administrator sistem
2. **users** - Data pengguna/pelanggan
3. **produk** - Katalog produk
4. **promo** - Program promosi dan diskon
5. **transaksi** - Riwayat transaksi/pesanan
6. **detail_transaksi** - Detail item dalam transaksi
7. **keranjang** - Shopping cart pelanggan
8. **ulasan** - Review dan rating produk
9. **detail_transaksi** - Item breakdown per transaksi

---

## 📋 Struktur Tabel Lengkap

### 1. **users** - Tabel User/Pelanggan
Menyimpan data semua pengguna yang terdaftar di platform.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|-----------|-----------|
| `id_user` | INT | PRIMARY KEY, AUTO_INCREMENT | ID unik user |
| `username` | VARCHAR(50) | UNIQUE, NOT NULL | Username login |
| `password` | VARCHAR(255) | NOT NULL | Password terenkripsi |
| `nama_lengkap` | VARCHAR(100) | NOT NULL | Nama lengkap user |
| `email` | VARCHAR(100) | NOT NULL | Email user |
| `no_telepon` | VARCHAR(15) | | Nomor telepon |
| `alamat` | TEXT | | Alamat lengkap |
| `tanggal_daftar` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Waktu pendaftaran |
| `status_akun` | ENUM('Aktif', 'Nonaktif') | DEFAULT 'Aktif' | Status akun user |

**Sample Data:** 6 users (user1, user2, testing, testing2, salambim, salambim2)

---

### 2. **admin** - Tabel Administrator
Menyimpan data admin yang memiliki akses ke dashboard admin.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|-----------|-----------|
| `id_admin` | INT | PRIMARY KEY, AUTO_INCREMENT | ID unik admin |
| `username` | VARCHAR(50) | UNIQUE, NOT NULL | Username admin |
| `password` | VARCHAR(255) | NOT NULL | Password terenkripsi |
| `nama_lengkap` | VARCHAR(100) | NOT NULL | Nama lengkap admin |
| `email` | VARCHAR(100) | NOT NULL | Email admin |
| `no_telepon` | VARCHAR(15) | | Nomor telepon |
| `tanggal_dibuat` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Waktu akun dibuat |

**Sample Data:** 1 admin (admin)

---

### 3. **produk** - Tabel Produk/Katalog
Menyimpan informasi semua produk yang dijual.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|-----------|-----------|
| `id_produk` | INT | PRIMARY KEY, AUTO_INCREMENT | ID unik produk |
| `nama_produk` | VARCHAR(100) | NOT NULL | Nama produk |
| `merek` | VARCHAR(50) | | Merek/brand produk |
| `deskripsi` | TEXT | | Deskripsi detail produk |
| `spesifikasi` | TEXT | | Spesifikasi teknis produk |
| `harga` | DECIMAL(10,2) | NOT NULL | Harga jual produk |
| `stok` | INT | NOT NULL DEFAULT 0 | Jumlah stok tersedia |
| `gambar` | VARCHAR(255) | | Path/URL gambar produk |
| `kategori` | VARCHAR(50) | | Kategori produk |
| `status_produk` | ENUM('Tersedia', 'Habis') | DEFAULT 'Tersedia' | Status ketersediaan |
| `tanggal_ditambahkan` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Waktu produk ditambah |

**Sample Data:** 13 produk (smartphone: Samsung Galaxy S23, iPhone 14 Pro, Xiaomi Redmi Note, dll)

---

### 4. **promo** - Tabel Program Promosi
Menyimpan data promosi dan program diskon.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|-----------|-----------|
| `id_promo` | INT | PRIMARY KEY, AUTO_INCREMENT | ID unik promo |
| `nama_promo` | VARCHAR(100) | NOT NULL | Nama program promosi |
| `jenis_promo` | VARCHAR(50) | | Jenis diskon (persentase/nominal) |
| `nilai_diskon` | DECIMAL(10,2) | | Nilai diskon yang diberikan |
| `persentase_diskon` | DECIMAL(5,2) | | Persentase diskon (jika %), max 100% |
| `tanggal_mulai` | DATE | | Tanggal mulai promosi |
| `tanggal_selesai` | DATE | | Tanggal akhir promosi |
| `status_promo` | ENUM('Aktif', 'Nonaktif') | DEFAULT 'Aktif' | Status promosi aktif |
| `deskripsi` | TEXT | | Deskripsi lengkap promosi |

**Sample Data:** 2 promo (Diskon Akhir Tahun, Flash Sale Midnight)

---

### 5. **transaksi** - Tabel Transaksi/Pesanan
Menyimpan riwayat transaksi/pesanan dari pengguna.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|-----------|-----------|
| `id_transaksi` | INT | PRIMARY KEY, AUTO_INCREMENT | ID unik transaksi |
| `id_user` | INT | FOREIGN KEY (users.id_user) | ID pengguna |
| `total_harga` | DECIMAL(12,2) | NOT NULL | Total harga transaksi |
| `status_pesanan` | VARCHAR(50) | | Status: Pending, Konfirmasi, Dikirim, Selesai |
| `metode_pembayaran` | VARCHAR(50) | | Metode: Transfer, COD, E-wallet, dll |
| `alamat_pengiriman` | TEXT | | Alamat tujuan pengiriman |
| `no_resi` | VARCHAR(50) | | Nomor resi pengiriman |
| `tanggal_transaksi` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Waktu transaksi |
| `tanggal_dikirim` | DATETIME | | Waktu barang dikirim |
| `kode_transaksi` | VARCHAR(50) | | Kode unik transaksi |
| `catatan_user` | TEXT | | Catatan/note dari pembeli |
| `bukti_pembayaran` | VARCHAR(255) | | Path bukti pembayaran |

**Sample Data:** Kosong (0 rows)

---

### 6. **detail_transaksi** - Tabel Detail Transaksi
Menyimpan breakdown item/produk dalam setiap transaksi.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|-----------|-----------|
| `id_detail` | INT | PRIMARY KEY, AUTO_INCREMENT | ID detail item |
| `id_transaksi` | INT | FOREIGN KEY (transaksi.id_transaksi) | ID transaksi referensi |
| `id_produk` | INT | FOREIGN KEY (produk.id_produk) | ID produk yang dibeli |
| `jumlah` | INT | NOT NULL | Jumlah item yang dibeli |
| `harga_satuan` | DECIMAL(10,2) | NOT NULL | Harga per unit saat pembelian |
| `subtotal` | DECIMAL(12,2) | NOT NULL | Total untuk item ini (jumlah × harga_satuan) |

**Sample Data:** Kosong (0 rows)

---

### 7. **keranjang** - Tabel Shopping Cart
Menyimpan item-item yang ada di keranjang belanja pengguna.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|-----------|-----------|
| `id_keranjang` | INT | PRIMARY KEY, AUTO_INCREMENT | ID unik keranjang |
| `id_user` | INT | FOREIGN KEY (users.id_user) | ID pemilik keranjang |
| `id_produk` | INT | FOREIGN KEY (produk.id_produk) | ID produk di keranjang |
| `jumlah` | INT | NOT NULL DEFAULT 1 | Jumlah item |
| `tanggal_ditambahkan` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Waktu item ditambah |

**Sample Data:** Kosong (0 rows)

---

### 8. **ulasan** - Tabel Review/Rating
Menyimpan review dan rating dari pengguna terhadap produk.

| Kolom | Tipe Data | Constraint | Deskripsi |
|-------|-----------|-----------|-----------|
| `id_ulasan` | INT | PRIMARY KEY, AUTO_INCREMENT | ID unik review |
| `id_user` | INT | FOREIGN KEY (users.id_user) | ID user yang memberi review |
| `id_produk` | INT | FOREIGN KEY (produk.id_produk) | ID produk yang direview |
| `rating` | INT | CHECK (1-5) | Rating 1-5 bintang |
| `komentar` | TEXT | | Komentar/review teks |
| `tanggal_ulasan` | DATETIME | DEFAULT CURRENT_TIMESTAMP | Waktu review dibuat |

**Sample Data:** Kosong (0 rows)

---

## 🔄 Relationships (Foreign Keys)

```
users ──┬──→ transaksi
        ├──→ keranjang
        └──→ ulasan

admin ─→ (standalone table)

produk ──┬──→ detail_transaksi
         ├──→ keranjang
         └──→ ulasan

transaksi ──→ detail_transaksi

promo ──→ (standalone table, linked via business logic)
```

---

## 📌 Index & Performance Tips

### Recommended Indexes
```sql
-- User lookups
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);

-- Product search
CREATE INDEX idx_produk_kategori ON produk(kategori);
CREATE INDEX idx_produk_status ON produk(status_produk);

-- Transaction queries
CREATE INDEX idx_transaksi_id_user ON transaksi(id_user);
CREATE INDEX idx_transaksi_status ON transaksi(status_pesanan);
CREATE INDEX idx_transaksi_tanggal ON transaksi(tanggal_transaksi);

-- Cart operations
CREATE INDEX idx_keranjang_id_user ON keranjang(id_user);

-- Review queries
CREATE INDEX idx_ulasan_id_produk ON ulasan(id_produk);
```

---

## 🛡️ Security Considerations

1. **Password:** Gunakan hashing (bcrypt/SHA-256) sebelum menyimpan
2. **Sensitive Data:** Email dan telepon sebaiknya terenkripsi di production
3. **SQL Injection:** Gunakan prepared statements di semua query
4. **Foreign Keys:** Enforce referential integrity dengan ON DELETE CASCADE/RESTRICT
5. **Data Validation:** Validasi di aplikasi sebelum insert/update ke database

---

## 🔧 Setup Instructions

### 1. Import Database
```bash
# Via phpMyAdmin: Import → pilih file SQL
# Atau via command line:
mysql -u root -p < mobilenest_db.sql
```

### 2. Create Database & Tables
```sql
CREATE DATABASE mobilenest_db;
USE mobilenest_db;

-- Jalankan query di file mobilenest_schema.sql
```

### 3. Verify Connection
```php
// Di config.php
$host = "localhost";
$user = "root";
$password = "";
$database = "mobilenest_db";

$conn = mysqli_connect($host, $user, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
```

---

## 📝 Maintenance & Backup

### Backup Database
```bash
# Backup to SQL file
mysqldump -u root -p mobilenest_db > mobilenest_db_backup_$(date +%Y%m%d).sql

# Restore from backup
mysql -u root -p mobilenest_db < mobilenest_db_backup_20251230.sql
```

### Regular Maintenance
- Check table integrity: `CHECK TABLE table_name;`
- Optimize tables: `OPTIMIZE TABLE table_name;`
- Monitor disk space dan growth rate
- Regular backups (daily/weekly)

---

## 📊 Data Statistics

| Tabel | Rows | Purpose |
|-------|------|---------|
| users | 6 | User accounts |
| admin | 1 | Admin accounts |
| produk | 13 | Product catalog |
| promo | 2 | Active promotions |
| transaksi | 0 | Order history |
| detail_transaksi | 0 | Order items |
| keranjang | 0 | Shopping carts |
| ulasan | 0 | Product reviews |

---

**Created by:** AI Assistant  
**For Project:** MobileNest E-Commerce Platform  
**Status:** Documentation Ready for Integration
