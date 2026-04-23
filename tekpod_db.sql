-- ============================================
-- TEKPOD - Database Schema
-- MySQL / MariaDB
-- ============================================

CREATE DATABASE IF NOT EXISTS tekpod_db 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE tekpod_db;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('owner', 'operator', 'supervisor') NOT NULL DEFAULT 'operator',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ============================================
-- PROCESSES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS processes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_proses VARCHAR(50) NOT NULL,
    urutan INT NOT NULL,
    icon VARCHAR(10) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_nama_proses (nama_proses),
    INDEX idx_urutan (urutan)
) ENGINE=InnoDB;

-- ============================================
-- ORDERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_job VARCHAR(200) NOT NULL,
    pelanggan VARCHAR(200) NOT NULL,
    qty_order INT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    status ENUM('draft', 'progress', 'selesai') NOT NULL DEFAULT 'draft',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_tanggal (tanggal_mulai)
) ENGINE=InnoDB;

-- ============================================
-- ORDER_PROCESSES (which processes apply to each order)
-- ============================================
CREATE TABLE IF NOT EXISTS order_processes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    process_id INT NOT NULL,
    urutan INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (process_id) REFERENCES processes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_process (order_id, process_id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ============================================
-- PRODUCTION_LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS production_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    process_id INT NOT NULL,
    shift TINYINT DEFAULT NULL COMMENT '1=Pagi, 2=Siang, 3=Malam',
    tipe_proses VARCHAR(50) DEFAULT NULL COMMENT 'e.g., Gloss/Matte for Laminating',
    hasil_bgs INT NOT NULL DEFAULT 0 COMMENT 'Bagus',
    hasil_nc INT NOT NULL DEFAULT 0 COMMENT 'Not Clean',
    hasil_ng INT NOT NULL DEFAULT 0 COMMENT 'Reject',
    operator_id INT DEFAULT NULL,
    operator_name VARCHAR(100) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (process_id) REFERENCES processes(id) ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_process (order_id, process_id),
    INDEX idx_operator (operator_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- SEED DATA: Default Processes
-- ============================================
INSERT IGNORE INTO processes (nama_proses, urutan, icon) VALUES
    ('Cetak', 1, '🖨️'),
    ('Laminating', 2, '🔲'),
    ('Spot UV', 3, '✨'),
    ('Hotstamp', 4, '🔥'),
    ('Pond', 5, '✂️'),
    ('Lem Mesin', 6, '🧴'),
    ('QC / Sortir', 7, '✅');

-- ============================================
-- SEED DATA: Default Owner User
-- Passwords hashed with password_hash()
-- ============================================
INSERT IGNORE INTO users (nama, email, password, role) VALUES
    ('Owner Tekpod', 'owner@tekpod.com', '$2y$10$pu84clZcGbPcy8WwPrR.Y.Kc7ylufPegih3cFtW5K5eLDnKJUpaBG', 'owner'),
    ('Budi Santoso', 'budi@tekpod.com', '$2y$10$Zb3PeNvXTO6H9IYy0PQntOZa3xQPh.78gv1SZW3a0amqiyxo2DZlq', 'operator'),
    ('Dewi Rahayu', 'dewi@tekpod.com', '$2y$10$Zb3PeNvXTO6H9IYy0PQntOZa3xQPh.78gv1SZW3a0amqiyxo2DZlq', 'operator'),
    ('Eko Prasetyo', 'eko@tekpod.com', '$2y$10$RGkE.tArw8swE9vr3W/J2OiaipFw9l55TxH4HbLg5U15VxKg01ZZK', 'supervisor'),
    ('Fitri Andini', 'fitri@tekpod.com', '$2y$10$Zb3PeNvXTO6H9IYy0PQntOZa3xQPh.78gv1SZW3a0amqiyxo2DZlq', 'operator');

-- ============================================
-- SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- SEED DATA: Default Settings
-- ============================================
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('ng_threshold', '100'),
    ('company_name', 'TEKPOD'),
    ('company_tagline', 'Tracking Produksi Percetakan');

