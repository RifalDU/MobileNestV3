# 📋 SETUP GUIDE - Pengiriman & Checkout Flow

## ⚡ Quick Start

**Jika database setup error, baca section "🔴 Foreign Key Error" dulu!**

---

## 🎯 Database Files Available

Ada 3 pilihan SQL file di folder `/database/`:

| File | Deskripsi | Gunakan Jika |
|------|-----------|---------------|
| `shipping-migration.sql` | **RECOMMENDED** - With FK | Database sudah pernah buat FK |
| `shipping-migration-no-fk.sql` | Without FK - Safer | Dapat FK error #1005 |
| `DEBUG-FOREIGN-KEY.sql` | Diagnostic queries | Perlu diagnosa error |

---

## 🔴 Foreign Key Error (ERRNO 1005)

**Error message:**
```
#1005 - Can't create table `mobilenest_db`.`pengiriman` 
(errno: 150 "Foreign key constraint is incorrectly formed")
```

### 🔧 Quick Fix (3 Steps)

#### Step 1: Check User Table Name

Jalankan query di phpMyAdmin:
```sql
SHOW TABLES LIKE 'pengguna';
SHOW TABLES LIKE 'users';
SHOW TABLES LIKE 'user';
```

Cari mana yang ada. Catat nama table-nya!

#### Step 2: Try No-FK Version

Buka file **`database/shipping-migration-no-fk.sql`** dan jalankan di phpMyAdmin. Ini versi tanpa foreign key yang lebih stabil.

**Langkah:**
1. Buka `database/shipping-migration-no-fk.sql` dari GitHub
2. Copy semua SQL
3. Buka phpMyAdmin
4. Tab **SQL**
5. Paste SQL
6. Klik **Go**

✅ Jika berhasil, skip ke "🔗 Integration Points"

#### Step 3: Jika Masih Error

Jalankan `database/DEBUG-FOREIGN-KEY.sql` untuk diagnosa:

1. Buka file `database/DEBUG-FOREIGN-KEY.sql`
2. Jalankan query 1-12 di phpMyAdmin
3. Catat hasil:
   - Nama table user (pengguna? users?)
   - Tipe id_user (INT? BIGINT?)
   - Engine (InnoDB? MyISAM?)

4. Edit `shipping-migration.sql`:
   - Ganti `pengguna` dengan nama table sebenarnya
   - Pastikan tipe data sesuai

5. Drop existing tables (jika perlu):
   ```sql
   DROP TABLE IF EXISTS `detail_pesanan`;
   DROP TABLE IF EXISTS `pesanan`;
   DROP TABLE IF EXISTS `pengiriman`;
   ```

6. Run SQL lagi

**Masih error?** Hubungi dengan screenshot error + hasil DEBUG query.

---

## 📁 Files Created

### 1. **pengiriman.php** (`/transaksi/pengiriman.php`)
- Halaman input data pengiriman (Step 2 dari checkout)
- Form validasi address, pilihan kurir, catatan
- Preview cart items & order summary di sidebar
- Styling konsisten dengan keranjang.php
- Real-time ongkir calculation

### 2. **pembayaran.php** (`/transaksi/pembayaran.php`)
- Halaman konfirmasi pembayaran (Step 3 dari checkout)
- Pilihan metode pembayaran (Bank Transfer, E-Wallet, Credit Card, COD)
- Upload bukti pembayaran dengan validasi
- Countdown timer 24 jam
- Rekap semua data pengiriman & pesanan

### 3. **shipping-handler.php** (`/api/shipping-handler.php`)
- API endpoint untuk menyimpan data pengiriman
- Validasi email, nomor telepon, kode pos
- Hitung ongkir berdasarkan metode pengiriman
- Simpan ke tabel `pengiriman`
- Simpan ke SESSION untuk pembayaran

### 4. **payment-handler.php** (`/api/payment-handler.php`)
- API endpoint untuk konfirmasi pembayaran
- Upload & validasi file bukti pembayaran
- Create order record di tabel `pesanan`
- Insert detail pesanan di tabel `detail_pesanan`
- Clear keranjang setelah order dibuat
- Update status pengiriman
- Database transaction dengan rollback on error

### 5. **SQL Migration Files** (`/database/`)

- **shipping-migration.sql** - Full version with FK
- **shipping-migration-no-fk.sql** - Safe version without FK
- **DEBUG-FOREIGN-KEY.sql** - Diagnostic queries

---

## 🗄️ Database Setup

### Step 1: Choose SQL File

**Recommended workflow:**

