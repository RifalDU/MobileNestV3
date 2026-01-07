# 🧪 Testing Checkout Flow - Complete Guide

**Status**: Database tables created ✅
**Next**: Test pengiriman → pembayaran → order created

---

## ✅ Pre-Testing Checklist

Before testing, verify:

```bash
✅ Database tables created (pengiriman, pesanan, detail_pesanan)
✅ Files pulled from GitHub (git pull origin main)
✅ Folders created:
   - mkdir -p uploads/pembayaran
   - mkdir -p logs
   - chmod 755 uploads/pembayaran logs
✅ Local server running (XAMPP/Laragon/etc)
✅ You're logged in as a user
✅ Cart has at least 1 product
```

---

## 🎯 Testing Flow Overview

```
1. LOGIN
   └─ Verify session['user_id'] set

2. ADD TO CART
   └─ Verify items in keranjang table

3. GO TO PENGIRIMAN.PHP
   └─ Test form pre-fill
   └─ Test shipping method selection
   └─ Test form validation
   └─ Submit → Check shipping-handler.php response

4. GO TO PEMBAYARAN.PHP  
   └─ Verify pengiriman data displayed
   └─ Test payment method selection
   └─ Test file upload
   └─ Submit → Check payment-handler.php response

5. VERIFY DATABASE
   └─ Check pengiriman table
   └─ Check pesanan table
   └─ Check detail_pesanan table
   └─ Check cart cleared

6. CHECK LOGS
   └─ shipping_debug.log
   └─ payment_debug.log
```

---

## 📍 Part 1: Login & Add to Cart

### Step 1a: Login

```
1. Open browser: http://localhost/MobileNestV3
2. Click Login
3. Enter username & password
4. Click Submit
5. Verify: You're logged in (see username in header)
```

**What to check:**
```php
// In your browser console (F12 → Console tab)
console.log(document.cookie);
// Should show: PHPSESSID=xxx...
```

### Step 1b: Add Product to Cart

```
1. Browse products
2. Click "Tambah ke Keranjang" (Add to Cart)
3. Verify: Product added to cart
4. Add 2-3 different products with different quantities
```

**Database check:**
```sql
-- Check in phpMyAdmin
SELECT * FROM keranjang WHERE id_user = [YOUR_USER_ID];

-- Should show: id_user, id_produk, qty, created_at, etc.
```

---

## 📍 Part 2: Test Pengiriman.php (Shipping Form)

### Step 2a: Navigate to Pengiriman Page

```
1. Click "Lanjut ke Pengiriman" button in cart
2. Should redirect to: /transaksi/pengiriman.php
3. Page should load successfully ✅
```

**If error:**
```
Check browser console (F12):
- Any JavaScript errors?
- Network tab: Did pengiriman.php load? (200 status?)
- Check logs/shipping_debug.log for PHP errors
```

### Step 2b: Verify Form Pre-fill

Form should pre-fill with your user data:

```
Nama Penerima: [your name from database]
Nomor Telepon: [your phone from database]
Email: [your email from database]
Provinsi: Jawa Barat (or your province)
Kota: [your city]
Alamat: [your address]
```

**If NOT pre-filled:**
```sql
-- Check your user data
SELECT id_user, nama_pengguna, no_telepon, email, alamat 
FROM pengguna 
WHERE id_user = [YOUR_USER_ID];

-- Should have values in these columns
```

### Step 2c: Test Shipping Method Selection

```
1. View cart summary sidebar (right side)
   Should show:
   - Product list with qty
   - Subtotal: Rp XXX
   - Ongkir: Rp 20.000 (Regular default)
   - Total: Rp XXX

2. Click "Express" shipping option
   Should update:
   - Ongkir: Rp 50.000
   - Total: Rp [subtotal + 50000]

3. Click "Same Day" shipping option
   Should update:
   - Ongkir: Rp 100.000
   - Total: Rp [subtotal + 100000]

4. Click back to "Regular"
   Should update back to Rp 20.000
```

**If calculation wrong:**
```js
// Check in browser console
const subtotal = <?php echo $subtotal; ?>;
const shipping = 50000; // selected method
const total = subtotal + shipping;
console.log('Total should be:', total);
```

### Step 2d: Fill Form (Valid Data)

