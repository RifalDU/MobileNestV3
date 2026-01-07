-- ============================================
-- Database Migration for Shipping & Orders
-- MobileNest E-Commerce Platform
-- 
-- IMPORTANT: Make sure:
-- 1. Table 'pengguna' or 'users' exists
-- 2. Table 'produk' exists
-- 3. Run queries ONE BY ONE in phpMyAdmin
-- ============================================

-- ⚠️ OPTIONAL: Drop existing tables if error
-- Uncomment these lines if you get foreign key errors
-- DROP TABLE IF EXISTS `detail_pesanan`;
-- DROP TABLE IF EXISTS `pesanan`;
-- DROP TABLE IF EXISTS `pengiriman`;

-- ============================================
-- Tabel untuk data pengiriman (Shipping)
-- ============================================
CREATE TABLE IF NOT EXISTS `pengiriman` (
  `id_pengiriman` INT NOT NULL AUTO_INCREMENT,
  `id_user` INT NOT NULL,
  `no_pengiriman` VARCHAR(50) NOT NULL UNIQUE,
  `nama_penerima` VARCHAR(100) NOT NULL,
  `no_telepon` VARCHAR(15) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `provinsi` VARCHAR(50) NOT NULL,
  `kota` VARCHAR(50) NOT NULL,
  `kecamatan` VARCHAR(50) NOT NULL,
  `kode_pos` VARCHAR(10) NOT NULL,
  `alamat_lengkap` TEXT NOT NULL,
  `metode_pengiriman` ENUM('regular', 'express', 'same_day') NOT NULL DEFAULT 'regular',
  `ongkir` INT NOT NULL DEFAULT 0,
  `catatan` TEXT,
  `status_pengiriman` VARCHAR(50) NOT NULL DEFAULT 'Menunggu Verifikasi Pembayaran',
  `tanggal_pengiriman` DATETIME NOT NULL,
  `tanggal_konfirmasi` DATETIME,
  `tanggal_diterima` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengiriman`),
  KEY `idx_id_user` (`id_user`),
  KEY `idx_no_pengiriman` (`no_pengiriman`),
  KEY `idx_status` (`status_pengiriman`),
  CONSTRAINT `fk_pengiriman_user` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel untuk data pesanan (Orders)
-- ============================================
CREATE TABLE IF NOT EXISTS `pesanan` (
  `id_pesanan` INT NOT NULL AUTO_INCREMENT,
  `id_user` INT NOT NULL,
  `id_pengiriman` INT NOT NULL,
  `no_pesanan` VARCHAR(50) NOT NULL UNIQUE,
  `subtotal` INT NOT NULL,
  `diskon` INT NOT NULL DEFAULT 0,
  `ongkir` INT NOT NULL,
  `total_bayar` INT NOT NULL,
  `status_pesanan` VARCHAR(50) NOT NULL DEFAULT 'Menunggu Verifikasi',
  `metode_pembayaran` VARCHAR(50) NOT NULL,
  `bukti_pembayaran` VARCHAR(255),
  `tanggal_pesanan` DATETIME NOT NULL,
  `tanggal_pembayaran` DATETIME,
  `tanggal_pengiriman` DATETIME,
  `tanggal_diterima` DATETIME,
  `catatan` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pesanan`),
  KEY `idx_id_user` (`id_user`),
  KEY `idx_id_pengiriman` (`id_pengiriman`),
  KEY `idx_no_pesanan` (`no_pesanan`),
  KEY `idx_status` (`status_pesanan`),
  CONSTRAINT `fk_pesanan_user` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_pesanan_pengiriman` FOREIGN KEY (`id_pengiriman`) REFERENCES `pengiriman` (`id_pengiriman`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel untuk detail pesanan (Order Items)
-- ============================================
CREATE TABLE IF NOT EXISTS `detail_pesanan` (
  `id_detail_pesanan` INT NOT NULL AUTO_INCREMENT,
  `id_pesanan` INT NOT NULL,
  `id_produk` INT NOT NULL,
  `nama_produk` VARCHAR(255) NOT NULL,
  `harga` INT NOT NULL,
  `qty` INT NOT NULL,
  `subtotal` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_detail_pesanan`),
  KEY `idx_id_pesanan` (`id_pesanan`),
  KEY `idx_id_produk` (`id_produk`),
  CONSTRAINT `fk_detail_pesanan_order` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_pesanan_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Verification Queries
-- ============================================
-- Run these to verify tables were created:
-- 
-- SHOW TABLES LIKE 'pengiriman';
-- SHOW TABLES LIKE 'pesanan';
-- SHOW TABLES LIKE 'detail_pesanan';
-- 
-- DESCRIBE pengiriman;
-- DESCRIBE pesanan;
-- DESCRIBE detail_pesanan;
-- 
-- Check foreign keys:
-- SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME 
-- FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
-- WHERE TABLE_NAME IN ('pengiriman', 'pesanan', 'detail_pesanan');