```
Try: shipping-migration.sql
  ↓
If FK error #1005:
  ↓
Switch to: shipping-migration-no-fk.sql
  ↓
Still error?
  ↓
Run: DEBUG-FOREIGN-KEY.sql (diagnose)
```

### Step 2: Import SQL Migration

**Via phpMyAdmin:**
```
1. Buka http://localhost/phpmyadmin
2. Pilih database MobileNest (atau mobilenest_db)
3. Tab "Import"
4. Browse file shipping-migration.sql (atau no-fk version)
5. Klik "Go"
```

**Via MySQL CLI:**
```bash
# With FK
mysql -u root -p mobilenest_db < database/shipping-migration.sql

# Without FK (if error)
mysql -u root -p mobilenest_db < database/shipping-migration-no-fk.sql
```

### Step 3: Verify Tables Created

Run these SQL queries to verify:
```sql
-- Check if tables exist
SHOW TABLES LIKE 'pengiriman';
SHOW TABLES LIKE 'pesanan';
SHOW TABLES LIKE 'detail_pesanan';

-- Check table structure
DESCRIBE pengiriman;
DESCRIBE pesanan;
DESCRIBE detail_pesanan;

-- Check row count (should be 0 at first)
SELECT COUNT(*) FROM pengiriman;
SELECT COUNT(*) FROM pesanan;
SELECT COUNT(*) FROM detail_pesanan;
```

---

## 📂 File Placement

Pastikan struktur folder seperti ini:

```
MobileNest/
├── transaksi/
│   ├── keranjang.php          ✅ Already exists
│   ├── pengiriman.php         ✨ NEW (from GitHub)
│   └── pembayaran.php         ✨ NEW (from GitHub)
│
├── api/
│   ├── cart-handler.php       ✅ Already exists
│   ├── shipping-handler.php   ✨ NEW (from GitHub)
│   └── payment-handler.php    ✨ NEW (from GitHub)
│
├── database/
│   ├── shipping-migration.sql
│   ├── shipping-migration-no-fk.sql
│   └── DEBUG-FOREIGN-KEY.sql
│
├── uploads/
│   └── pembayaran/            📁 Create manually
│
├── logs/
│   ├── cart_debug.log
│   ├── shipping_debug.log     📝 Auto-created
│   └── payment_debug.log      📝 Auto-created
│
└── config.php                 ✅ Already exists
```

### Create Folders Manually:

```bash
mkdir -p uploads/pembayaran
mkdir -p logs
chmod 755 uploads/pembayaran
chmod 755 logs
```

---

## 🔧 Configuration Checklist

- [ ] Database tables created (pengiriman, pesanan, detail_pesanan)
- [ ] pengiriman.php copied to `/transaksi/`
- [ ] pembayaran.php copied to `/transaksi/`
- [ ] shipping-handler.php copied to `/api/`
- [ ] payment-handler.php copied to `/api/`
- [ ] `/uploads/pembayaran/` folder created with 755 permissions
- [ ] `/logs/` folder exists with write permissions
- [ ] Database connection in `config.php` is working
- [ ] Session is enabled (session_start() in config.php)
- [ ] Files pulled from GitHub (git pull origin main)

---

## 🔗 Integration Points

### From keranjang.php:

Tombol "Lanjut ke Pengiriman" harus ada:

```html
<a href="pengiriman.php" class="btn btn-primary">Lanjut ke Pengiriman</a>
```

Jika belum ada, tambahkan link tersebut di halaman keranjang.

### Session Flow:

```
User Login (session['user_id'] set)
        ↓
keranjang.php (view cart items)
        ↓
Pengiriman.php (enter shipping address)
        ├─→ API: shipping-handler.php
        ├─→ Save to DB: pengiriman table
        ├─→ Save to SESSION: id_pengiriman, ongkir
        ↓
pembayaran.php (select payment method & upload proof)
        ├─→ API: payment-handler.php
        ├─→ Create DB: pesanan, detail_pesanan
        ├─→ Clear: keranjang table
        ├─→ Update: pengiriman status
        ↓
order-success.php (confirmation page)
```

### Environment Variables Needed:

Pastikan ada di `config.php`:
```php
<?php
date_default_timezone_set('Asia/Jakarta');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mobilenest_db"; // ← Sesuaikan dengan nama database Anda

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
session_start();
?>
```

---

## 🧪 Testing Checklist

### Pre-Testing Setup:
1. ✅ User sudah login
2. ✅ Ada produk di keranjang
3. ✅ Database tables sudah created
4. ✅ Folders sudah dibuat
5. ✅ Files sudah di-copy dari GitHub

### Step 1: Test Pengiriman Page

