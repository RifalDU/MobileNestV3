# 📋 SETUP GUIDE - Pengiriman & Checkout Flow

## ✨ Fitur Baru

Cekout flow yang lengkap dengan 3 tahapan:
1. **Keranjang (Cart)** - Already exists
2. **Pengiriman (Shipping)** - NEW ✨
3. **Pembayaran (Payment)** - NEW ✨
4. **Konfirmasi (Success)** - To be created

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

### 5. **shipping-migration.sql** (Database Migration)
- Tabel `pengiriman` - untuk menyimpan alamat & metode pengiriman
- Tabel `pesanan` - untuk menyimpan order
- Tabel `detail_pesanan` - untuk menyimpan item per order
- Foreign keys dan indexes untuk optimasi

---

## 🗄️ Database Setup

### Step 1: Import SQL Migration

**Using phpMyAdmin:**
```
1. Buka http://localhost/phpmyadmin
2. Pilih database MobileNest
3. Tab "Import"
4. Pilih file database/shipping-migration.sql
5. Klik "Go"
```

**Using MySQL CLI:**
```bash
mysql -u root -p mobilenest < database/shipping-migration.sql
```

**Using VS Code MySQL extension:**
```
1. Right-click database/shipping-migration.sql
2. Click "Run Query"
3. Select MobileNest database
```

### Step 2: Verify Tables Created

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

-- Check foreign keys
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME IN ('pengiriman', 'pesanan', 'detail_pesanan');
```

---

## 📂 File Placement

Pastikan struktur folder seperti ini:

```
MobileNest/
├── transaksi/
│   ├── keranjang.php          ✅ Already exists
│   ├── pengiriman.php         ✨ NEW - Push from GitHub
│   └── pembayaran.php         ✨ NEW - Push from GitHub
│
├── api/
│   ├── cart-handler.php       ✅ Already exists
│   ├── shipping-handler.php   ✨ NEW - Push from GitHub
│   └── payment-handler.php    ✨ NEW - Push from GitHub
│
├── database/
│   └── shipping-migration.sql ✨ NEW - Import to database
│
├── uploads/
│   └── pembayaran/            📁 Create manually
│
└── logs/
    ├── cart_debug.log         ✅ Already exists
    ├── shipping_debug.log     📝 Auto-created by handler
    └── payment_debug.log      📝 Auto-created by handler
```

### Create Folders Manually:

```bash
# Create upload directory for payment proofs
mkdir -p uploads/pembayaran
chmod 755 uploads/pembayaran

# Create logs directory if not exists
mkdir -p logs
chmod 755 logs
```

---

## 🔧 Configuration Checklist

Sebelum testing, pastikan:

- [ ] Database tables created via `shipping-migration.sql`
- [ ] `pengiriman.php` copied to `/transaksi/`
- [ ] `pembayaran.php` copied to `/transaksi/`
- [ ] `shipping-handler.php` copied to `/api/`
- [ ] `payment-handler.php` copied to `/api/`
- [ ] `/uploads/pembayaran/` folder created with 755 permissions
- [ ] `/logs/` folder exists with write permissions
- [ ] Database connection in `config.php` is working
- [ ] Session is enabled in `config.php`

---

## 🔗 Integration Points

### From keranjang.php:

Sudah ada tombol "Lanjut ke Pengiriman" yang mengarah ke `pengiriman.php`:

```html
<a href="pengiriman.php" class="btn btn-primary">Lanjut ke Pengiriman</a>
```

Jika belum ada, tambahkan link tersebut.

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

Pastikan ada di `config.php` atau session:
- `$_SESSION['user_id']` - From login/auth
- `$conn` - Database connection (mysqli)
- `date_default_timezone_set()` - For Indonesia timezone (Asia/Jakarta)

**Add to config.php:**
```php
<?php
date_default_timezone_set('Asia/Jakarta');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mobilenest";

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
1. User sudah login
2. Ada produk di keranjang
3. Database tables sudah created
4. Folders sudah dibuat

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
✓ Check shipping_debug.log untuk error messages
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
✓ Check payment_debug.log untuk error messages
```

### Step 3: Database Verification

Setelah order berhasil, check database:

```sql
-- Check pengiriman data
SELECT * FROM pengiriman 
WHERE id_user = 5 
ORDER BY id_pengiriman DESC 
LIMIT 1;

-- Check pesanan created
SELECT * FROM pesanan 
WHERE id_user = 5 
ORDER BY id_pesanan DESC 
LIMIT 1;

-- Check detail_pesanan
SELECT * FROM detail_pesanan 
WHERE id_pesanan = [ID];

-- Check cart cleared
SELECT COUNT(*) as keranjang_count FROM keranjang 
WHERE id_user = 5;

-- Check bukti_pembayaran file
SELECT no_pesanan, bukti_pembayaran, status_pesanan 
FROM pesanan 
WHERE id_user = 5 
ORDER BY id_pesanan DESC;
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
- Database connection error

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
- Session file tidak ter-create

**Solutions:**
```bash
# Check session directory
ls -la /var/lib/php/sessions/

# Clear session
rm /var/lib/php/sessions/sess_*

# Login again
```

#### Issue 3: File upload fails
**Possible causes:**
- `/uploads/pembayaran/` folder tidak ada
- Folder tidak punya write permission
- File size > 5MB
- File format bukan JPG/PNG
- Server max_upload_size terlalu kecil