```
Nama Penerima: John Doe
Nomor Telepon: 082123456789
Email: john@example.com
Provinsi: Jawa Barat
Kota: Bandung
Kecamatan: Cihampelas
Kode Pos: 40141
Alamat Lengkap: Jl. Sudirman No. 123, RT 01, RW 02
Metode Pengiriman: Regular (or your choice)
Catatan: Kirim hati-hati
```

### Step 2e: Submit Form & Check Response

```
1. Click "Lanjut ke Pembayaran" button
2. Check browser Network tab (F12 → Network):
   - POST to /api/shipping-handler.php
   - Status: 200 ✅ (success)
   - Response should be: {"success": true}

3. Page should redirect to pembayaran.php ✅
```

**If error (status 400, 500):**
```
1. Check Network → Response tab
   - What's the error message?
   - Check "message" field in JSON response

2. Check logs/shipping_debug.log:
   tail -f logs/shipping_debug.log
   - Look for [ERROR] messages

3. Common errors:
   - Invalid email format
   - Phone digits < 10 or > 13
   - Postal code not 5-10 digits
   - User not logged in (session invalid)
```

### Step 2f: Verify Database Entry

```sql
-- Check pengiriman table
SELECT * FROM pengiriman 
WHERE id_user = [YOUR_USER_ID] 
ORDER BY id_pengiriman DESC 
LIMIT 1;

-- Should show:
-- id_pengiriman: 1 (or higher)
-- id_user: [YOUR_ID]
-- no_pengiriman: PGR-XXXXX (auto-generated)
-- nama_penerima: John Doe
-- status_pengiriman: Menunggu Verifikasi Pembayaran
-- ongkir: 20000 (or selected amount)
-- created_at: 2026-01-08 03:00:00
```

**If no record:**
```
1. Check shipping_debug.log for error
2. Verify pengiriman table exists:
   SHOW TABLES LIKE 'pengiriman';
3. Verify table structure:
   DESCRIBE pengiriman;
```

---

## 📍 Part 3: Test Pembayaran.php (Payment Form)

### Step 3a: Verify Page Load

```
Page should display:
✅ Progress bar (Step 1 ✓, Step 2 ✓, Step 3 ACTIVE, Step 4)
✅ Countdown timer (24:00:00, counting down)
✅ Pengiriman summary box (nama, alamat, metode)
✅ Payment methods (4 options)
✅ Form fields
✅ Cart summary sidebar (right)
```

### Step 3b: Verify Pengiriman Data Displayed

Sidebar should show:
```
No. Pengiriman: PGR-XXXXX
Status: Menunggu Verifikasi Pembayaran
Metode: Regular (or your choice)
Alamat: [your address]
```

**If not showing:**
```sql
-- Check session is preserved
-- In pengiriman.php, check:
-- if (!isset($_SESSION['id_pengiriman']))

-- Check pengiriman table
SELECT * FROM pengiriman 
WHERE id_pengiriman = [ID from session];
```

### Step 3c: Test Payment Method Selection

```
1. Click "Bank Transfer" option
   - Should highlight with blue border
   - Radio button should be checked

2. Click "E-Wallet" option
   - Should highlight
   - Previous should unhighlight

3. Click "Credit Card" option
4. Click "COD" option
```

### Step 3d: Fill Form with Valid Data

```
Nama Pengirim: John Doe
Tanggal Transfer: [Today's date]
Payment Method: Bank Transfer (select one)
Catatan: Optional - leave blank or add note
```

### Step 3e: Test File Upload

#### Option A: Drag & Drop
```
1. Get a screenshot/photo (JPG or PNG)
2. Make sure < 5MB
3. Drag file into upload area
4. File should appear in preview box
5. Preview shows: filename, file size, remove button
```

#### Option B: Click Upload
```
1. Click in upload area
2. File dialog opens
3. Select JPG or PNG file
4. File should appear in preview
```

**Test validation:**
```
1. Try uploading PNG > 5MB
   - Should error: "max 5MB"

2. Try uploading .txt file
   - Should error: "JPG or PNG only"

3. Try uploading valid JPG < 5MB
   - Should show preview ✅
```

### Step 3f: Submit Form