```
✓ Load pengiriman.php berhasil
✓ Form pre-fill dari user data (nama, email)
✓ Shipping method selection working (Regular, Express, Same Day)
✓ Cost calculation updating saat pilih metode
✓ Form submit mengirim ke API
✓ Validasi form (email format, phone digits)
✓ Error messages muncul untuk invalid input
✓ Success redirect ke pembayaran.php
```

### Step 2: Test Pembayaran Page

```
✓ Load pembayaran.php berhasil
✓ All shipping data displayed correctly
✓ Payment method selection working (4 methods)
✓ File upload working dengan drag & drop
✓ File preview showing setelah upload
✓ Countdown timer running (24:00:00 → 23:59:59)
✓ Form validation working
✓ File size check (max 5MB)
✓ File type check (JPG/PNG only)
✓ Submit creates pesanan record
```

### Step 3: Database Verification

Setelah order berhasil, check database:

```sql
-- Check pengiriman data
SELECT * FROM pengiriman 
WHERE id_user = 5 
ORDER BY id_pengiriman DESC LIMIT 1;

-- Check pesanan created
SELECT * FROM pesanan 
WHERE id_user = 5 
ORDER BY id_pesanan DESC LIMIT 1;

-- Check detail_pesanan
SELECT * FROM detail_pesanan 
WHERE id_pesanan = [ID];

-- Check cart cleared
SELECT COUNT(*) as keranjang_count FROM keranjang 
WHERE id_user = 5;

-- Check bukti_pembayaran file saved
SELECT no_pesanan, bukti_pembayaran, status_pesanan 
FROM pesanan 
WHERE id_user = 5 
ORDER BY id_pesanan DESC LIMIT 1;
```

---

## 🐛 Debugging

### Check Debug Logs:

```bash
# View shipping handler logs
tail -f logs/shipping_debug.log

# View payment handler logs
tail -f logs/payment_debug.log

# Watch in real-time
watch -n 1 tail logs/payment_debug.log
```

### Common Issues & Solutions:

#### Issue 1: "Gagal menyimpan data pengiriman"
**Possible causes:**
- `pengiriman` table tidak ada di database
- Foreign key constraint failed (user_id tidak ada)
- User belum login (id_user null)

**Solutions:**
```bash
# Check if table exists
SHOW TABLES LIKE 'pengiriman';

# Check table structure
DESCRIBE pengiriman;

# Check user exists
SELECT * FROM pengguna WHERE id_user = 5;

# Check for query errors
tail logs/shipping_debug.log
```

#### Issue 2: "Session tidak valid"
**Possible causes:**
- User tidak login sebelum ke pengiriman.php
- Session timeout
- Browser cookies cleared

**Solutions:**
- Login ulang
- Clear browser cache
- Check session is enabled in config.php

#### Issue 3: File upload fails
**Possible causes:**
- `/uploads/pembayaran/` folder tidak ada
- Folder tidak punya write permission
- File size > 5MB
- File format bukan JPG/PNG

**Solutions:**
```bash
# Create folder
mkdir -p uploads/pembayaran
chmod 755 uploads/pembayaran

# Check permissions
ls -la uploads/

# Check error log
tail logs/payment_debug.log
```

#### Issue 4: Order not created
**Possible causes:**
- Cart kosong
- Pengiriman session tidak ada
- Database transaction rollback

**Solutions:**
```bash
# Check cart items
SELECT COUNT(*) FROM keranjang WHERE id_user = 5;

# Check if session exists
tail logs/payment_debug.log

# Check foreign keys
SELECT * FROM pengiriman WHERE id_pengiriman = [ID];
```

---

## 📝 Next Steps

### After Successful Testing:

1. **Create order-success.php**
   - Display order confirmation
   - Show no_pesanan & order details
   - Link to order tracking page

2. **Create admin panel for payment verification**
   - List pending payments
   - Approve/reject payments
   - Upload payment verification status

3. **Add email notifications**
   - Order confirmation email
   - Payment received email
   - Shipment tracking email

4. **Create order tracking page**
   - Show order status
   - Show shipping details
   - Show estimated delivery date

5. **Add order history page**
   - List all user orders
   - Filter by status
   - Download invoice

---

## 📞 Support

**Jika masih ada error:**

1. Check `/logs/` folder untuk detailed error messages
2. Run `DEBUG-FOREIGN-KEY.sql` untuk diagnosa
3. Verify database tables dengan query di "Database Verification"
4. Test step-by-step sesuai "Testing Checklist"
5. Review debug logs untuk error tracking

---

**Happy Coding! 🎉**

**Last Updated:** 2026-01-08  
**Version:** 2.0 (Fixed FK Issues)  
**Status:** Ready for Testing  
