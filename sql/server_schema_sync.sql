-- Sinkronisasi skema server dengan dump localhost.sql dan kebutuhan source code.
-- Hanya membuat tabel/kolom yang hilang; tidak menyalin atau mengubah isi tabel.
-- Tidak ada INSERT, UPDATE, DELETE, TRUNCATE, atau DROP.
-- Pilih database aplikasi server yang benar sebelum menjalankan skrip ini.
-- Tabel activity_reminder_logs dan activity_reminder_phases sudah ada pada kedua dump.
-- Membutuhkan MariaDB 10.0.2+ (dump server tercatat dibuat dengan MariaDB 11.8.6).

-- Kolom ini ada di localhost.tb_member_logs tetapi belum ada di dump server.
-- IF NOT EXISTS melewati kolom bila sudah ada.
ALTER TABLE `tb_member_logs`
  ADD COLUMN IF NOT EXISTS `id_telegram` VARCHAR(50) NULL AFTER `id_hfm`;

-- Metadata campaign HFM dan atribusi sumber registrasi yang digunakan aplikasi.
-- Jalankan patch ini pada localhost dan server agar kolom aplikasi tersedia.
ALTER TABLE `tb_member_vip`
  ADD COLUMN IF NOT EXISTS `campaign` VARCHAR(255) NULL DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `registration_source` VARCHAR(50) NULL DEFAULT NULL AFTER `id_telegram`,
  ADD COLUMN IF NOT EXISTS `registration_source_id` INT UNSIGNED NULL DEFAULT NULL AFTER `registration_source`,
  ADD KEY IF NOT EXISTS `idx_tb_member_vip_registration_source` (`registration_source`, `registration_source_id`);

-- Tabel link grup yang ada di localhost tetapi belum ada di dump server.
CREATE TABLE IF NOT EXISTS `bot_group_links` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` VARCHAR(20) NOT NULL,
  `group_name` VARCHAR(150) NOT NULL,
  `invite_link` VARCHAR(500) NOT NULL,
  `group_id` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bot_group_links_platform` (`platform`),
  KEY `idx_bot_group_links_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel jadwal bot yang ada di localhost tetapi belum ada di dump server.
CREATE TABLE IF NOT EXISTS `bot_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_key` VARCHAR(50) NOT NULL,
  `schedule_name` VARCHAR(150) NOT NULL,
  `schedule_type` VARCHAR(30) NOT NULL,
  `run_at` CHAR(5) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bot_schedule_key` (`schedule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel anggota grup WhatsApp yang ada di localhost tetapi belum ada di dump server.
CREATE TABLE IF NOT EXISTS `whatsapp_group_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `whatsapp_id` VARCHAR(100) NOT NULL,
  `phone_number` VARCHAR(30) DEFAULT NULL,
  `display_name` VARCHAR(150) DEFAULT NULL,
  `group_id` VARCHAR(100) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_whatsapp_group_member_identity` (`whatsapp_id`, `group_id`),
  KEY `idx_whatsapp_group_member_phone` (`phone_number`),
  KEY `idx_whatsapp_group_member_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel event Telegram agar status masuk/keluar grup dapat ditampilkan di dashboard.
CREATE TABLE IF NOT EXISTS `telegram_group_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `telegram_id` VARCHAR(50) NOT NULL,
  `phone_number` VARCHAR(30) DEFAULT NULL,
  `display_name` VARCHAR(150) DEFAULT NULL,
  `group_id` VARCHAR(100) NOT NULL,
  `group_name` VARCHAR(150) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_telegram_group_member_identity` (`telegram_id`, `group_id`),
  KEY `idx_telegram_group_member_phone` (`phone_number`),
  KEY `idx_telegram_group_member_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel metadata migrasi CI4. Dibuat kosong; riwayat migration localhost tidak disalin.
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` VARCHAR(255) NOT NULL,
  `class` VARCHAR(255) NOT NULL,
  `group` VARCHAR(255) NOT NULL,
  `namespace` VARCHAR(255) NOT NULL,
  `time` INT NOT NULL,
  `batch` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verifikasi skema tanpa memeriksa tabel sistem.
SHOW COLUMNS FROM `tb_member_logs` LIKE 'id_telegram';
SHOW COLUMNS FROM `tb_member_vip` LIKE 'registration_source';
SHOW TABLES LIKE 'bot_group_links';
SHOW TABLES LIKE 'bot_schedules';
SHOW TABLES LIKE 'whatsapp_group_members';
SHOW TABLES LIKE 'telegram_group_members';
SHOW TABLES LIKE 'migrations';
