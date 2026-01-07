# 🔧 DEBUG HTTP 500 Error - Troubleshooting Guide

**Status**: Simplified code deployed
**Date**: 2026-01-08

---

## 🎯 What We Changed

### ✅ Code Optimizations
- ✅ Removed heavy CSS from PHP pages
- ✅ Simplified HTML structure
- ✅ Reduced database queries
- ✅ Better error handling with try-catch
- ✅ Cleaner API responses
- ✅ File size reduced by ~60%

### 📝 Files Updated
```
✅ transaksi/pengiriman.php         - Simplified UI
✅ transaksi/pembayaran.php         - Simplified UI
✅ api/shipping-handler.php         - Clean code
✅ api/payment-handler.php          - Clean code
```

---

## 🔍 Debugging HTTP 500

### Step 1: Check PHP Error Logs

```bash
# On XAMPP (Windows)
C:\xampp\apache\logs\error.log

# On XAMPP (Mac)
/Applications/XAMPP/logs/apache_error.log

# On Linux
/var/log/apache2/error.log

# Application logs
logs/shipping_debug.log
logs/payment_debug.log
```

**What to look for:**
```
[ERROR] ...
[Parse error] ...
[Fatal error] ...
[Warning] ...
```

### Step 2: Check Browser Console

1. Open browser
2. Press `F12` (Developer Tools)
3. Go to **Console** tab
4. Look for error messages
5. Check **Network** tab:
   - Click on request to pengiriman.php
   - See what status code?
   - Check "Response" tab for error details

### Step 3: Check Database Connection

```bash
# Test MySQL connection
mysql -u root -p

# Show databases
SHOW DATABASES;

# Check if mobilenest_db exists
USE mobilenest_db;

# Check tables
SHOW TABLES;

# Check pengiriman table
DESCRIBE pengiriman;
```

### Step 4: Check File Permissions

```bash
# Check logs folder
ls -la logs/
# Should show: drwxr-xr-x (755)

# Check uploads folder
ls -la uploads/pembayaran/
# Should show: drwxr-xr-x (755)

# Fix permissions if needed
chmod 755 logs/
chmod 755 uploads/pembayaran/
```

---

## 🚨 Common HTTP 500 Causes

### 1. **PHP Syntax Error**
```php
// ❌ WRONG - Missing semicolon
$variable = 'value'
echo $variable;

// ✅ CORRECT
$variable = 'value';
echo $variable;
```

**Check in logs:**
```
Parse error: syntax error in pengiriman.php on line 42
```

**Fix:**
- Pull latest code: `git pull origin main`
- Check line number in error
- Look for missing `;` or `}`

### 2. **Database Connection Error**
```php
// Error: "Error connecting to database"
```

**Fix:**
```bash
# Check config.php
cat config/config.php

# Verify values:
# - $db_host = 'localhost' ✓
# - $db_user = 'root' ✓
# - $db_password = '' (or your password)
# - $db_name = 'mobilenest_db' ✓

# Test connection
mysql -u root -p mobilenest_db -e "SHOW TABLES;"
```

### 3. **Table Not Found**
```
Error: Table 'mobilenest_db.pengiriman' doesn't exist
```

**Fix:**
```bash
# Import SQL
mysql -u root -p mobilenest_db < database/shipping-migration.sql

# Or in phpMyAdmin:
# 1. Select mobilenest_db
# 2. Import > shipping-migration.sql
# 3. Go

# Verify tables exist
USE mobilenest_db;
SHOW TABLES;
```

### 4. **File Upload Permission**
```
Error: Failed to upload file
```

**Fix:**
```bash
# Create folder
mkdir -p uploads/pembayaran
chmod 755 uploads/pembayaran

# Verify
ls -la uploads/ | grep pembayaran
# Should show: drwxr-xr-x
```

### 5. **Session Issues**
```
Error: User not logged in / Session invalid
```

**Fix:**
- Clear browser cookies
- Login again
- Check `$_SESSION['user_id']` is set

### 6. **JSON Encoding Error**
```php
// ❌ WRONG - Resource can't be JSON encoded
echo json_encode($resource);

// ✅ CORRECT
echo json_encode([
    'success' => true,
    'data' => $data
]);
```

---

## 🛠️ Quick Debug Workflow

```
1. Pull latest code
   git pull origin main

2. Check error logs
   tail -f logs/shipping_debug.log
   tail -f logs/payment_debug.log

3. Check PHP error log
   tail -f apache_error.log (or xampp logs)

4. Check browser console (F12)
   Look for JavaScript errors

5. Check Network tab (F12)
   Click request, see response

6. Check database
   mysql -u root -p mobilenest_db -e "SHOW TABLES;"

7. Check permissions
   ls -la logs/
   ls -la uploads/pembayaran/

8. Restart services
   - Restart XAMPP
   - Restart Apache
   - Restart MySQL

9. Test again
   - Visit pengiriman.php
   - Check console for errors
```

