-- Log Karyawan — schema + seed
-- ponytail: $2b$ and $2y$ are identical algo; PHP password_verify() accepts both since 5.6
CREATE DATABASE IF NOT EXISTS log_karyawan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE log_karyawan;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('karyawan','pic','direktur','admin') NOT NULL DEFAULT 'karyawan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS logbooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pic_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam DECIMAL(4,2) NOT NULL,
    aktivitas TEXT NOT NULL,
    status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
    catatan_pic TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (pic_id) REFERENCES users(id)
);

-- Seed users — all passwords are: password123
INSERT INTO users (name, email, password, role) VALUES
('Admin Sistem',    'admin@company.com',    '$2b$10$mjLAS5Wd20V7mH6QvDiu8uKz1vex3M6ItNJe27Yu1qrPP7U0K0cBy', 'admin'),
('Direktur Utama',  'direktur@company.com', '$2b$10$dUKmQew2AVvxGWndUFB0NubjP12YzMxeblwp.sJWyTlcuiTqkvv2O', 'direktur'),
('Budi Santoso',    'budi@company.com',     '$2b$10$z4vzg1wnm3bnjaGwlVnIP.gHFCHwSonXJlXK1vAK4n5xq4a4ZJAEq', 'pic'),
('Sari Dewi',       'sari@company.com',     '$2b$10$/VjfqDxYFuTnv8mM1HDb6enRtaq7Wk9Vkcs0AuunMWjNT7ATpvdoW', 'pic'),
('Andi Pratama',    'andi@company.com',     '$2b$10$mjLAS5Wd20V7mH6QvDiu8uKz1vex3M6ItNJe27Yu1qrPP7U0K0cBy', 'karyawan'),
('Rina Kusuma',     'rina@company.com',     '$2b$10$0b8IOIFLJrcejGHBM8G5Yu7XEVlLrTaFWqlBEzJo/usyLfkBuvl5y', 'karyawan'),
('Doni Wijaya',     'doni@company.com',     '$2b$10$2utK/sB6NFsOeRqA0qE3WuDJedZQZxeC/6JD5lqHWC4oyMhWHdI.S', 'karyawan')
ON DUPLICATE KEY UPDATE password=VALUES(password);
-- ↑ ON DUPLICATE KEY: re-running the script fixes existing wrong hashes without wiping data

-- Fix existing installs that used the wrong Laravel default hash
UPDATE users SET password='$2b$10$dUKmQew2AVvxGWndUFB0NubjP12YzMxeblwp.sJWyTlcuiTqkvv2O' WHERE email='direktur@company.com' AND password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
UPDATE users SET password='$2b$10$z4vzg1wnm3bnjaGwlVnIP.gHFCHwSonXJlXK1vAK4n5xq4a4ZJAEq' WHERE email='budi@company.com'     AND password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
UPDATE users SET password='$2b$10$/VjfqDxYFuTnv8mM1HDb6enRtaq7Wk9Vkcs0AuunMWjNT7ATpvdoW' WHERE email='sari@company.com'     AND password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
UPDATE users SET password='$2b$10$mjLAS5Wd20V7mH6QvDiu8uKz1vex3M6ItNJe27Yu1qrPP7U0K0cBy' WHERE email='andi@company.com'     AND password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
UPDATE users SET password='$2b$10$0b8IOIFLJrcejGHBM8G5Yu7XEVlLrTaFWqlBEzJo/usyLfkBuvl5y' WHERE email='rina@company.com'     AND password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
UPDATE users SET password='$2b$10$2utK/sB6NFsOeRqA0qE3WuDJedZQZxeC/6JD5lqHWC4oyMhWHdI.S' WHERE email='doni@company.com'     AND password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
UPDATE users SET password='$2b$10$mjLAS5Wd20V7mH6QvDiu8uKz1vex3M6ItNJe27Yu1qrPP7U0K0cBy' WHERE email='admin@company.com'    AND password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Upgrade: hapus kolom department dari instalasi lama
ALTER TABLE users MODIFY COLUMN role ENUM('karyawan','pic','direktur','admin') NOT NULL DEFAULT 'karyawan';