**Solutions:**
```bash
# Create folder
mkdir -p uploads/pembayaran
chmod 755 uploads/pembayaran

# Check permissions
ls -la uploads/

# Check php.ini settings
php -r "echo ini_get('upload_max_filesize').PHP_EOL;"
php -r "echo ini_get('post_max_size').PHP_EOL;"

# Check error log
tail logs/payment_debug.log
```

#### Issue 4: Order not created
**Possible causes:**
- Cart kosong
- Pengiriman session tidak ada
- Database transaction rollback
- Foreign key constraint failed

**Solutions:**
```bash
# Check cart items
SELECT COUNT(*) FROM keranjang WHERE id_user = 5;

# Check if session exists
tail logs/payment_debug.log

# Check foreign keys
SELECT * FROM pengiriman WHERE id_pengiriman = [ID];

# Check transaction log
tail logs/payment_debug.log | grep -i "transaction\|error"
```

#### Issue 5: "SQLSTATE[HY000]: General error: 1030 Got error"
**Possible cause:** Disk space full or MySQL config issue

**Solutions:**
```bash
# Check disk space
df -h

# Check MySQL error log
sudo tail /var/log/mysql/error.log
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

### Optional Enhancements:

- Real payment gateway integration (Midtrans, Stripe)
- SMS notifications via Twilio
- Real courier API integration (JNE, Tiki, Pos)
- Multiple address support
- Wishlist feature
- Product reviews & ratings
- Admin dashboard for analytics

---

## 📞 Support Files

### If needed, create these later:

**order-success.php** - Success page after payment
```php
<?php
session_start();
require_once 'config.php';

$id_pesanan = $_GET['id'] ?? null;
$no_pesanan = $_GET['no'] ?? null;

if (!$id_pesanan) {
    header('Location: keranjang.php');
    exit;
}

// Get order details
$sql = "SELECT * FROM pesanan WHERE id_pesanan = '$id_pesanan'";
// ... display order confirmation
?>
```

**order-tracking.php** - Track order status
**order-history.php** - User's past orders
**admin/payment-verification.php** - Admin payment approval panel

---

## ✅ Validation Rules

### Pengiriman Form Validation:

```php
Nama Penerima:
- Required: true
- Max length: 100 characters
- Min length: 3 characters
- Pattern: alphanumeric + spaces

Nomor Telepon:
- Required: true
- Pattern: 10-13 digits
- Valid format: preg_match('/^[0-9]{10,13}$/')

Email:
- Required: true
- Format: valid email
- Filter: FILTER_VALIDATE_EMAIL

Provinsi:
- Required: true
- Max length: 50 characters

Kota:
- Required: true
- Max length: 50 characters

Kecamatan:
- Required: true
- Max length: 50 characters

Kode Pos:
- Required: true
- Pattern: 5-10 digits
- Valid format: preg_match('/^[0-9]{5,10}$/')

Alamat Lengkap:
- Required: true
- Min length: 10 characters
- Max length: 500 characters

Metode Pengiriman:
- Required: true
- Allowed: regular, express, same_day
```

### Pembayaran Form Validation:

```php
Bukti Pembayaran:
- Required: true
- Allowed types: JPG, PNG
- Max size: 5MB (5242880 bytes)
- Min size: 1KB
- Validation: getimagesize()

Nama Pengirim:
- Required: true
- Max length: 100 characters
- Min length: 3 characters

Tanggal Transfer:
- Required: true
- Format: YYYY-MM-DD
- Min date: today
- Max date: +7 days

Metode Pembayaran:
- Required: true
- Allowed: bank_transfer, ewallet, credit_card, cod

Catatan:
- Optional
- Max length: 500 characters
```

---

## 🎓 Learning Resources

- PHP File Upload: https://www.w3schools.com/php/php_file_upload.asp
- MySQL Transactions: https://www.mysqltutorial.org/mysql-transactions.aspx
- jQuery File Upload: https://blueimp.github.io/jQuery-File-Upload/
- Bootstrap 5: https://getbootstrap.com/docs/5.0/
- AJAX with Fetch API: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API

---

## 📊 Database Schema Diagram

```
pengguna (id_user)
    ↓
    ├─→ pengiriman (id_pengiriman, id_user)
    │       ↓
    │       └─→ pesanan (id_pesanan, id_pengiriman, id_user)
    │               ↓
    │               └─→ detail_pesanan (id_detail_pesanan, id_pesanan, id_produk)
    │                       ↓
    │                       └─→ produk (id_produk)
    │
    └─→ keranjang (id_user) → produk (id_produk)
```

---

## ✨ Summary

✅ **Selesai!** Checkout flow sudah siap untuk ditest.

Total files created:
- 2 PHP pages (pengiriman.php, pembayaran.php)
- 2 API handlers (shipping-handler.php, payment-handler.php)
- 1 Database migration (shipping-migration.sql)
- Folders: uploads/pembayaran/, logs/

Untuk pertanyaan atau issues:
1. Check `/logs/` folder untuk detailed error messages
2. Verify database tables dengan query di section "Database Verification"
3. Test step-by-step sesuai "Testing Checklist"
4. Review debug logs untuk error tracking

**Happy Coding! 🎉**

---

**Last Updated:** 2026-01-08  
**Version:** 1.0  
**Status:** Ready for Testing  