```
1. Make sure:
   - Payment method selected ✅
   - File uploaded ✅
   - Form filled ✅

2. Click "Konfirmasi & Buat Pesanan" button

3. Check browser Network tab:
   - POST to /api/payment-handler.php
   - Content-Type: multipart/form-data (file upload)
   - Status: 200 ✅

4. Check response:
   {
     "success": true,
     "id_pesanan": 1,
     "no_pesanan": "ORD-XXXXX"
   }

5. Page should redirect to order-success.php
   (or show success message)
```

**If error:**
```
1. Check Network → Response tab
2. Check logs/payment_debug.log:
   tail logs/payment_debug.log
3. Common errors:
   - File upload folder permission
   - Cart empty
   - pengiriman session missing
   - Database transaction failed
```

### Step 3g: Verify File Upload

```bash
# Check if file saved
ls -la uploads/pembayaran/

# Should show:
# pembayaran_[USER_ID]_[TIMESTAMP].jpg
# pembayaran_5_1704700000.jpg (example)

# Check file size
du -h uploads/pembayaran/pembayaran_*.jpg
```

---

## 📍 Part 4: Verify Database Changes

### Step 4a: Check Pesanan Table

```sql
-- Check order created
SELECT * FROM pesanan 
WHERE id_user = [YOUR_USER_ID] 
ORDER BY id_pesanan DESC 
LIMIT 1;

-- Should show:
-- id_pesanan: 1
-- id_user: [YOUR_ID]
-- id_pengiriman: 1
-- no_pesanan: ORD-XXXXX
-- subtotal: 500000 (example)
-- ongkir: 20000
-- total_bayar: 520000
-- status_pesanan: Menunggu Verifikasi
-- metode_pembayaran: bank_transfer
-- bukti_pembayaran: pembayaran_5_1704700000.jpg
-- tanggal_pesanan: 2026-01-08 03:00:00
```

### Step 4b: Check Detail Pesanan Table

```sql
-- Check order items
SELECT * FROM detail_pesanan 
WHERE id_pesanan = [ID from above];

-- Should show multiple rows (one per product):
-- id_detail_pesanan: 1, 2, 3...
-- id_pesanan: 1
-- id_produk: 5
-- nama_produk: "Samsung Galaxy A12"
-- harga: 200000
-- qty: 1
-- subtotal: 200000
```

### Step 4c: Check Cart Cleared

```sql
-- Verify cart is empty
SELECT COUNT(*) as cart_count FROM keranjang 
WHERE id_user = [YOUR_USER_ID];

-- Should return: 0

-- Or detailed check:
SELECT * FROM keranjang 
WHERE id_user = [YOUR_USER_ID];

-- Should return: empty result set (no rows)
```

### Step 4d: Check Pengiriman Updated

```sql
-- Check pengiriman status
SELECT id_pengiriman, status_pengiriman, tanggal_konfirmasi 
FROM pengiriman 
WHERE id_user = [YOUR_USER_ID] 
ORDER BY id_pengiriman DESC LIMIT 1;

-- Should show:
-- status_pengiriman: Menunggu Verifikasi Pembayaran (same)
-- tanggal_konfirmasi: 2026-01-08 03:00:00 (updated)
```

---

## 📊 Database Summary Check

Run this comprehensive query:

```sql
SELECT 
  'Total Pengiriman' as metric,
  COUNT(*) as count
FROM pengiriman
WHERE id_user = [YOUR_USER_ID]
UNION ALL
SELECT 'Total Pesanan', COUNT(*) FROM pesanan WHERE id_user = [YOUR_USER_ID]
UNION ALL
SELECT 'Total Detail Pesanan', COUNT(*) FROM detail_pesanan 
WHERE id_pesanan IN (
  SELECT id_pesanan FROM pesanan WHERE id_user = [YOUR_USER_ID]
)
UNION ALL
SELECT 'Cart Items Left', COUNT(*) FROM keranjang WHERE id_user = [YOUR_USER_ID];

-- Expected:
-- Total Pengiriman: 1
-- Total Pesanan: 1
-- Total Detail Pesanan: 2-3 (or however many products)
-- Cart Items Left: 0
```

---

## 📋 Testing Checklist

### ✅ Pengiriman Form Tests

