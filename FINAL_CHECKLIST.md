# ✅ FINAL CHECKLIST - Checkout Flow Setup & Testing

**Last Updated**: 2026-01-08
**Status**: Ready for Testing

---

## 💩 PHASE 1: Setup & Configuration

### Database

```
☐ Database tables created in mobilenest_db:
   ☐ SHOW TABLES LIKE 'pengiriman';
   ☐ SHOW TABLES LIKE 'pesanan';
   ☐ SHOW TABLES LIKE 'detail_pesanan';
   ☐ All 3 tables exist ✅
```

### File Structure

```
☐ Code files in correct locations:
   ☐ transaksi/pengiriman.php exists
   ☐ transaksi/pembayaran.php exists
   ☐ api/shipping-handler.php exists
   ☐ api/payment-handler.php exists
☐ All 4 PHP files present ✅
```

### Folders & Permissions

```bash
☐ Create folders:
   mkdir -p uploads/pembayaran logs
   chmod 755 uploads/pembayaran logs

☐ Verify permissions:
   ls -la uploads/ | grep pembayaran  # Should show drwxr-xr-x
   ls -la logs/                        # Should show drwxr-xr-x
☐ Folders created with correct permissions ✅
```

### Pull Latest Code

```bash
☐ cd MobileNestV3
☐ git pull origin main
☐ git status  # Should show "On branch main, up to date"
☐ Latest code pulled ✅
```

---

## 🚀 PHASE 2: Login & Cart Preparation

### User Login

```
☐ Open: http://localhost/MobileNestV3
☐ Login with your account
☐ Verify: Username shows in header
☐ Session: PHPSESSID cookie created
☐ User logged in successfully ✅
```

### Add Products to Cart

```
☐ Browse products page
☐ Click "Tambah ke Keranjang" (Add to Cart)
☐ Add 2-3 different products with different quantities
☐ Verify cart shows correct count
☐ Check database:
   SELECT * FROM keranjang WHERE id_user = [YOUR_ID];
   Should show: 2-3 rows with id_produk, qty
☐ Products added to cart ✅
```

---

## 💰 PHASE 3: Test Pengiriman.php (Shipping Form)

### Page Load

```
☐ Click "Lanjut ke Pengiriman" button
☐ URL should be: /transaksi/pengiriman.php
☐ Page loads without errors
☐ Check browser console (F12): No errors shown
☐ Page loaded successfully ✅
```

### Form Pre-fill

```
☐ Check form fields are pre-filled:
   ☐ Nama Penerima: [shows your name]
   ☐ Nomor Telepon: [shows your phone]
   ☐ Email: [shows your email]
   ☐ Provinsi: [shows your province]
   ☐ Kota: [shows your city]
   ☐ Alamat: [shows your address]
☐ All fields pre-filled from database ✅
```

### Cart Summary Display

```
☐ Right sidebar shows cart items:
   ☐ Product names visible
   ☐ Quantities shown
   ☐ Subtotal calculated
   ☐ Ongkir (shipping) shown: Rp 20,000 (Regular default)
   ☐ Total: Rp [subtotal + 20000]
☐ Cart summary displayed correctly ✅
```

### Shipping Method Selection

```
☐ Click "Regular" option
   ☐ Ongkir updates to: Rp 20,000
   ☐ Total updates: Rp [subtotal + 20000]

☐ Click "Express" option
   ☐ Ongkir updates to: Rp 50,000
   ☐ Total updates: Rp [subtotal + 50000]

☐ Click "Same Day" option
   ☐ Ongkir updates to: Rp 100,000
   ☐ Total updates: Rp [subtotal + 100000]

☐ Shipping method selection works ✅
```

### Form Validation

```
☐ Clear email field, try submit
   ☐ Should show: "Email harus diisi"

☐ Clear phone field, try submit
   ☐ Should show: "Nomor telepon harus diisi"

☐ Enter invalid phone (5 digits), try submit
   ☐ Should show: "Nomor telepon harus 10-13 digit"

☐ Clear postal code, try submit
   ☐ Should show: "Kode pos harus diisi"

☐ Enter invalid postal code (3 digits), try submit
   ☐ Should show: "Kode pos harus 5-10 digit"

☐ Form validation working correctly ✅
```

### Form Submission

