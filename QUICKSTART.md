# 🚀 QUICKSTART - Checkout Flow

**Estimated time: 5-10 minutes**

---

## 📄 What Was Built

3-step checkout flow:
1. **Pengiriman** (Shipping) - Fill address & select shipping method
2. **Pembayaran** (Payment) - Select payment & upload proof
3. **Success** - Order confirmed (to be created)

---

## 👕 What You Need

- [x] Database tables created in `mobilenest_db`
- [x] Files pulled from GitHub
- [ ] Folders created (next step)
- [ ] User logged in
- [ ] Products in cart

---

## 🚀 5-Minute Setup

### 1. Pull Latest Code
```bash
cd MobileNestV3
git pull origin main
```

### 2. Create Folders
```bash
mkdir -p uploads/pembayaran logs
chmod 755 uploads/pembayaran logs
```

### 3. Verify Database Tables

Open phpMyAdmin, run:
```sql
SHOW TABLES LIKE 'pengiriman';
SHOW TABLES LIKE 'pesanan';
SHOW TABLES LIKE 'detail_pesanan';
```

Should show 3 tables ✅

### 4. Login & Test

1. Open: `http://localhost/MobileNestV3`
2. Login with your account
3. Add products to cart
4. Click "Lanjut ke Pengiriman"
5. Fill shipping form
6. Click "Lanjut ke Pembayaran"
7. Upload payment proof
8. Click "Konfirmasi"

**Done!** 🎉

---

## 🗑 File Locations

```
Key Files:
  /transaksi/pengiriman.php       → Shipping form
  /transaksi/pembayaran.php       → Payment form
  /api/shipping-handler.php       → Save shipping
  /api/payment-handler.php        → Save payment
  
Database:
  /database/shipping-migration.sql       → Main SQL (with FK)
  /database/shipping-migration-no-fk.sql → Backup (no FK)
  /database/DEBUG-FOREIGN-KEY.sql        → Debug queries

Documentation:
  SETUP_CHECKOUT_FLOW.md    → Full setup guide
  TESTING_CHECKOUT_FLOW.md  → Testing procedures
  QUICKSTART.md             → This file

Folders:
  /uploads/pembayaran/      → Payment proof storage
  /logs/                    → Debug logs
```

---

## 🔍 How to Debug

### Check Logs
```bash
# View shipping errors
tail -f logs/shipping_debug.log

# View payment errors
tail -f logs/payment_debug.log
```

### Check Database
```sql
-- View your order
SELECT * FROM pengiriman 
WHERE id_user = [YOUR_ID] 
ORDER BY id_pengiriman DESC LIMIT 1;

-- View your payment
SELECT * FROM pesanan 
WHERE id_user = [YOUR_ID] 
ORDER BY id_pesanan DESC LIMIT 1;

-- View items in order
SELECT * FROM detail_pesanan 
WHERE id_pesanan = [PESANAN_ID];
```

### Browser Console (F12)
```js
// Check for JavaScript errors
console.log('Errors visible above')

// Check API response
// Open Network tab, submit form, check response
```

---

## 🎉 Expected Results

### After Completing Checkout:

**Database should have:**
```
pengiriman table:   1 new row (your shipping address)
pesanan table:      1 new row (your order)
detail_pesanan:     N rows (one per product)
keranjang table:    Empty (cleared)
uploads/ folder:    payment_proof.jpg file
```

**Browser should show:**
```
Success message: "Pesanan berhasil dibuat"
Order number: ORD-XXXXX
Redirect to: order-success.php (to be created)
```

---

## ❔ Common Issues

| Problem | Solution |
|---------|----------|
| "Gagal menyimpan data pengiriman" | Check logs/shipping_debug.log |
| File upload fails | Check uploads/pembayaran/ exists & writable |
| Session invalid | Login again |
| Table doesn't exist | Import shipping-migration.sql |
| Foreign key error | Use shipping-migration-no-fk.sql |

---

## 📕 Next Steps

After successful test:

```
1. Create order-success.php
2. Create admin payment verification panel
3. Add email notifications
4. Create order tracking page
5. Create order history page
```

---

## 💱 Reference Docs

- **Full Setup**: `SETUP_CHECKOUT_FLOW.md`
- **Testing Guide**: `TESTING_CHECKOUT_FLOW.md`
- **Database Debug**: `database/DEBUG-FOREIGN-KEY.sql`

---

## ✅ Checklist

```
☐ Database tables created (3 tables)
☐ Code pulled from GitHub
☐ Folders created (uploads/pembayaran, logs)
☐ Permissions set (755)
☐ User logged in
☐ Products in cart
☐ Pengiriman form tested
☐ Pembayaran form tested
☐ Database verified
☐ Logs checked
```

---

**Ready to test? Let's go! 🚀**

For detailed testing steps, see `TESTING_CHECKOUT_FLOW.md`