---

## 📋 Checklist Before Testing

```
✅ Latest code pulled
   git pull origin main

✅ MySQL running
   Service: MySQL running ✓
   Database: mobilenest_db exists
   Tables: pengiriman, pesanan, detail_pesanan

✅ Apache running
   Service: Apache running ✓
   Logs: No errors

✅ Folders created
   mkdir -p uploads/pembayaran logs
   chmod 755 uploads/pembayaran logs

✅ User logged in
   Can see username in header
   Session: PHPSESSID set

✅ Cart has items
   Add 1-2 products
   Cart shows items

✅ Ready to test
   Click "Lanjut ke Pengiriman"
```

---

## 📊 Common Error Codes

| Code | Meaning | Solution |
|------|---------|----------|
| 400 | Bad Request | Check form validation, check browser console |
| 401 | Unauthorized | User not logged in, need to login |
| 403 | Forbidden | Permission denied, check file permissions |
| 404 | Not Found | File/table not found, check paths |
| 500 | Server Error | PHP error, check error logs |
| 502 | Bad Gateway | Apache/MySQL not running |
| 503 | Service Unavailable | Server overloaded, restart services |

---

## 🔍 Real-Time Error Monitoring

### Terminal 1: Watch shipping logs
```bash
tail -f logs/shipping_debug.log
```

### Terminal 2: Watch payment logs
```bash
tail -f logs/payment_debug.log
```

### Terminal 3: Watch PHP errors
```bash
tail -f /path/to/apache_error.log
```

### Terminal 4: Test in browser
```
http://localhost/MobileNestV3/transaksi/pengiriman.php
```

Watch all 3 logs - you'll see exactly where error occurs!

---

## 💡 Tips for Debugging

### 1. Use Browser Console (F12)
```javascript
// Check what data is being sent
console.log('Form data:', new FormData(document.getElementById('shippingForm')));

// Check API response
fetch('../api/shipping-handler.php', {method: 'POST', body: new FormData(form)})
  .then(r => r.json())
  .then(d => console.log('Response:', d));
```

### 2. Add Debug Output
```php
<?php
error_log('DEBUG: ' . json_encode($variable));
var_dump($variable);
die();
?>
```

### 3. Test API Directly
```bash
# Test shipping API with curl
curl -X POST http://localhost/MobileNestV3/api/shipping-handler.php \
  -F "nama_penerima=John" \
  -F "no_telepon=082123456789" \
  -F "email=john@example.com" \
  -F "alamat_lengkap=Jl. Sudirman" \
  -F "kota=Bandung" \
  -F "kode_pos=40141" \
  -F "metode_pengiriman=regular"
```

### 4. Check Recent Commits
```bash
# See what changed
git log --oneline -10

# See diff of latest commit
git show HEAD

# Revert if needed
git revert HEAD
```

---

## 🚀 Testing New Simplified Code

### Test 1: Load pengiriman.php
```
1. Open: http://localhost/MobileNestV3/transaksi/pengiriman.php
2. Should load without errors ✅
3. Check console (F12): No errors ✅
4. Form should pre-fill ✅
```

### Test 2: Test Shipping Method
```
1. Click "Express"
2. Cost should update to Rp 50.000 ✅
3. Total should update ✅
```

### Test 3: Submit Form
```
1. Fill form with valid data
2. Click "Lanjut ke Pembayaran"
3. Check Network tab (F12):
   - Status: 200 ✅
   - Response: {"success": true} ✅
4. Should redirect to pembayaran.php ✅
```

### Test 4: Check Database
```sql
SELECT * FROM pengiriman WHERE id_user = 5 ORDER BY id_pengiriman DESC LIMIT 1;
-- Should have 1 row ✅
```

---

## 📞 Need Help?

### Check These Files First
1. `logs/shipping_debug.log` - Shipping errors
2. `logs/payment_debug.log` - Payment errors
3. Apache error log - Server errors
4. Browser console (F12) - Client errors
5. Browser Network tab (F12) - API responses

### Then Review
1. TESTING_CHECKOUT_FLOW.md - Testing guide
2. SETUP_CHECKOUT_FLOW.md - Setup issues
3. This file - Debugging guide

---

## ✅ All Good?

If everything works:
```
✅ pengiriman.php loads
✅ Form pre-fills
✅ Shipping methods work
✅ Form submits successfully
✅ pembayaran.php loads
✅ Payment form works
✅ File upload works
✅ Order created in database
✅ No errors in logs
```

**Then you're good to go!** 🎉

---

**Happy debugging!** 🔧