```
☐ Fill all fields with valid data:
   Nama Penerima: John Doe
   Nomor Telepon: 082123456789
   Email: john@example.com
   Provinsi: Jawa Barat
   Kota: Bandung
   Kecamatan: Cihampelas
   Kode Pos: 40141
   Alamat Lengkap: Jl. Sudirman No. 123
   Metode: Regular (selected)

☐ Click "Lanjut ke Pembayaran" button
☐ Check Network tab (F12 > Network):
   ☐ POST request to /api/shipping-handler.php
   ☐ Status: 200 (success)
   ☐ Response: {"success": true}

☐ Page redirects to pembayaran.php ✅
```

### Database Verification (Pengiriman)

```sql
SELECT * FROM pengiriman 
WHERE id_user = [YOUR_ID] 
ORDER BY id_pengiriman DESC 
LIMIT 1;

☐ Should have 1 row with:
   ☐ id_pengiriman: exists
   ☐ id_user: [YOUR_ID]
   ☐ no_pengiriman: PGR-XXXXX (auto-generated)
   ☐ nama_penerima: John Doe
   ☐ no_telepon: 082123456789
   ☐ email: john@example.com
   ☐ ongkir: 20000
   ☐ status_pengiriman: Menunggu Verifikasi Pembayaran
   ☐ created_at: 2026-01-08 (today)

☐ Pengiriman data saved correctly ✅
```

---

## 🌟 PHASE 4: Test Pembayaran.php (Payment Form)

### Page Load

```
☐ You should be redirected to pembayaran.php
☐ URL should be: /transaksi/pembayaran.php
☐ Page loads without errors
☐ Check console (F12): No errors shown
☐ Page loaded successfully ✅
```

### Progress & Timer Display

```
☐ Progress bar shows: Step 1 ✓, Step 2 ✓, Step 3 (ACTIVE), Step 4
☐ Countdown timer visible and running
☐ Timer showing approximately: 23:59:xx (counting down)
☐ Timer decreases every second
☐ Progress and timer displayed correctly ✅
```

### Pengiriman Summary

```
☐ Left section shows pengiriman recap:
   ☐ No. Pengiriman: PGR-XXXXX (matches database)
   ☐ Status: Menunggu Verifikasi Pembayaran
   ☐ Metode: Regular
   ☐ Alamat: [your address]
   ☐ Ongkir: Rp 20,000

☐ Cart summary shows:
   ☐ Product names
   ☐ Quantities
   ☐ Subtotal
   ☐ Ongkir
   ☐ Total

☐ Pengiriman data displayed correctly ✅
```

### Payment Method Selection

```
☐ Click "Bank Transfer" option
   ☐ Should highlight with blue border
   ☐ Radio button checked

☐ Click "E-Wallet" option
   ☐ Should highlight
   ☐ Previous option unhighlights

☐ Click "Credit Card" option
   ☐ Should highlight

☐ Click "COD" option
   ☐ Should highlight

☐ Payment method selection works ✅
```

### File Upload

#### Option A: Drag & Drop
```
☐ Find payment proof image (JPG or PNG)
☐ Make sure file size < 5MB
☐ Drag file over upload area
☐ Drop file in area
☐ File should appear in preview box
☐ Preview shows: filename, size, remove button
```

#### Option B: Click to Upload
```
☐ Click in upload area
☐ File dialog opens
☐ Select JPG or PNG file
☐ File should appear in preview
```

#### Validation Test
```
☐ Try uploading PNG > 5MB
   ☐ Should show error: "Ukuran file maksimal 5MB"

☐ Try uploading .txt file
   ☐ Should show error: "Format file hanya JPG atau PNG"

☐ Try uploading valid JPG < 5MB
   ☐ Should show preview ✅

☐ File upload working correctly ✅
```

### Form Validation

```
☐ Remove uploaded file, try submit
   ☐ Should show error: "Bukti pembayaran harus diunggah"

☐ Clear payment method, try submit
   ☐ Should show error: "Metode pembayaran harus dipilih"

☐ Enter all valid data:
   Nama Pengirim: John Doe
   Tanggal Transfer: [today's date]
   Payment Method: Bank Transfer
   File: [valid payment proof]

☐ Form validation works ✅
```

### Form Submission & Order Creation