```
☐ Page loads successfully
☐ Form pre-fills with user data
☐ Shipping methods display correctly
☐ Shipping cost updates when method changes
☐ Form validates on submit:
  ☐ Email format validation
  ☐ Phone number length validation
  ☐ Postal code format validation
  ☐ Required fields check
☐ Success redirects to pembayaran.php
☐ Data saved to pengiriman table
☐ Session created for next step
```

### ✅ Pembayaran Form Tests

```
☐ Page loads successfully
☐ Pengiriman data displays correctly
☐ Countdown timer runs (24:00:00 → 23:59:59)
☐ Payment methods display
☐ Can select payment method
☐ File upload area works
☐ Drag & drop works
☐ File validation works:
  ☐ Max 5MB check
  ☐ JPG/PNG only check
  ☐ File preview shows
☐ Form validates on submit:
  ☐ Payment method required
  ☐ File required
  ☐ All fields filled
☐ Success creates pesanan record
☐ Data saved to detail_pesanan table
☐ Cart cleared
☐ File saved to uploads/pembayaran/
```

### ✅ Database Tests

```
☐ pengiriman table has 1 new row
☐ pengiriman.id_user matches logged-in user
☐ pengiriman.status_pengiriman correct
☐ pengiriman.ongkir matches selected shipping
☐ pengiriman.no_pengiriman auto-generated
☐ pesanan table has 1 new row
☐ pesanan.id_pengiriman matches pengiriman.id_pengiriman
☐ pesanan.no_pesanan auto-generated
☐ pesanan.total_bayar = subtotal + ongkir - diskon
☐ pesanan.bukti_pembayaran filename correct
☐ pesanan.metode_pembayaran matches selection
☐ detail_pesanan has correct number of rows
☐ detail_pesanan.id_pesanan correct
☐ detail_pesanan.qty matches cart qty
☐ detail_pesanan.subtotal = harga × qty
☐ keranjang emptied (count = 0)
```

---

## 🐛 Troubleshooting Quick Reference

### pengiriman.php won't load
```bash
# Check:
1. File exists: transaksi/pengiriman.php
2. Check logs/shipping_debug.log
3. Browser console (F12) for errors
4. URL is: /transaksi/pengiriman.php (not /transaksi/pengiriman)
```

### Form won't submit
```bash
# Check:
1. Browser console for JavaScript errors
2. Network tab (F12) → see request/response
3. Check all required fields filled
4. Check form validation in console:
   const form = document.getElementById('shippingForm');
   console.log(form.checkValidity());
```

### shipping-handler.php error
```bash
# Check:
1. logs/shipping_debug.log for SQL error
2. Database connection working
3. pengiriman table exists
4. User ID in session
```

### File upload not working
```bash
# Check:
1. uploads/pembayaran/ folder exists
2. Folder has write permission (755)
3. File < 5MB
4. File format JPG or PNG
5. logs/payment_debug.log
```

### Cart not clearing
```bash
# Check:
1. logs/payment_debug.log for DELETE error
2. Verify keranjang table exists
3. Check user ID matches
```

---

## 📝 Test Report Template

Use this to document your test results:

```markdown
# Test Report - Checkout Flow

**Date**: 2026-01-08
**Tester**: [Your Name]
**User ID**: 5

## Login & Cart
- [x] Login successful
- [x] Product added to cart
- [x] Cart shows correct total

## Pengiriman Form
- [x] Page loads
- [x] Form pre-fills
- [x] Shipping cost updates
- [x] Form submits successfully
- [x] Redirects to pembayaran.php
- [ ] Error: [describe if any]

## Pembayaran Form
- [x] Page loads
- [x] Data displays
- [x] File uploads
- [x] Form submits
- [ ] Error: [describe if any]

## Database
- [x] pengiriman table: 1 row added
- [x] pesanan table: 1 row added
- [x] detail_pesanan table: 2 rows added
- [x] keranjang table: cleared
- [x] File saved to uploads/pembayaran/

## Overall Result
✅ PASSED / ❌ FAILED

## Notes
[Any observations or issues]
```

---

## ✨ Next After Successful Test

If all tests pass:

```
1. Create order-success.php (confirmation page)
2. Create admin verification panel
3. Add email notifications
4. Create order tracking page
5. Add order history
```

---

**Happy Testing! 🧪🎉**

If you encounter errors, check logs first, then reference this guide's troubleshooting section.
