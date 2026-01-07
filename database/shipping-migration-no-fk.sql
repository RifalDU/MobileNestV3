-- ============================================
-- Database Migration for Shipping & Orders
-- MobileNest E-Commerce Platform
-- 
-- VERSION: Without Foreign Keys
-- Use this if you get foreign key constraint errors
-- ============================================

-- ⚠️ DROP existing tables first (OPTIONAL)
-- Uncomment if you have existing tables
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
  INDEX `idx_id_user` (`id_user`),
  INDEX `idx_no_pengiriman` (`no_pengiriman`),
  INDEX `idx_status` (`status_pengiriman`)
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
  INDEX `idx_id_user` (`id_user`),
  INDEX `idx_id_pengiriman` (`id_pengiriman`),
  INDEX `idx_no_pesanan` (`no_pesanan`),
  INDEX `idx_status` (`status_pesanan`)
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
  INDEX `idx_id_pesanan` (`id_pesanan`),
  INDEX `idx_id_produk` (`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Add Indexes for Performance
-- ============================================
-- Optional: Add these for better performance
-- ALTER TABLE `pengiriman` ADD INDEX `idx_id_user_tanggal` (`id_user`, `tanggal_pengiriman`);
-- ALTER TABLE `pesanan` ADD INDEX `idx_id_user_tanggal` (`id_user`, `tanggal_pesanan`);

-- ============================================
-- Verification Queries
-- ============================================
-- Run these to verify tables were created:
-- 
-- SHOW TABLES;
-- 
-- DESCRIBE pengiriman;
-- DESCRIBE pesanan;
-- DESCRIBE detail_pesanan;
-- 
-- SELECT COUNT(*) FROM pengiriman;
-- SELECT COUNT(*) FROM pesanan;
-- SELECT COUNT(*) FROM detail_pesanan;