```
☐ Click "Konfirmasi & Buat Pesanan" button
☐ Check Network tab (F12):
   ☐ POST request to /api/payment-handler.php
   ☐ Content-Type: multipart/form-data
   ☐ Status: 200 (success)
   ☐ Response shows: {"success": true, "id_pesanan": 1}

☐ Check browser console: No errors
☐ Page redirects to order-success.php (or shows success message) ✅
```

### File Upload to Server

```bash
☐ Check file was saved:
   ls -la uploads/pembayaran/
   Should show: pembayaran_[USER_ID]_[TIMESTAMP].jpg
   Example: pembayaran_5_1704700000.jpg

☐ Check file size:
   du -h uploads/pembayaran/pembayaran_*.jpg
   Should show correct size

☐ File saved to server ✅
```

---

## 🖱️ PHASE 5: Database Verification

### Pesanan Table

```sql
SELECT * FROM pesanan 
WHERE id_user = [YOUR_ID] 
ORDER BY id_pesanan DESC 
LIMIT 1;

☐ Should have 1 row with:
   ☐ id_pesanan: exists (1 or higher)
   ☐ id_user: [YOUR_ID]
   ☐ id_pengiriman: [ID from pengiriman table]
   ☐ no_pesanan: ORD-XXXXX (auto-generated)
   ☐ subtotal: [product total]
   ☐ ongkir: 20000
   ☐ total_bayar: [subtotal + ongkir]
   ☐ status_pesanan: Menunggu Verifikasi
   ☐ metode_pembayaran: bank_transfer
   ☐ bukti_pembayaran: pembayaran_5_1704700000.jpg
   ☐ created_at: 2026-01-08 (today)

☐ Pesanan created correctly ✅
```

### Detail Pesanan Table

```sql
SELECT * FROM detail_pesanan 
WHERE id_pesanan = [ID from above];

☐ Should have multiple rows (one per product):
   ☐ id_detail_pesanan: auto-increment
   ☐ id_pesanan: [matches pesanan table]
   ☐ id_produk: [product ID]
   ☐ nama_produk: [product name]
   ☐ harga: [unit price]
   ☐ qty: [quantity from cart]
   ☐ subtotal: harga × qty
   ☐ created_at: timestamp

☐ Detail pesanan created correctly ✅
```

### Cart Cleared

```sql
SELECT COUNT(*) as cart_count FROM keranjang 
WHERE id_user = [YOUR_ID];

☐ Should return: 0
   OR run detailed query:
   ☐ SELECT * FROM keranjang WHERE id_user = [YOUR_ID];
   ☐ Should return: empty result (no rows)

☐ Cart cleared successfully ✅
```

### Pengiriman Status Updated

```sql
SELECT id_pengiriman, status_pengiriman, tanggal_konfirmasi 
FROM pengiriman 
WHERE id_user = [YOUR_ID] 
ORDER BY id_pengiriman DESC 
LIMIT 1;

☐ tanggal_konfirmasi should be updated to today's timestamp
☐ Pengiriman status updated ✅
```

### Comprehensive Database Summary

```sql
-- Run this query to verify all data:
SELECT 
  'Total Pengiriman' as metric,
  COUNT(*) as count
FROM pengiriman
WHERE id_user = [YOUR_ID]
UNION ALL
SELECT 'Total Pesanan', COUNT(*) FROM pesanan WHERE id_user = [YOUR_ID]
UNION ALL
SELECT 'Total Detail Pesanan', COUNT(*) FROM detail_pesanan 
WHERE id_pesanan IN (
  SELECT id_pesanan FROM pesanan WHERE id_user = [YOUR_ID]
)
UNION ALL
SELECT 'Cart Items Left', COUNT(*) FROM keranjang WHERE id_user = [YOUR_ID];

☐ Expected results:
   ☐ Total Pengiriman: 1
   ☐ Total Pesanan: 1
   ☐ Total Detail Pesanan: 2-3 (or number of products)
   ☐ Cart Items Left: 0

☐ All database verification passed ✅
```

---

## 🔍 PHASE 6: Debug Logs Review

### Shipping Debug Log

```bash
☐ Check shipping log:
   tail -n 50 logs/shipping_debug.log

☐ Should show entries like:
   [INFO] pengiriman.php loaded
   [INFO] User ID from session: 5
   [INFO] Pengiriman form submitted
   [INFO] Pengiriman data saved: id=1, no_pengiriman=PGR-XXXXX
   [SUCCESS] Redirect to pembayaran.php

☐ No [ERROR] entries should be present
☐ Shipping log looks good ✅
```

### Payment Debug Log

```bash
☐ Check payment log:
   tail -n 50 logs/payment_debug.log

☐ Should show entries like:
   [INFO] pembayaran.php loaded
   [INFO] Pengiriman session valid: id=1
   [INFO] File upload received: pembayaran_5_1704700000.jpg
   [INFO] Pesanan created: id=1, no_pesanan=ORD-XXXXX
   [INFO] Detail pesanan created: 2 items
   [INFO] Cart cleared for user 5
   [SUCCESS] Order complete, redirect to order-success.php

☐ No [ERROR] entries should be present
☐ Payment log looks good ✅
```

---

## 🙋 Common Issues Quick Check

```
☐ If pengiriman.php won't load:
   ☐ Check: transaksi/pengiriman.php exists
   ☐ Check: logs/shipping_debug.log for errors
   ☐ Check: Browser console (F12) for JS errors

☐ If form won't submit:
   ☐ Check: All required fields filled
   ☐ Check: Browser console for errors
   ☐ Check: Network tab shows POST request

☐ If database error:
   ☐ Check: All 3 tables exist in phpMyAdmin
   ☐ Check: logs/shipping_debug.log or payment_debug.log
   ☐ Check: user ID in session matches

☐ If file upload fails:
   ☐ Check: uploads/pembayaran/ folder exists
   ☐ Check: Folder has 755 permissions
   ☐ Check: File < 5MB and JPG/PNG format
   ☐ Check: logs/payment_debug.log for upload error

☐ If cart won't clear:
   ☐ Check: logs/payment_debug.log for DELETE error
   ☐ Check: Verify keranjang table exists
   ☐ Check: User ID matches
```

---

## 🏁 Final Status Check

### Critical Items

```
☐ Database tables exist (3 tables) ........................ REQUIRED
☐ PHP files in correct folders (4 files) ................ REQUIRED
☐ Folders created with permissions (2 folders) ........ REQUIRED
☐ User can login ........................................... REQUIRED
☐ Cart has products ......................................... REQUIRED
☐ Pengiriman form loads ...................................... REQUIRED
☐ Pembayaran form loads ..................................... REQUIRED
☐ Pengiriman data saved ..................................... REQUIRED
☐ Pesanan created .............................................. REQUIRED
☐ Cart cleared ................................................. REQUIRED
```

### Verification Complete?

```
If ALL items above are checked ✅:

✅ CHECKOUT FLOW IS READY FOR PRODUCTION

Next steps:
1. Create order-success.php (success page)
2. Create admin verification panel
3. Add email notifications
4. Create order tracking page
5. Add order history
```

---

## 📚 Documentation References

If you encounter issues:

- **Quick Setup**: Read `QUICKSTART.md`
- **Full Setup**: Read `SETUP_CHECKOUT_FLOW.md`
- **Detailed Testing**: Read `TESTING_CHECKOUT_FLOW.md`
- **Database Issues**: Run `database/DEBUG-FOREIGN-KEY.sql`
- **Implementation Overview**: Read `IMPLEMENTATION_SUMMARY.md`

---

## ✅ Completion Certificate

```
🎆 CHECKOUT FLOW IMPLEMENTATION - VERIFICATION COMPLETE 🎆

Project: MobileNest E-commerce
Feature: 3-Step Checkout Flow
Date: 2026-01-08

Phase 1: Setup & Configuration .......... ✅ PASSED
Phase 2: Login & Cart Preparation ....... ✅ PASSED
Phase 3: Shipping Form Testing .......... ✅ PASSED
Phase 4: Payment Form Testing ........... ✅ PASSED
Phase 5: Database Verification .......... ✅ PASSED
Phase 6: Debug Logs Review .............. ✅ PASSED

OVERALL STATUS: ✅ READY FOR PRODUCTION

Tested by: [Your Name]
Date: [Today's Date]
```

---

**🌟 Great work! Your checkout flow is ready! 🌟**

For any questions or issues, refer to the documentation files or check the debug logs.
